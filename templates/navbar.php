<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/config.php';

$userDetails = getUserDetails();
$role = $userDetails['role'];
?>
<nav>
    <?php if (isAccessible('printers')): ?>
        <a href="<?php echo BASE_URL . '/pages/printers.php'; ?>">Drukarki</a>
    <?php endif; ?>
    <?php if (isAccessible('pc')): ?>
        <a href="<?php echo BASE_URL . '/pages/pc.php'; ?>">PC</a>
    <?php endif; ?>
    <?php if (isAccessible('du7')): ?>
        <a href="<?php echo BASE_URL . '/pages/du7.php'; ?>">DU7</a>
    <?php endif; ?>
    <?php if (isAccessible('tv')): ?>
        <a href="<?php echo BASE_URL . '/pages/tv.php'; ?>">TV</a>
    <?php endif; ?>
    <?php if (isAccessible('devices')): ?>
        <a href="<?php echo BASE_URL . '/pages/devices.php'; ?>">Urządzenia</a>
    <?php endif; ?>
    <?php if (isAccessible('server_room')): ?>
        <a href="<?php echo BASE_URL . '/pages/server_room.php'; ?>">Serwerownia</a>
    <?php endif; ?>
    <?php if ($role === 'admin'): ?>
        <a href="<?php echo BASE_URL . '/pages/admin_panel.php'; ?>">Administracja</a>
    <?php endif; ?>
</nav>