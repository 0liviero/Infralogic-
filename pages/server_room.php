<?php
define('ACCESS_GRANTED', true);
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/config.php';

// Sprawdzenie dostępu użytkownika
checkAccess('read', 'server_room');

$tableName = 'server_room'; // Nazwa tabeli
$role = $_SESSION['role'] ?? 'read';
$departmentId = $_SESSION['department_id'];

$columnNames = [
    'id' => 'ID',
    'name' => 'Nazwa',
    'ip_address' => 'Adres IP',
    'os' => 'System Operacyjny',
    'location' => 'Lokalizacja',
];

// Pobierz widoczne pola dla użytkownika
$visibleFields = getVisibleFields($pdo, $tableName, $departmentId);

// Sprawdź, czy użytkownik ma dostęp do tabeli
if (empty($visibleFields)) {
    die('Brak dostępu do tej tabeli.');
}

// Pobierz dane urządzeń
$devices = fetchRecords($pdo, $tableName, $visibleFields);

// Obsługa operacji POST
if ($_SERVER["REQUEST_METHOD"] === "POST" && in_array($role, ['admin', 'write'])) {
    $action = $_POST['action'] ?? '';
    $deviceId = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);

    if ($action === 'delete' && $deviceId) {
        if (deleteRecord($pdo, $tableName, $deviceId)) {
            $_SESSION['message'] = "Urządzenie zostało usunięte.";
        } else {
            $_SESSION['message'] = "Błąd podczas usuwania urządzenia.";
            $_SESSION['error'] = true;
        }
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }

    if ($action === 'edit' && $deviceId) {
        $updatedData = [];
        foreach ($visibleFields as $field) {
            if ($field !== 'id') {
                $updatedData[$field] = filter_var($_POST[$field] ?? '', FILTER_SANITIZE_SPECIAL_CHARS);

            }
        }
    
        $validationResult = validateDeviceData($updatedData); // Poprawiono deklarację zmiennej
        if (!$validationResult['status']) {
            $_SESSION['message'] = $validationResult['error'];
            $_SESSION['error'] = true;
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        }
    
        if (updateRecord($pdo, $tableName, $updatedData, $deviceId)) {
            $_SESSION['message'] = "Dane urządzenia zostały zaktualizowane.";
            $_SESSION['error'] = false; // Dodano, aby wiadomość była poprawnie stylizowana
        } else {
            $_SESSION['message'] = "Błąd podczas aktualizacji danych.";
            $_SESSION['error'] = true;
        }
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
    

    if ($action === 'add') {
        $newDevice = [];
        foreach ($visibleFields as $field) {
            if ($field !== 'id') {
                $newDevice[$field] = filter_var($_POST[$field] ?? '', FILTER_SANITIZE_SPECIAL_CHARS);
            }
        }

        $validationResult = validateDeviceData($newDevice);
        if (!$validationResult['status']) {
            $_SESSION['message'] = $validationResult['error'];
            $_SESSION['error'] = true;
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        }

        if (addRecord($pdo, $tableName, $newDevice)) {
            $_SESSION['message'] = "Urządzenie zostało dodane.";
        } else {
            $_SESSION['message'] = "Błąd podczas dodawania urządzenia.";
            $_SESSION['error'] = true;
        }
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="user-role" content="<?php echo htmlspecialchars($_SESSION['role']); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Serwerownia</title>
    <link rel="icon" href="<?php echo BASE_URL; ?>/images/favicon.png" type="image/x-icon">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/table.css">
    
</head>
<body>
    <?php include BASE_PATH . '/templates/header.php'; ?>
    <?php include BASE_PATH . '/templates/navbar.php'; ?>

    <div class="container">
        <h1>Lista Serwerowni</h1>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="message <?php echo isset($_SESSION['error']) ? 'error' : ''; ?>">
                <p><?php echo htmlspecialchars($_SESSION['message']); ?></p>
            </div>
            <?php unset($_SESSION['message'], $_SESSION['error']); ?>
        <?php endif; ?>


        <div class="search-container">
            <input type="text" id="searchInput" placeholder="Wyszukaj urządzenie..." data-type="server_room"/>
            <?php if (hasExportPermission($pdo, $_SESSION['username'])): ?>
            <form class="export-form" method="post" action="<?php echo BASE_URL; ?>/includes/export.php">
                <input type="hidden" name="table_name" value="printers"> 
                <label for="file_format">Wybierz format:</label>
                <select name="file_format" id="file_format">
                    <option value="xls">Excel (XLS)</option>
                    <option value="csv">CSV</option>
                    <option value="json">JSON</option>
                </select>
                <button class="export-button" type="submit" name="export">Eksportuj</button>
            </form>
            <?php endif; ?>
        </div>

        <table id="deviceTable">
            <thead>
                <tr>
                    <?php foreach ($visibleFields as $field): ?>
                        <th><?php echo htmlspecialchars($columnNames[$field] ?? ucfirst(str_replace('_', ' ', $field))); ?></th>
                    <?php endforeach; ?>
                    <?php if (in_array($role, ['admin', 'write'])): ?>
                        <th>Akcje</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
            <!-- Wiersz dodawania -->
            <?php if (in_array($role, ['admin', 'write'])): ?>
                <tr>
                    <form method="post">
                        <td>-</td>
                        <?php foreach ($visibleFields as $field): ?>
                            <?php if ($field !== 'id'): ?>
                                <td><input type="text" class="device-input" name="<?php echo htmlspecialchars($field); ?>" placeholder="Wpisz <?php echo htmlspecialchars($columnNames[$field] ?? $field); ?>" required></td>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        <td>
                            <button class="add-btn" type="submit" name="action" value="add">Dodaj</button>
                        </td>
                    </form>
                </tr>
            <?php endif; ?>
            <!-- Wiersze danych -->
            <?php foreach ($devices as $device): ?>
                <tr data-id="<?php echo $device['id']; ?>">
                    <?php foreach ($visibleFields as $field): ?>
                        <td data-column="<?php echo htmlspecialchars($field); ?>">
                            <?php echo formatAsLink($device[$field] ?? '', $field, $tableName); ?>
                        </td>
                    <?php endforeach; ?>
                    <?php if (in_array($role, ['admin', 'write'])): ?>
                        <td>
                            <button class="edit-btn">Edytuj</button>
                            <button class="save-btn hidden">Zapisz</button>
                            <button class="cancel-btn hidden">Anuluj</button>
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo htmlspecialchars($device['id']); ?>">
                                <button class="delete-btn" type="submit" onclick="return confirm('Czy na pewno chcesz usunąć to urządzenie?');">Usuń</button>
                            </form>
                        </td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php include BASE_PATH . '/templates/footer.php'; ?>
    <script src="<?php echo BASE_URL; ?>/js/search.js" defer></script>
</body>
</html>

