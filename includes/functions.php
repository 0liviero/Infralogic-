<?php

require_once __DIR__ . '/../includes/config.php';

/**
    * Funkcja logująca wiadomości
    * 
    * @param string $level Poziom logu (DEBUG, INFO, WARNING, ERROR)
    * @param string $message Treść wiadomości do zalogowania
    */
    function log_message($level, $message) {
        $allowed_levels = ['DEBUG', 'INFO', 'WARNING', 'ERROR'];
        $current_level_priority = array_flip($allowed_levels)[LOG_LEVEL] ?? 0;
        $message_level_priority = array_flip($allowed_levels)[$level] ?? 0;

        // Loguj tylko wiadomości o poziomie >= ustawionym poziomie logowania
        if ($message_level_priority >= $current_level_priority) {
            $timestamp = date('Y-m-d H:i:s');
            $log_message = "$timestamp [$level] $message\n";
            file_put_contents(APP_LOG_FILE, $log_message, FILE_APPEND);
        }
    }
    // Definicje logów
    define('LOG_PATH', BASE_PATH . 'logs/');
    define('APP_LOG_FILE', LOG_PATH . 'app.log');
    define('ERROR_LOG_FILE', LOG_PATH . 'error.log');
    define('LOG_LEVEL', 'DEBUG'); // Poziomy: DEBUG, INFO, WARNING, ERROR

    // Ustawienia logowania błędów PHP
    ini_set('log_errors', 1);
    ini_set('error_log', ERROR_LOG_FILE);  // Przekierowanie logów PHP do error.log
    ini_set('display_errors', 1);  // Wyświetlaj błędy (dla trybu deweloperskiego)
    ini_set('display_startup_errors', 1); 
    error_reporting(E_ALL);  // Raportuj wszystkie błędy


    function deleteRecord(PDO $pdo, string $tableName, int $id): bool {
        try {
            $query = "DELETE FROM $tableName WHERE id = :id";
            $stmt = $pdo->prepare($query);
            $result = $stmt->execute(['id' => $id]);
            if ($result) {
                log_message('INFO', "Użytkownik {$_SESSION['username']} usunął rekord ID: $id został usunięty z tabeli $tableName.");
            } else {
                log_message('WARNING', "Użytkownik {$_SESSION['username']} nie udało się usunąć rekordu ID: $id z tabeli $tableName.");
            }
            return $result;
        } catch (PDOException $e) {
            log_message('ERROR', "Błąd usuwania rekordu ID: $id z tabeli $tableName przez użytkownika {$_SESSION['username']}: " . $e->getMessage());
            return false;
        }
    }
    

    function updateRecord(PDO $pdo, string $tableName, array $data, int $id): bool {
        try {
            $columns = implode(', ', array_map(fn($key) => "$key = :$key", array_keys($data)));
            $query = "UPDATE $tableName SET $columns WHERE id = :id";
            $stmt = $pdo->prepare($query);
            $data['id'] = $id;
            $result = $stmt->execute($data);
            if ($result) {
                log_message('INFO', "Użytkownik {$_SESSION['username']} zaktualizował rekord ID: $id w tabeli $tableName.");
            } else {
                log_message('WARNING', "Użytkownik {$_SESSION['username']} nie zaktualizował rekordu ID: $id w tabeli $tableName.");
            }
            return $result;
        } catch (PDOException $e) {
            log_message('ERROR', "Błąd aktualizacji rekordu ID: $id w tabeli $tableName przez użytkownika {$_SESSION['username']}: " . $e->getMessage());
            return false;
        }
    }
    

    function addRecord(PDO $pdo, string $tableName, array $data): bool {
        try {
            $columns = implode(', ', array_keys($data));
            $placeholders = implode(', ', array_map(fn($key) => ":$key", array_keys($data)));
            $query = "INSERT INTO $tableName ($columns) VALUES ($placeholders)";
            $stmt = $pdo->prepare($query);
            $result = $stmt->execute($data);
            if ($result) {
                log_message('INFO', "Użytkownik {$_SESSION['username']} dodał pomyślnie nowy rekord do tabeli $tableName: " . json_encode($data));
            } else {
                log_message('WARNING', "Użytkownik {$_SESSION['username']} nie udało się dodać nowego rekordu do tabeli $tableName.");
            }
            return $result;
        } catch (PDOException $e) {
            log_message('ERROR', "Użytkownik {$_SESSION['username']} Błąd dodawania rekordu do tabeli $tableName: " . $e->getMessage());
            return false;
        }
    }
    

    function fetchRecords(PDO $pdo, string $tableName, array $columns): array {
        try {
            $columnsList = implode(', ', $columns);
            $query = "SELECT $columnsList FROM $tableName";
            $stmt = $pdo->query($query);
            $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return $records;
        } catch (PDOException $e) {
            log_message('ERROR', "Użytkownik {$_SESSION['username']} błąd pobierania rekordów z tabeli $tableName: " . $e->getMessage());
            return [];
        }
    }
    

