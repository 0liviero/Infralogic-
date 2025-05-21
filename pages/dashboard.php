<?php
define('ACCESS_GRANTED', true);
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/config.php';

// Sprawdzenie dostępu użytkownika
checkAccess();

/**
 * Lista kafelków na panelu głównym.
 */
$tiles = [
    'printers' => 'Drukarki',
    'pc' => 'Komputery',
    'du7' => 'DU7',
    'tv' => 'TV',
    'devices' => 'Urządzenia',
    'server_room' => 'Serwerownia',
    'admin_panel' => 'Administracja',
];

/**
 * Funkcja sprawdzająca, czy kafelek jest dostępny dla użytkownika.
 */
function isTileAccessible($key) {
    return isAccessible($key); // Funkcja z auth.php
}

?>
<!DOCTYPE html>
<html lang="pl">
    <head>
        <meta meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Panel główny</title>
        <link rel="icon" href="<?php echo BASE_URL; ?>/images/favicon.png" type="image/x-icon">
        <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/dashboard.css">
    </head>
    <body>
        <?php include BASE_PATH . '/templates/header.php'; ?>

        <div class="tile-container animate__animated animate__backInUp">
        <?php foreach ($tiles as $key => $label): ?>
            <?php if (isTileAccessible($key)): ?>
                <a href="<?php echo BASE_URL . "/pages/{$key}.php"; ?>" class="tile">
                    <?php echo htmlspecialchars($label); ?>
                </a>
            <?php endif; ?>
        <?php endforeach; ?>
        </div>

        <?php include BASE_PATH . '/templates/footer.php'; ?>
    </body>
</html>    
