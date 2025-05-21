<?php
if (!defined('ACCESS_GRANTED')) {
    define('ACCESS_GRANTED', true);
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/config.php';

function loginUser($username, $password) {
    global $pdo;

    try {
        $stmt = $pdo->prepare("SELECT id, username, password, role, department_id, session_id, session_expires_at FROM users WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password'])) {
            log_message('WARNING', "Nieudana próba logowania dla użytkownika {$username}.");
            return "Nieprawidłowy login lub hasło.";
        }

        // Sprawdzenie aktywności sesji
        if (!empty($user['session_id']) && $user['session_id'] !== session_id()) {
            if (!empty($user['session_expires_at']) && strtotime($user['session_expires_at']) > time()) {
                log_message('WARNING', "Użytkownik {$username} próbował się zalogować, ale jest już aktywny na innym urządzeniu.");
                return "Użytkownik jest już zalogowany na innym urządzeniu.";
            }
        }

        // Generowanie nowego session_id
        session_regenerate_id(true);
        $newSessionId = session_id();

        // Aktualizacja session_id i czasu wygaśnięcia sesji w bazie
        $expiry = date('Y-m-d H:i:s', time() + 1800); // 30 minut
        $updateStmt = $pdo->prepare("UPDATE users SET session_id = ?, session_expires_at = ? WHERE id = ?");
        $updateStmt->execute([$newSessionId, $expiry, $user['id']]);

        // Ustawiamy dane użytkownika w sesji
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['department_id'] = $user['department_id'];

        log_message('INFO', "Użytkownik {$username} zalogował się pomyślnie.");
        return true;
    } catch (PDOException $e) {
        error_log("Błąd logowania: " . $e->getMessage());
        return "Wystąpił błąd podczas logowania.";
    }
}

function logoutUser() {
    global $pdo;

    if (!isset($_SESSION['user_id'])) {
        return;
    }

    $userId = $_SESSION['user_id'];

    try {
        // Wyczyść `session_id` oraz `session_expires_at` w tabeli users
        $stmt = $pdo->prepare("UPDATE users SET session_id = NULL, session_expires_at = NULL WHERE id = :user_id");
        $stmt->execute([':user_id' => $userId]);
    } catch (PDOException $e) {
        error_log("Błąd podczas usuwania session_id użytkownika: " . $e->getMessage());
    }

    // Zniszczenie sesji
    session_unset();
    session_destroy();

    // Przekierowanie na stronę logowania
    header("Location: " . LOGIN_PAGE);
    exit();
}

// Obsługa żądania GET do wylogowania
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    logoutUser();
}

/**
 * Pobranie szczegółów użytkownika z sesji
 */
function getUserDetails() {
    return [
        'role' => $_SESSION['role'] ?? 'read',
        'department_id' => $_SESSION['department_id'] ?? 0,
    ];
}


/**
 * Sprawdzenie, czy użytkownik ma dostęp do danej tabeli
 */
function isAccessible($table_name) {
    global $pdo;
    $user = getUserDetails();

    if ($user['role'] === 'admin') {
        return true;
    }

    try {
        $stmt = $pdo->prepare("SELECT is_visible FROM table_permissions WHERE table_name = ? AND department_id = ? AND is_visible = TRUE");
        $stmt->execute([$table_name, $user['department_id']]);
        return (bool)$stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log("Błąd sprawdzania uprawnień: " . $e->getMessage());
        return false;
    }
}

/**
 * Pobiera widoczne kolumny dla tabeli w zależności od roli użytkownika i działu.
 *
 * @param PDO $pdo Obiekt PDO do komunikacji z bazą danych.
 * @param string $tableName Nazwa tabeli.
 * @param int $departmentId Identyfikator działu.
 * @return array Lista widocznych kolumn.
 */
function getVisibleFields(PDO $pdo, string $tableName, int $departmentId): array {
    try {
        // Administrator ma dostęp do wszystkich kolumn
        if ($_SESSION['role'] === 'admin') {
            $query = "SHOW COLUMNS FROM $tableName";
            $stmt = $pdo->query($query);
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        }

        // Pobranie wszystkich kolumn tabeli
        $query = "SHOW COLUMNS FROM $tableName";
        $stmt = $pdo->query($query);
        $allColumns = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // Pobranie niewidocznych kolumn z field_permissions
        $query = "SELECT field_name 
                  FROM field_permissions 
                  WHERE table_name = :table_name 
                    AND department_id = :department_id 
                    AND is_visible = FALSE";
        $stmt = $pdo->prepare($query);
        $stmt->execute([
            ':table_name' => $tableName,
            ':department_id' => $departmentId,
        ]);
        $hiddenColumns = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // Obliczenie widocznych kolumn
        $visibleColumns = array_diff($allColumns, $hiddenColumns);

        return $visibleColumns;
    } catch (PDOException $e) {
        error_log("Błąd w funkcji getVisibleFields: " . $e->getMessage());
        return [];
    }
}

/**
 * Sprawdzanie dostępu do strony na podstawie roli
 */
function checkAccess($requiredRole = null, $tableName = null) {
    if (!isset($_SESSION['username'])) {
        header("Location: " . LOGIN_PAGE);
        exit();
    }

    // Sprawdzenie roli użytkownika
    if ($requiredRole !== null) {
        $userRole = $_SESSION['role'] ?? 'read';
        $rolesHierarchy = ['read' => 1, 'write' => 2, 'admin' => 3];

        if (!isset($rolesHierarchy[$userRole]) || $rolesHierarchy[$userRole] < $rolesHierarchy[$requiredRole]) {
            header("HTTP/1.1 403 Forbidden");
            die('Brak dostępu: Nie masz wystarczających uprawnień.');
        }
    }

    // Sprawdzenie dostępu do tabeli na podstawie department_id
    if ($tableName !== null) {
        global $pdo;
        
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM table_permissions 
            WHERE table_name = :table_name 
            AND department_id = :department_id 
            AND is_visible = 1
        ");
        $stmt->execute([
            ':table_name' => $tableName,
            ':department_id' => $_SESSION['department_id'] ?? 0
        ]);
        $hasAccess = $stmt->fetchColumn();

        if (!$hasAccess) {
            log_message('WARNING', "Użytkownik {$_SESSION['username']} próbował uzyskać dostęp do tabeli {$tableName} bez uprawnień.");
            header("HTTP/1.1 403 Forbidden");
            die('Brak dostępu do tej tabeli.');
        }
    }
}

