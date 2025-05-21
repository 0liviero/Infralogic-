<?php
define('ACCESS_GRANTED', true);
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/config.php';

// Sprawdzenie metody żądania
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Pobranie danych z żądania
    $event = filter_input(INPUT_POST, 'event', FILTER_SANITIZE_STRING) ?? 'UNKNOWN';
    $details = filter_input(INPUT_POST, 'details', FILTER_SANITIZE_STRING) ?? 'Brak szczegółów';
    $username = $_SESSION['username'] ?? 'Nieznany użytkownik';

    // Zarejestrowanie zdarzenia
    try {
        log_message('INFO', "JS Event: {$event}, Szczegóły: {$details}, Użytkownik: {$username}");
        http_response_code(200);
        echo json_encode(['status' => 'success', 'message' => 'Zdarzenie zarejestrowane.']);
    } catch (Exception $e) {
        error_log("Błąd logowania zdarzenia: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Nie udało się zarejestrować zdarzenia.']);
    }
    exit();
}

// Obsługa nieprawidłowego żądania
http_response_code(400);
echo json_encode(['status' => 'error', 'message' => 'Nieprawidłowe żądanie.']);
