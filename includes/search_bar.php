<?php
define('ACCESS_GRANTED', true);
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/config.php';

$type = $_GET['type'] ?? '';
$searchQuery = $_GET['search_query'] ?? '';

if (!$type) {
    http_response_code(400);
    log_message('WARNING', "Brak typu tabeli w żądaniu od użytkownika {$_SESSION['username']}.");
    echo "Nie podano typu tabeli.";
    exit();
}

// Konfiguracja tabel
$tableConfigs = [
    'printers' => ['table' => 'printers', 'columns' => ['id', 'name', 'ip_address', 'mac_address', 'location']],
    'pc' => ['table' => 'pc', 'columns' => ['id', 'name', 'model', 'ip_address', 'mac_address', 'holder']],
    'du7' => ['table' => 'du7', 'columns' => ['id', 'name', 'ip_address', 'vnc_address', 'location']],
    'tv' => ['table' => 'tv', 'columns' => ['id', 'name', 'vnc_address', 'mac_address']],
    'devices' => ['table' => 'devices', 'columns' => ['id', 'name', 'ip_address', 'mac_address', 'holder']],
    'server_room' => ['table' => 'server_room', 'columns' => ['id', 'name', 'ip_address', 'os', 'location']],
    'users' => ['table' => 'users', 'columns' => ['id', 'username', 'role', 'department_id', 'export_visible']],
];

// Walidacja typu tabeli
if (!array_key_exists($type, $tableConfigs)) {
    log_message('WARNING', "Nieobsługiwany typ tabeli ({$type}) w żądaniu od użytkownika {$_SESSION['username']}.");
    http_response_code(400);
    echo "Nieobsługiwany typ tabeli.";
    exit();
}

$tableConfig = $tableConfigs[$type];
$tableName = $tableConfig['table'];
$columns = $tableConfig['columns'];

// Funkcja pobierająca widoczne kolumny
function getVisibleColumns(PDO $pdo, string $tableName, int $departmentId, string $role, array $columns): array {
    if ($role === 'admin') {
        return $columns; // Admin widzi wszystkie kolumny
    }

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

    return array_diff($columns, $hiddenColumns);
}

// Funkcja generująca wiersz tabeli
function generateTableRow(array $row, array $columns, string $role, string $tableName): string {
    $output = "<tr data-id='" . htmlspecialchars($row['id']) . "'>";
    foreach ($columns as $col) {
        $value = $row[$col] ?? '';
        if ($col === 'export_visible') {
            $value = ($value == 1) ? 'Tak' : 'Nie';
        }
        $output .= "<td data-column='" . htmlspecialchars($col) . "'>" . formatAsLink($value, $col, $tableName) . "</td>";
    }

    if (in_array($role, ['admin', 'write'])) {
        $editClass = ($tableName === 'users') ? 'edit-user-btn' : 'edit-btn';
        $output .= "<td>
                        <button class='$editClass'>Edytuj</button>
                        <button class='save-btn hidden'>Zapisz</button>
                        <button class='cancel-btn hidden'>Anuluj</button>
                        <button class='delete-btn'>Usuń</button>
                    </td>";
    }

    $output .= "</tr>";
    return $output;
}

// Pobranie roli i działu z sesji
$role = $_SESSION['role'] ?? 'read';
$departmentId = $_SESSION['department_id'] ?? 0;

// Pobranie widocznych kolumn
$visibleColumns = getVisibleColumns($pdo, $tableName, $departmentId, $role, $columns);

// Jeśli nie ma widocznych kolumn, wyświetl komunikat i zakończ
if (empty($visibleColumns)) {
    echo "<tr><td colspan='1'>Brak dostępnych danych.</td></tr>";
    exit();
}

// Zapytanie SQL
if ($tableName === 'users') {
    // Upewnij się, że kolumny zawierają export_visible i department_name
    if (!in_array('export_visible', $visibleColumns)) {
        $visibleColumns[] = 'export_visible';
    }
    if (!in_array('department_name', $visibleColumns)) {
        $visibleColumns[] = 'department_name';
    }

    // Ustaw kolumny do SELECT-a z aliasem
    $columnsWithJoin = array_map(function ($col) {
        return $col === 'department_name' ? 'd.name AS department_name' : "u.`$col`";
    }, $visibleColumns);

    $searchColumns = array_map(function ($col) {
        return $col === 'department_name' ? 'd.name' : "u.`$col`";
    }, $visibleColumns);

    $query = sprintf(
        "SELECT %s FROM users u LEFT JOIN departments d ON u.department_id = d.id WHERE CONCAT_WS(' ', %s) LIKE :query",
        implode(', ', $columnsWithJoin),
        implode(', ', $searchColumns)
    );
} else {
    // Standardowe zapytanie bez JOIN-ów
    $query = sprintf(
        "SELECT %s FROM `%s` WHERE CONCAT_WS(' ', %s) LIKE :query",
        implode(', ', array_map(fn($col) => "`$col`", $visibleColumns)),
        $tableName,
        implode(', ', array_map(fn($col) => "`$col`", $visibleColumns))
    );
}

// Wykonanie zapytania
$stmt = $pdo->prepare($query);
$stmt->execute([':query' => "%$searchQuery%"]);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Generowanie wyników
if ($results) {
    foreach ($results as $row) {
        echo generateTableRow($row, $visibleColumns, $role, $tableName);
    }
} else {
    echo "<tr><td colspan='" . (count($visibleColumns) + 1) . "'>Brak wyników</td></tr>";
}