/**
 * Sprawdza poprawność adresu IP.
 *
 * @param string $ip Adres IP do sprawdzenia.
 * @return bool True, jeśli adres IP jest poprawny.
 */
function isValidIP(string $ip): bool {
    return filter_var($ip, FILTER_VALIDATE_IP) !== false;
}

/**
 * Sprawdza poprawność adresu MAC.
 *
 * @param string $mac Adres MAC do sprawdzenia.
 * @return bool True, jeśli adres MAC jest poprawny.
 */
function isValidMAC(string $mac): bool {
    return (bool) preg_match('/^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$/', $mac);
}

/**
 * Waliduje dane urządzenia, w tym adresy IP i MAC, i zwraca szczegóły błędów.
 *
 * @param array $data Dane urządzenia do sprawdzenia.
 * @return array Wynik walidacji. Zwraca ['status' => true] jeśli poprawne, lub ['status' => false, 'error' => 'Opis błędu'] w przypadku błędu.
 */
function validateDeviceData(array $data): array {
    if (isset($data['ip_address']) && !isValidIP($data['ip_address'])) {
        log_message('WARNING', "Użytkownik {$_SESSION['username']} wpisał nieprawidłowy adres IP: {$data['ip_address']}");
        return ['status' => false, 'error' => 'Nieprawidłowy adres IP.'];
    }

    if (isset($data['mac_address']) && !isValidMAC($data['mac_address'])) {
        log_message('WARNING', "Użytkownik {$_SESSION['username']} wpisał nieprawidłowy adres MAC: {$data['mac_address']}");
        return ['status' => false, 'error' => 'Nieprawidłowy adres MAC.'];
    }

    return ['status' => true];
}

/**
 * Formatuje wartość kolumny jako link z odpowiednim przedrostkiem.
 *
 * @param string $value Wartość kolumny.
 * @param string $column Nazwa kolumny.
 * @param string $tableName Nazwa tabeli.
 * @return string Sformatowana wartość jako link lub zwykły tekst.
 */
function formatAsLink(string $value, string $column, string $tableName): string {
    // Wyjątek: nie formatuj kolumn w tabelach `pc` i `devices`
    if (in_array($tableName, ['pc', 'devices'])) {
        return htmlspecialchars($value);
    }

    // Formatuj linki w zależności od kolumny
    if ($column === 'ip_address') {
        $url = "http://" . ltrim($value, '/');
        return "<a href='" . htmlspecialchars($url) . "' target='_blank'>" . htmlspecialchars($value) . "</a>";
    }

    if ($column === 'vnc_address') {
        $url = "bmsvnc://" . ltrim($value, '/');
        return "<a href='" . htmlspecialchars($url) . "' target='_blank'>" . htmlspecialchars($value) . "</a>";
    }

    // Dla innych kolumn zwróć zwykły tekst
    return htmlspecialchars($value);
}

function exportToXLS(array $data, string $tableName): void {
    if (empty($data)) {
        echo 'Brak danych do eksportu.';
        return;
    }

    header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $tableName . '_data.xls"');
    header('Content-Transfer-Encoding: binary');
    echo "\xEF\xBB\xBF"; // BOM dla UTF-8

    echo '<table border="1">';
    echo '<tr>';
    foreach (array_keys($data[0]) as $columnName) {
        echo '<th>' . htmlspecialchars($columnName, ENT_QUOTES, 'UTF-8') . '</th>';
    }
    echo '</tr>';

    foreach ($data as $row) {
        echo '<tr>';
        foreach ($row as $cell) {
            echo '<td>' . htmlspecialchars($cell, ENT_QUOTES, 'UTF-8') . '</td>';
        }
        echo '</tr>';
    }

    echo '</table>';
}


