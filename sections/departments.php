<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/config.php';

// Pobranie listy działów
try {
    $departments = fetchAllDepartments($pdo);
} catch (Exception $e) {
    die('Błąd podczas pobierania działów: ' . $e->getMessage());
}

// Obsługa akcji POST (dodawanie, edycja, usuwanie)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $action = $_POST['action'] ?? '';
        $departmentId = $_POST['department_id'] ?? null;
        $departmentName = $_POST['department_name'] ?? '';

        if ($action === 'add') {
            $stmt = $pdo->prepare("INSERT INTO departments (name) VALUES (:name)");
            $stmt->execute([':name' => $departmentName]);
            $_SESSION['message'] = 'Dział został dodany.';
        } if ($action === 'edit' && $departmentId) {
            $stmt = $pdo->prepare("UPDATE departments SET name = :name WHERE id = :id");
            $stmt->execute([':name' => $departmentName, ':id' => $departmentId]);
            $_SESSION['message'] = 'Nazwa działu została zaktualizowana.';
        } if ($action === 'delete' && $departmentId) {
            $stmt = $pdo->prepare("DELETE FROM departments WHERE id = :id");
            $stmt->execute([':id' => $departmentId]);
            $_SESSION['message'] = 'Dział został usunięty.';
        }
    } catch (Exception $e) {
        $_SESSION['error'] = 'Błąd: ' . $e->getMessage();
    }

    header("Location: " . $_SERVER['PHP_SELF'] . "?section=departments");
    exit();
}
?>
<div class="container">
    <h1>Zarządzanie Działami</h1>
    <?php if (isset($_SESSION['message'])): ?>
        <div class="message success"><?php echo htmlspecialchars($_SESSION['message']); ?></div>
        <?php unset($_SESSION['message']); ?>
    <?php endif; ?>
    <?php if (isset($_SESSION['error'])): ?>
        <div class="message error"><?php echo htmlspecialchars($_SESSION['error']); ?></div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <!-- Tabela działów -->
    <h2>Lista Działów</h2>
    <button type="button" class="add-department-btn" data-action="add">Dodaj Dział</button>
    <table class="departments-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nazwa Działu</th>
                <th>Akcje</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($departments as $department): ?>
                <tr data-id="<?php echo htmlspecialchars($department['id']); ?>" data-name="<?php echo htmlspecialchars($department['name']); ?>">
                    <td><?php echo htmlspecialchars($department['id']); ?></td> <!-- Poprawka - wyświetlanie ID -->
                    <td><?php echo htmlspecialchars($department['name']); ?></td>
                    <td>
                        <button class="edit-department-btn" data-action="edit">Edytuj</button>
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="department_id" value="<?php echo htmlspecialchars($department['id']); ?>">
                            <button type="submit" class="delete-btn" onclick="return confirm('Czy na pewno usunąć dział?');">Usuń</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>


<!-- Modal do dodawania/edycji -->
<div id="departmentModal" class="modal hidden">
    <div class="modal-content">
        <span class="close-btn">&times;</span>
        <h1 id="modal-title">Dodaj/Edytuj Dział</h1>
        <form id="departmentForm" method="post">
            <input type="hidden" name="action" id="form-action">
            <input type="hidden" name="department_id" id="department-id">
            <div class="form-group">
                <label for="department-name">Nazwa Działu</label>
                <input type="text" name="department_name" id="department-name" required>
            </div>
            <button type="submit" class="submit-btn">Zapisz</button>
        </form>
    </div>
</div>

<script>
   document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('departmentModal');
    const closeModalBtn = modal.querySelector('.close-btn');
    const addDepartmentBtn = document.querySelector('.add-department-btn');
    const editDepartmentBtns = document.querySelectorAll('.edit-department-btn');
    const modalTitle = document.getElementById('modal-title');
    const formActionInput = document.getElementById('form-action');
    const departmentIdInput = document.getElementById('department-id');
    const departmentNameInput = document.getElementById('department-name');

    // Otwieranie modala dla dodawania
    addDepartmentBtn.addEventListener('click', (e) => {
        e.preventDefault();
        modalTitle.textContent = 'Dodaj Dział';
        formActionInput.value = 'add';
        departmentIdInput.value = '';
        departmentNameInput.value = '';
        modal.style.display = 'block';
    });

    // Otwieranie modala dla edycji
    editDepartmentBtns.forEach(button => {
        button.addEventListener('click', (e) => {
            e.preventDefault();
            const row = button.closest('tr');
            const departmentId = row.getAttribute('data-id');
            const departmentName = row.getAttribute('data-name');

            modalTitle.textContent = 'Edytuj Dział';
            formActionInput.value = 'edit';
            departmentIdInput.value = departmentId;
            departmentNameInput.value = departmentName;
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