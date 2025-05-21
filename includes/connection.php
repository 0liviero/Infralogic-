<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/config.php';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC); // Domyślny tryb fetch
} catch (PDOException $e) {
    error_log("Błąd połączenia z bazą danych: " . $e->getMessage()); // Loguj błąd
    die("Nie udało się połączyć z bazą danych. Skontaktuj się z administratorem."); // Bezpieczny komunikat
}
?>
