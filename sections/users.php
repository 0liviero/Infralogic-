<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/config.php';

// Pobranie listy działów i użytkowników
try {
    $departments = fetchAllDepartments($pdo);
    $users = fetchAllUsers($pdo);
} catch (Exception $e) {
    die('Błąd podczas pobierania danych: ' . $e->getMessage());
}

// Obsługa żądań POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $action = $_POST['action'] ?? '';
        $userId = $_POST['user_id'] ?? null;
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        $role = $_POST['role'] ?? 'read';
        $departmentId = $_POST['department_id'] ?? null;

        if ($action === 'add') {
            // Dodawanie użytkownika
            if (empty($password) || $password !== $confirmPassword) {
                throw new Exception('Hasło jest wymagane i musi być zgodne!');
            }

            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, password, role, department_id, export_visible) VALUES (:username, :password, :role, :department_id, :export_visible)");
            $stmt->execute([
                ':username' => $username,
                ':password' => $hashedPassword,
                ':role' => $role,
                ':department_id' => $departmentId,
                ':export_visible' => isset($_POST['export_visible']) ? 1 : 0,
            ]);
            $_SESSION['message'] = 'Użytkownik został dodany.';

        } elseif ($action === 'edit') {
            // Edycja użytkownika
            $query = "UPDATE users SET username = :username, role = :role, department_id = :department_id, export_visible = :export_visible";
            $params = [
                ':username' => $username,
                ':role' => $role,
                ':department_id' => $departmentId,
                ':export_visible' => isset($_POST['export_visible']) ? 1 : 0,
            ];

            if (!empty($password)) {
                if ($password !== $confirmPassword) {
                    throw new Exception('Hasła muszą być zgodne!');
                }
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $query .= ", password = :password";
                $params[':password'] = $hashedPassword;
            }

            $query .= " WHERE id = :id";
            $params[':id'] = $userId;

            $stmt = $pdo->prepare($query);
            $stmt->execute($params);
            $_SESSION['message'] = 'Dane użytkownika zostały zaktualizowane.';

        } elseif ($action === 'delete') {
            $id = $_POST['id'] ?? null;

            if ($id) {
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = :id");
                $stmt->execute([':id' => $id]);
                $_SESSION['message'] = 'Użytkownik został usunięty.';
            } else {
                $_SESSION['message'] = 'Nieprawidłowe dane do usunięcia.';
                $_SESSION['error'] = true;
            }

        }

        header("Location: " . $_SERVER['PHP_SELF'] . "?section=users");
        exit();
    } catch (Exception $e) {
        $_SESSION['message'] = 'Błąd: ' . $e->getMessage();
        $_SESSION['error'] = true;
        header("Location: " . $_SERVER['PHP_SELF'] . "?section=users");
        exit();
    }
}

?>