function exportToCSV(array $data, string $tableName): void {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $tableName . '_data.csv"');
    echo "\xEF\xBB\xBF"; // BOM dla UTF-8
    $output = fopen('php://output', 'w');
    fputcsv($output, array_keys($data[0]));
    foreach ($data as $row) {
        fputcsv($output, $row);
    }
    fclose($output);
}

function exportToJSON(array $data, string $tableName): void {
    header('Content-Type: application/json; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $tableName . '_data.json"');
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}

/**
 * Sprawdza aktywność użytkownika i wylogowuje po określonym czasie bezczynności.
 *
 * @param int $timeout Czas bezczynności w sekundach (domyślnie 900 sekund = 15 minut).
 * @return void
 */
function checkInactivity(int $timeout = 600): void {
    global $pdo;

    if (!isset($_SESSION['user_id'])) {
        return;
    }

    // Sprawdzenie czasu ostatniej aktywności
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout)) {
        try {
            // Usunięcie `session_id` z bazy danych
            $stmt = $pdo->prepare("UPDATE users SET session_id = NULL WHERE id = :user_id");
            $stmt->execute([':user_id' => $_SESSION['user_id']]);
        } catch (PDOException $e) {
            error_log("Błąd podczas usuwania session_id: " . $e->getMessage());
        }

        // Automatyczne wylogowanie
        session_unset();
        session_destroy();
        header("Location: " . LOGIN_PAGE);
        exit();
    }

    // Aktualizacja czasu ostatniej aktywności
    $_SESSION['last_activity'] = time();
}


function countUsers(PDO $pdo): int {
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    return (int) $stmt->fetchColumn();
}
function countTables(PDO $pdo): int {
    $stmt = $pdo->query("SHOW TABLES");
    return $stmt->rowCount();
}
function fetchAllUsers(PDO $pdo): array {
    $query = "
        SELECT 
            u.id, 
            u.username, 
            u.role, 
            u.department_id, 
            u.export_visible,
            d.name AS department_name
        FROM 
            users u
        LEFT JOIN 
            departments d 
        ON 
            u.department_id = d.id
    ";
    $stmt = $pdo->query($query);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getActiveUsersFromDB(PDO $pdo, int $timeout = 10): array {
    $query = "
        SELECT 
            u.username, 
            u.role, 
            u.department_id, 
            d.name AS department_name
        FROM 
            users u
        LEFT JOIN 
            departments d 
        ON 
            u.department_id = d.id
        WHERE 
            u.last_activity >= NOW() - INTERVAL :timeout MINUTE
    ";
    $stmt = $pdo->prepare($query);
    $stmt->execute([':timeout' => $timeout]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


// Poprawiona funkcja fetchAllDepartments
function fetchAllDepartments(PDO $pdo): array {
    $stmt = $pdo->query("SELECT id, name FROM departments");
    $departments = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $departments[$row['id']] = $row; // Kluczem jest id
    }
    return $departments;
}

/**
 * Pobiera uprawnienia do tabel z bazy danych.
 */
function fetchTablePermissions(PDO $pdo): array {
    $query = "
        SELECT tp.id, tp.table_name, tp.department_id, d.name AS department_name, tp.is_visible
        FROM table_permissions tp
        LEFT JOIN departments d ON tp.department_id = d.id
    ";
    $stmt = $pdo->query($query);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


/**
 * Pobiera uprawnienia do pól z bazy danych.
 */
function fetchFieldPermissions(PDO $pdo): array {
    $query = "
        SELECT fp.id, fp.table_name, fp.field_name, fp.department_id, d.name AS department_name, fp.is_visible
        FROM field_permissions fp
        LEFT JOIN departments d ON fp.department_id = d.id
    ";
    $stmt = $pdo->query($query);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Sprawdza, czy użytkownik ma uprawnienia do eksportu danych.
 *
 * @param PDO $pdo Obiekt PDO do połączenia z bazą danych.
 * @param string $username Nazwa użytkownika.
 * @return bool True, jeśli użytkownik ma dostęp do eksportu, false w przeciwnym razie.
 */
function hasExportPermission(PDO $pdo, string $username): bool {
    $stmt = $pdo->prepare("SELECT export_visible FROM users WHERE username = :username");
    $stmt->execute([':username' => $username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    return !empty($user['export_visible']) && $user['export_visible'] == 1;
}
?>