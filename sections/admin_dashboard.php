<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/config.php';

// Pobranie liczby użytkowników, tabel i aktywnych użytkowników
try {
    $userCount = countUsers($pdo);
    $tableCount = countTables($pdo);
    $activeUsers = getActiveUsersFromDB($pdo, 10); // Aktywni użytkownicy w ciągu ostatnich 10 minut
    $departments = fetchAllDepartments($pdo); // Pobranie wszystkich działów
} catch (Exception $e) {
    die('Błąd podczas pobierania danych: ' . $e->getMessage());
}
?>

<div class="container">
    <h1>Panel Administratora</h1>

    <!-- Sekcja Statystyk -->
    <div class="stats-section">
        <div class="stat-box">
            <h3>Liczba użytkowników</h3>
            <p><?php echo $userCount; ?></p>
        </div>
        <div class="stat-box">
            <h3>Liczba tabel</h3>
            <p><?php echo $tableCount; ?></p>
        </div>
        <div class="stat-box">
            <h3>Aktywni użytkownicy</h3>
            <p><?php echo count($activeUsers); ?></p>
        </div>
        <div class="stat-box">
            <h3>Ostatnie logi</h3>
            <a href="?section=logs" class="view-logs-btn">
              <img src="<?php echo BASE_URL . '/images/event-log.png'; ?>" class="log-logo">
            </a>
        </div>
    </div>

    <!-- Lista Aktywnych Użytkowników -->
    <h2>Lista aktywnych użytkowników</h2>
    <div class="filter-section">
        <label for="roleFilter">Filtruj według roli:</label>
        <select id="roleFilter">
            <option value="">Wszystkie</option>
            <option value="admin">Admin</option>
            <option value="write">Write</option>
            <option value="read">Read</option>
        </select>
        <label for="departmentFilter">Filtruj według działu:</label>
        <select id="departmentFilter">
            <option value="">Wszystkie</option>
            <?php foreach ($departments as $department): ?>
                <option value="<?php echo htmlspecialchars($department['name']); ?>">
                    <?php echo htmlspecialchars($department['name']); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <table class="users-table">
        <thead>
            <tr>
                <th>Nazwa użytkownika</th>
                <th>Rola</th>
                <th>Dział</th>
            </tr>
        </thead>
        <tbody id="activeUsersBody">
    <?php if (!empty($activeUsers)): ?>
        <?php foreach ($activeUsers as $user): ?>
            <tr data-role="<?php echo htmlspecialchars($user['role']); ?>" data-department="<?php echo htmlspecialchars($user['department_name'] ?? 'Nieznany dział'); ?>">
                <td><?php echo htmlspecialchars($user['username']); ?></td>
                <td><?php echo htmlspecialchars($user['role']); ?></td>
                <td><?php echo htmlspecialchars($user['department_name'] ?? 'Nieznany dział'); ?></td>
            </tr>
        <?php endforeach; ?>
    <?php else: ?>
        <tr id="noUsersMessage">
            <td colspan="3">Brak aktywnych użytkowników</td>
        </tr>
    <?php endif; ?>
</tbody>
    </table>

    <!-- Przyciski akcji -->
    <div class="action-buttons">
        <button class="refresh-btn" onclick="location.reload();">Odśwież dane</button>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const roleFilter = document.getElementById('roleFilter');
    const departmentFilter = document.getElementById('departmentFilter');
    const activeUsersBody = document.getElementById('activeUsersBody');

    if (!activeUsersBody) {
        console.error('Nie znaleziono elementu activeUsersBody.');
        return;
    }

    function filterUsers() {
        const selectedRole = roleFilter.value.toLowerCase();
        const selectedDepartment = departmentFilter.value.toLowerCase();
        const rows = activeUsersBody.querySelectorAll('tr[data-role]');

        let hasResults = false;

        rows.forEach(row => {
            const role = row.getAttribute('data-role').toLowerCase();
            const department = row.getAttribute('data-department').toLowerCase();

            if (
                (selectedRole === '' || role === selectedRole) &&
                (selectedDepartment === '' || department === selectedDepartment)
            ) {
                row.style.display = '';
                hasResults = true;
            } else {
                row.style.display = 'none';
            }
        });

        const noUsersRow = document.getElementById('noUsersMessage');
        if (noUsersRow) {
            noUsersRow.style.display = hasResults ? 'none' : '';
            noUsersRow.querySelector('td').textContent = 'Brak aktywnych użytkowników dla wybranych filtrów.';
        }
    }

    roleFilter.addEventListener('change', filterUsers);
    departmentFilter.addEventListener('change', filterUsers);
});

</script>