<div id="userModal" class="modal hidden">
    <div class="modal-content">
        <span class="close-btn">&times;</span>
        <h2 id="modal-title">Dodaj/Edytuj użytkownika</h2>
        <form id="userForm" method="post">
            <input type="hidden" name="action" id="form-action" value="add">
            <input type="hidden" name="user_id" id="user-id">

            <div class="form-group">
                <label for="username">Nazwa użytkownika</label>
                <input type="text" name="username" id="username" placeholder="Wprowadź nazwę użytkownika" required>
            </div>

            <div class="form-group">
                <label for="password">Hasło</label>
                <input type="password" name="password" id="password" placeholder="Wprowadź hasło">
            </div>

            <div class="form-group">
                <label for="confirm-password">Potwierdź hasło</label>
                <input type="password" name="confirm_password" id="confirm-password" placeholder="Potwierdź hasło">
            </div>

            <div class="form-group">
                <label for="role">Rola</label>
                <select name="role" id="role" required>
                    <option value="admin">Admin</option>
                    <option value="write">Write</option>
                    <option value="read">Read</option>
                </select>
            </div>

            <div class="form-group">
                <label for="department">Dział</label>
                <select name="department_id" id="department" required>
                    <option value="" disabled selected>Wybierz dział</option>
                    <?php foreach ($departments as $department): ?>
                        <option value="<?php echo htmlspecialchars($department['id']); ?>">
                            <?php echo htmlspecialchars($department['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="export_visible">Dostęp do eksportu:</label>
                <input type="checkbox" name="export_visible" id="export_visible">
            </div>
            <button type="submit" class="submit-btn">Zapisz</button>
        </form>
    </div>
</div>

<div class="container">
    <h1>Lista Użytkowników</h1>
    <?php if (isset($_SESSION['message'])): ?>
        <div class="message <?php echo isset($_SESSION['error']) ? 'error' : ''; ?>">
            <p><?php echo htmlspecialchars($_SESSION['message']); ?></p>
        </div>
        <?php unset($_SESSION['message'], $_SESSION['error']); ?>
    <?php endif; ?>

    <div class="search-container">
        <input type="text" id="searchInput" placeholder="Wyszukaj użytkownika..." data-type="users"/>
        <button class="add-user-btn">Dodaj użytkownika</button>
        <form class="export-form" method="post" action="<?php echo BASE_URL; ?>/includes/export.php">
            <input type="hidden" name="table_name" value="users">
            <label for="file_format">Wybierz format:</label>
            <select name="file_format" id="file_format">
                <option value="xls">Excel (XLS)</option>
                <option value="csv">CSV</option>
                <option value="json">JSON</option>
            </select>
            <button class="export-button" type="submit" name="export">Eksportuj</button>
        </form>
    </div>

    <table id="deviceTable">
        <thead>
        <tr>
            <th>ID</th>
            <th>Login</th>
            <th>Rola</th>
            <th>Dział</th>
            <th>Eksport dostęp</th> <!-- Nowa kolumna -->
            <th>Akcje</th>
        </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $user): ?>
                <tr>
                    <td><?php echo htmlspecialchars($user['id']); ?></td>
                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                    <td><?php echo htmlspecialchars($user['role']); ?></td>
                    <td><?php echo htmlspecialchars($user['department_name'] ?? 'Brak przypisanego działu'); ?></td>
                    <td><?php echo $user['export_visible'] ? 'Tak' : 'Nie'; ?></td> <!-- Wyświetlanie statusu eksportu -->
                    <td>
                        <button class="edit-user-btn" 
                                data-id="<?php echo htmlspecialchars($user['id']); ?>" 
                                data-username="<?php echo htmlspecialchars($user['username']); ?>" 
                                data-role="<?php echo htmlspecialchars($user['role']); ?>" 
                                data-department-id="<?php echo htmlspecialchars($user['department_id']); ?>"
                                data-export-visible="<?php echo htmlspecialchars($user['export_visible']); ?>">
                            Edytuj
                        </button>
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?php echo htmlspecialchars($user['id']); ?>">
                            <input type="hidden" name="type" value="users">
                            <button type="submit" class="delete-btn" onclick="return confirm('Czy na pewno usunąć użytkownika?');">Usuń</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<script src="<?php echo BASE_URL; ?>/js/search.js" defer></script>
<script>
  document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('userModal');
    const addUserBtn = document.querySelector('.add-user-btn');
    const closeModalBtn = modal.querySelector('.close-btn');

    function addUserEditListeners() {
        document.querySelectorAll('.edit-user-btn').forEach(button => {
            button.addEventListener('click', () => {
                const user = {
                    id: button.dataset.id,
                    username: button.dataset.username,
                    role: button.dataset.role,
                    department_id: button.dataset.departmentId,
                    export_visible: button.dataset.exportVisible
                };
                openUserModal('edit', user);
            });
        });
    }

    function openUserModal(action, user = {}) {
        const modalTitle = document.getElementById('modal-title');
        const formAction = document.getElementById('form-action');
        const userIdField = document.getElementById('user-id');
        const usernameField = document.getElementById('username');
        const passwordField = document.getElementById('password');
        const confirmPasswordField = document.getElementById('confirm-password');
        const roleField = document.getElementById('role');
        const departmentField = document.getElementById('department');
        const exportVisibleCheckbox = document.getElementById('export_visible');

        modalTitle.textContent = action === 'add' ? 'Dodaj użytkownika' : 'Edytuj użytkownika';
        formAction.value = action;

        userIdField.value = user.id || '';
        usernameField.value = user.username || '';
        passwordField.value = '';
        confirmPasswordField.value = '';
        roleField.value = user.role || 'read';
        departmentField.value = user.department_id || '';
        exportVisibleCheckbox.checked = user.export_visible === '1';

        if (action === 'edit') {
            passwordField.removeAttribute('required');
            confirmPasswordField.removeAttribute('required');
        } else {
            passwordField.setAttribute('required', 'required');
            confirmPasswordField.setAttribute('required', 'required');
        }

        modal.classList.remove('hidden');
        modal.style.display = 'block';
    }

    addUserBtn.addEventListener('click', () => openUserModal('add'));

    closeModalBtn.addEventListener('click', () => {
        modal.classList.add('hidden');
        modal.style.display = 'none';
    });

    window.addEventListener('click', (e) => {
        if (e.target === modal) {
            modal.classList.add('hidden');
            modal.style.display = 'none';
        }
    });

    addUserEditListeners();
});

</script>
