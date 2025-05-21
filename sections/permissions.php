<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/config.php';

// Pobranie uprawnień do tabel i kolumn
try {
    $tablePermissions = fetchTablePermissions($pdo);
    $fieldPermissions = fetchFieldPermissions($pdo);
    $departments = fetchAllDepartments($pdo);
} catch (Exception $e) {
    die('Błąd podczas pobierania danych: ' . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $action = $_POST['action'] ?? '';
        $permissionId = $_POST['permission_id'] ?? null;
        $type = $_POST['type'] ?? '';
        $tableName = $_POST['table_name'] ?? '';
        $fieldName = $_POST['field_name'] ?? null;
        $departmentId = $_POST['department_id'] ?? null;
        $isVisible = isset($_POST['is_visible']) ? 1 : 0;

        if ($action === 'add') {
            if ($type === 'table') {
                $stmt = $pdo->prepare("INSERT INTO table_permissions (table_name, department_id, is_visible) VALUES (:table_name, :department_id, :is_visible)");
                $stmt->execute([':table_name' => $tableName, ':department_id' => $departmentId, ':is_visible' => $isVisible]);
            } elseif ($type === 'field') {
                $stmt = $pdo->prepare("INSERT INTO field_permissions (table_name, field_name, department_id, is_visible) VALUES (:table_name, :field_name, :department_id, :is_visible)");
                $stmt->execute([':table_name' => $tableName, ':field_name' => $fieldName, ':department_id' => $departmentId, ':is_visible' => $isVisible]);
            }
            $_SESSION['message'] = 'Uprawnienie zostało dodane.';
        } elseif ($action === 'edit') {
            if ($type === 'table') {
                $stmt = $pdo->prepare("UPDATE table_permissions SET table_name = :table_name, department_id = :department_id, is_visible = :is_visible WHERE id = :id");
                $stmt->execute([':table_name' => $tableName, ':department_id' => $departmentId, ':is_visible' => $isVisible, ':id' => $permissionId]);
            } elseif ($type === 'field') {
                $stmt = $pdo->prepare("UPDATE field_permissions SET table_name = :table_name, field_name = :field_name, department_id = :department_id, is_visible = :is_visible WHERE id = :id");
                $stmt->execute([':table_name' => $tableName, ':field_name' => $fieldName, ':department_id' => $departmentId, ':is_visible' => $isVisible, ':id' => $permissionId]);
            }
            $_SESSION['message'] = 'Uprawnienie zostało zaktualizowane.';
        } elseif ($action === 'delete') {
            $id = $_POST['id'] ?? null;

            if ($id && isset($_POST['type'])) {
                if ($type === 'table') {
                    $stmt = $pdo->prepare("DELETE FROM table_permissions WHERE id = :id");
                    $stmt->execute([':id' => $id]);
                } elseif ($type === 'field') {
                    $stmt = $pdo->prepare("DELETE FROM field_permissions WHERE id = :id");
                    $stmt->execute([':id' => $id]);
                }
                $_SESSION['message'] = 'Uprawnienie zostało usunięte.';
            } else {
                $_SESSION['message'] = 'Nieprawidłowe dane do usunięcia.';
                $_SESSION['error'] = true;
            }
        }
    } catch (Exception $e) {
        $_SESSION['message'] = 'Błąd: ' . $e->getMessage();
        $_SESSION['error'] = true;
    }

    header("Location: " . $_SERVER['PHP_SELF'] . "?section=permissions");
    exit();
}
?>
    <div class="container">
    <h1>Zarządzanie Uprawnieniami</h1>
    <?php if (isset($_SESSION['message'])): ?>
        <div class="message <?php echo isset($_SESSION['error']) ? 'error' : ''; ?>">
            <p><?php echo htmlspecialchars($_SESSION['message']); ?></p>
        </div>
        <?php unset($_SESSION['message'], $_SESSION['error']); ?>
    <?php endif; ?>

    <!-- Sekcja uprawnień do tabel -->
    <section class="permissions-section">
        <h2>Uprawnienia do Tabel</h2>
        <button type="button" class="add-permission-btn" data-type="table">Dodaj Uprawnienie</button>
        <table class="permissions-table">
            <thead>
                <tr>
                    <th>Nazwa Tabeli</th>
                    <th>Dział</th>
                    <th>Widoczna</th>
                    <th>Akcje</th>
                </tr>
            </thead>
            <tbody>
    <?php foreach ($tablePermissions as $permission): ?>
        <tr data-id="<?php echo htmlspecialchars($permission['id']); ?>" data-type="table">
            <td><?php echo htmlspecialchars($permission['table_name']); ?></td>
            <td data-department-id="<?php echo htmlspecialchars($permission['department_id']); ?>">
                <?php echo htmlspecialchars($departments[$permission['department_id']]['name'] ?? 'Nieznany dział'); ?>
            </td>
            <td><?php echo $permission['is_visible'] ? 'Tak' : 'Nie'; ?></td>
            <td>
                <button class="edit-table-btn">Edytuj</button>
                <form method="post" style="display:inline;">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($permission['id']); ?>">
                    <input type="hidden" name="type" value="table">
                    <button type="submit" class="delete-btn" onclick="return confirm('Czy na pewno usunąć uprawnienia?');">Usuń</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
