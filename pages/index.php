<?php
define('ACCESS_GRANTED', true);
require_once __DIR__ . '/../includes/config.php';

// Przekierowanie, jeśli użytkownik jest już zalogowany
if (isset($_SESSION['username'])) {
    log_message('INFO', "Użytkownik {$_SESSION['username']} próbował ponownie uzyskać dostęp do strony logowania.");
    header("Location: " . BASE_URL . "/pages/dashboard.php");
    exit;
}

$errorMessage = $_SESSION['login_error'] ?? '';
unset($_SESSION['login_error']); // Usuwamy komunikat po wyświetleniu

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'Nieznany IP';

    $loginResult = loginUser($username, $password);

    if ($loginResult === true) {
        header("Location: " . BASE_URL . "/pages/dashboard.php");
        exit;
    } else {
        log_message('WARNING', "Nieudana próba logowania dla użytkownika {$username} z adresu IP: {$ipAddress}. Powód: {$loginResult}");
        $errorMessage = $loginResult;
    }
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logowanie</title>
    <link rel="icon" href="<?php echo BASE_URL; ?>/images/favicon.png" type="image/x-icon">
    <link rel="shortcut icon" href="<?php echo BASE_URL; ?>/images/favicon.png" type="image/x-icon">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/index.css">
</head>
<body>
    <?php include BASE_PATH . '/templates/header.php'; ?>

    <div class="login-container animate__animated ">
        <h2>Logowanie</h2>
        <form method="post" action="">
            <input type="text" name="username" placeholder="Nazwa użytkownika" required>
            <input type="password" name="password" placeholder="Hasło" required>
            <button type="submit" class="animate__animated">Zaloguj się</button>
            <?php if (!empty($errorMessage)): ?>
                <p id="error-message" class="error"><?php echo htmlspecialchars($errorMessage); ?></p>

            <?php endif; ?>
        </form>
    </div>

    <?php include BASE_PATH . '/templates/footer.php'; ?>
</body>
</html>
