<?php
    // Ścieżki i URL
    define('BASE_PATH', __DIR__ . '/../');  
    define('BASE_URL', 'http://192.168.68.103/');  

 
    
    // Strona logowania
    define('LOGIN_PAGE', BASE_URL . 'pages/index.php'); 

    // Zabezpieczenie przed nieautoryzowanym dostępem
    if (!defined('ACCESS_GRANTED')) {
       die('Brak dostępu!');
    }
    
    date_default_timezone_set('Europe/Warsaw');


    // Konfiguracja bazy danych
    define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
    define('DB_USER', getenv('DB_USER') ?: 'root');
    define('DB_PASS', getenv('DB_PASS') ?: 'Verbicom@2025');
    define('DB_NAME', getenv('DB_NAME') ?: 'htportal');

    // Ładowanie plików
    require_once BASE_PATH . '/includes/connection.php'; 
    require_once BASE_PATH . '/includes/auth.php';       
    require_once BASE_PATH . '/includes/functions.php';

    // Rozpoczęcie sesji
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (isset($_SESSION['user_id'])) {
        // Pobierz dane z bazy
        $userId = $_SESSION['user_id'];
        try {
            $stmt = $pdo->prepare("SELECT session_expires_at FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $sessionExpiresAt = $stmt->fetchColumn();
    
            if ($sessionExpiresAt && strtotime($sessionExpiresAt) < time()) {
                // Sesja wygasła – wyloguj użytkownika
                session_unset();
                session_destroy();
                header("Location: " . LOGIN_PAGE . "?timeout=1");
                exit();
            }
        } catch (PDOException $e) {
            error_log("Błąd walidacji sesji: " . $e->getMessage());
        }
    }
    

    // Sprawdzenie bezczynności użytkownika (15 minut)
    checkInactivity(600);
    
    if (isset($_SESSION['user_id'])) {
        static $lastUpdate = 0;
        if (time() - $lastUpdate > 60) { // Aktualizuj maksymalnie raz na minutę
            $stmt = $pdo->prepare("UPDATE users SET last_activity = NOW() WHERE id = :user_id");
            $stmt->execute([':user_id' => $_SESSION['user_id']]);
            $lastUpdate = time();
        }
    }
    
    

?>