</tbody>

        </table>
    </section>

    <!-- Sekcja uprawnień do kolumn -->
    <section class="permissions-section">
        <h2>Uprawnienia do Kolumn</h2>
        <button type="button" class="add-permission-btn" data-type="field">Dodaj Uprawnienie</button>
        <table class="permissions-table">
            <thead>
                <tr>
                    <th>Nazwa Tabeli</th>
                    <th>Nazwa Kolumny</th>
                    <th>Dział</th>
                    <th>Widoczna</th>
                    <th>Akcje</th>
                </tr>
            </thead>
            <tbody>
    <?php foreach ($fieldPermissions as $permission): ?>
        <tr data-id="<?php echo htmlspecialchars($permission['id']); ?>" data-type="field">
            <td><?php echo htmlspecialchars($permission['table_name']); ?></td>
            <td><?php echo htmlspecialchars($permission['field_name']); ?></td>
            <td data-department-id="<?php echo htmlspecialchars($permission['department_id']); ?>">
                <?php echo htmlspecialchars($departments[$permission['department_id']]['name'] ?? 'Nieznany dział'); ?>
            </td>
            <td><?php echo $permission['is_visible'] ? 'Tak' : 'Nie'; ?></td>
            <td>
            <button class="edit-field-btn">Edytuj</button>
            <form method="post" style="display:inline;">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($permission['id']); ?>">
                    <input type="hidden" name="type" value="filed">
                    <button type="submit" class="delete-btn" onclick="return confirm('Czy na pewno usunąć uprawnienia?');">Usuń</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
</tbody>

        </table>
    </section>
</div>


<!-- Modal -->
<div id="permissionsModal" class="modal hidden">
    <div class="modal-content">
        <span class="close-btn">&times;</span>
        <h2 id="modal-title">Dodaj/Edytuj Uprawnienie</h2>
        <form id="permissionsForm" method="post">
            <input type="hidden" name="action" id="form-action">
            <input type="hidden" name="permission_id" id="permission-id">
            <input type="hidden" name="type" id="permission-type">

            <div class="form-group">
                <label for="table_name">Nazwa Tabeli</label>
                <input type="text" name="table_name" id="table_name" required>
            </div>
            <div class="form-group hidden" id="field-name-group">
                <label for="field_name">Nazwa Kolumny</label>
                <input type="text" name="field_name" id="field_name">
            </div>
            <div class="form-group">
                <label for="department_id">Dział</label>
                <select name="department_id" id="department_id" required>
                    <?php foreach ($departments as $department): ?>
                        <option value="<?php echo htmlspecialchars($department['id']); ?>">
                            <?php echo htmlspecialchars($department['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="is_visible">Widoczna</label>
                <input type="checkbox" name="is_visible" id="is_visible">
            </div>
            <button type="submit" class="submit-btn">Zapisz</button>
        </form>
    </div>
</div>


<script defer>
   document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('permissionsModal');
    const closeModalBtn = modal.querySelector('.close-btn');
    const addPermissionBtns = document.querySelectorAll('.add-permission-btn');
    const form = document.getElementById('permissionsForm');
    const fieldNameGroup = document.getElementById('field-name-group');
    const typeInput = document.getElementById('permission-type');
    const tableNameInput = document.getElementById('table_name');
    const fieldNameInput = document.getElementById('field_name');
    const departmentInput = document.getElementById('department_id');
    const isVisibleInput = document.getElementById('is_visible');
    const modalTitle = document.getElementById('modal-title');

    // Dodawanie uprawnień
    addPermissionBtns.forEach(button => {
        button.addEventListener('click', (e) => {
            e.preventDefault();
            form.reset();
            document.getElementById('form-action').value = 'add';
            typeInput.value = button.dataset.type;

            // Ustaw tytuł modala dla dodawania
            modalTitle.textContent = button.dataset.type === 'field' ? 'Dodaj Uprawnienie do Kolumny' : 'Dodaj Uprawnienie do Tabeli';

            // Pokaż/ukryj pole "Nazwa Kolumny" w zależności od typu
            if (button.dataset.type === 'field') {
                fieldNameGroup.style.display = '';
            } else {
                fieldNameGroup.style.display = 'none';
            }

            modal.style.display = 'block';
        });
    });

    // Edycja uprawnień
    document.querySelectorAll('.edit-table-btn, .edit-field-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const row = e.target.closest('tr');
            const type = row.dataset.type;

            form.reset();
            document.getElementById('form-action').value = 'edit';
            document.getElementById('permission-id').value = row.dataset.id;
            typeInput.value = type;

            // Ustaw tytuł modala dla edycji
            modalTitle.textContent = type === 'field' ? 'Edytuj Uprawnienie do Kolumny' : 'Edytuj Uprawnienie do Tabeli';

            // Wypełnij pola edycji
            tableNameInput.value = row.cells[0].textContent.trim();
            departmentInput.value = row.cells[1].dataset.departmentId;
            isVisibleInput.checked = row.cells[2].textContent.trim() === 'Tak';

            if (type === 'field') {
                fieldNameGroup.style.display = '';
                fieldNameInput.value = row.cells[1].textContent.trim(); // Nazwa kolumny
            } else {
                fieldNameGroup.style.display = 'none';
                fieldNameInput.value = ''; // Wyczyszczenie pola kolumny
            }

            modal.style.display = 'block';
        });
    });

    // Zamknięcie modala
    closeModalBtn.addEventListener('click', () => {
        modal.style.display = 'none';
    });

    // Zamykanie po kliknięciu w tło
    window.addEventListener('click', (e) => {
        if (e.target === modal) {
            modal.style.display = 'none';
        }
    });
});
</script>