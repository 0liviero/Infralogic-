<?php
define('ACCESS_GRANTED', true);
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/config.php';

// Sprawdzenie uprawnień
if ($_SESSION['role'] !== 'admin') {
    die('Brak dostępu! Tylko administratorzy mają dostęp do tego panelu.');
}

// Określenie aktualnej sekcji panelu
$section = $_GET['section'] ?? 'admin_dashboard';

// Dozwolone sekcje
$allowedSections = ['admin_dashboard', 'users', 'permissions', 'logs', 'departments'];

if (!in_array($section, $allowedSections)) {
    die('Nieprawidłowa sekcja!');
}

// Wczytaj odpowiednią sekcję
$sectionFile = BASE_PATH . "/sections/{$section}.php";
if (!file_exists($sectionFile)) {
    die('Plik sekcji nie istnieje!');
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Administratora</title>
    <link rel="icon" href="<?php echo BASE_URL; ?>/images/favicon.png" type="image/x-icon">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/admin.css">
</head>
<body>
    <?php include BASE_PATH . '/templates/header.php'; ?>
    <?php include BASE_PATH . '/templates/navbar.php'; ?>

    <div class="admin-container">
        <aside class="admin-sidebar">
            <ul>
                <li><a href="?section=admin_dashboard" class="<?php echo $section === 'admin_dashboard' ? 'active' : ''; ?>">Panel Główny</a></li>
                <li><a href="?section=users" class="<?php echo $section === 'users' ? 'active' : ''; ?>">Zarządzanie użytkownikami</a></li>
                <li><a href="?section=permissions" class="<?php echo $section === 'permissions' ? 'active' : ''; ?>">Uprawnienia</a></li>
                <li><a href="?section=departments" class="<?php echo $section === 'departments' ? 'active' : ''; ?>">Działy</a></li>
                <li><a href="?section=logs" class="<?php echo $section === 'logs' ? 'active' : ''; ?>">Logi systemowe</a></li>
            </ul>
            <img src="<?php echo BASE_URL . '/images/it.png'; ?>" alt="Logo" class="aside-logo">
        </aside>

        <main class="admin-content">
            <?php include $sectionFile; ?>
        </main>
    </div>
    <?php include BASE_PATH . '/templates/footer.php'; ?>
</body>
</html>
