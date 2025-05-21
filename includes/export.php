<?php

define('ACCESS_GRANTED', true);
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/config.php';

if (isset($_POST['export'], $_POST['table_name'], $_POST['file_format'])) {
    $tableName = htmlspecialchars($_POST['table_name']);
    $fileFormat = htmlspecialchars($_POST['file_format']);

    // Weryfikacja nazwy tabeli
    $allowedTables = ['printers', 'pc', 'devices', 'du7', 'tv', 'server_room','users']; // Dodaj inne tabele w razie potrzeby
    if (!in_array($tableName, $allowedTables)) {
        http_response_code(400);
        log_message('WARNING', "Próba eksportu z nieobsługiwanej tabeli: {$tableName} przez użytkownika {$_SESSION['username']}.");
        echo "Nieobsługiwana tabela.";
        exit();
    }

    try {
        // Pobranie danych z tabeli
        $query = "SELECT * FROM $tableName";
        $stmt = $pdo->query($query);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($data) {
            // Wywołanie odpowiedniej funkcji eksportu
            switch ($fileFormat) {
                case 'xls':
                    exportToXLS($data, $tableName);
                    log_message('INFO', "Użytkownik {$_SESSION['username']} wyeksportował dane z tabeli {$tableName} w formacie XLS.");
                    break;
                case 'csv':
                    exportToCSV($data, $tableName);
                    log_message('INFO', "Użytkownik {$_SESSION['username']} wyeksportował dane z tabeli {$tableName} w formacie CSV.");
                    break;
                case 'json':
                    exportToJSON($data, $tableName);
                    log_message('INFO', "Użytkownik {$_SESSION['username']} wyeksportował dane z tabeli {$tableName} w formacie JSON.");
                    break;
                default:
                    http_response_code(400);
                    log_message('WARNING', "Nieobsługiwany format pliku ({$fileFormat}) przez użytkownika {$_SESSION['username']}.");
                    echo "Nieobsługiwany format pliku.";
            }
        } else {
            log_message('INFO', "Użytkownik {$_SESSION['username']} próbował wyeksportować dane z tabeli {$tableName}, ale tabela jest pusta.");
            echo "Brak danych w tabeli.";
        }
    } catch (Exception $e) {
        log_message('ERROR', "Błąd eksportu danych z tabeli {$tableName} przez użytkownika {$_SESSION['username']}: " . $e->getMessage());
        echo "Wystąpił błąd podczas eksportu danych.";
    }
    exit();
} else {
    http_response_code(400);
    log_message('WARNING', "Nieprawidłowe żądanie eksportu danych od użytkownika {$_SESSION['username']}.");
    echo "Nieprawidłowe żądanie!";
    exit();
}
