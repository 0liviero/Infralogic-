<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/config.php';

$logDir = $_SERVER['DOCUMENT_ROOT'] . '/logs';
$errorLogFile = $logDir . '/error.log';
$appLogFile = $logDir . '/app.log';

// Funkcja do odczytu plików logów z diagnostyką
function readLogFile(string $filePath): array {
    if (!file_exists($filePath)) {
        return ["Plik $filePath nie istnieje."];
    }
    if (!is_readable($filePath)) {
        return ["Plik $filePath nie ma praw do odczytu."];
    }
    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return ["Błąd podczas odczytu pliku $filePath."];
    }
    return array_map(fn($line) => htmlspecialchars($line), $lines);
}

// Odczyt logów
$errorLogs = readLogFile($errorLogFile);
$appLogs = readLogFile($appLogFile);

// Obsługa żądania czyszczenia logów
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $logType = $_POST['log_type'] ?? '';
    try {
        if ($logType === 'error') {
            file_put_contents($errorLogFile, '');
            $_SESSION['message'] = 'Logi błędów zostały wyczyszczone.';
        } elseif ($logType === 'app') {
            file_put_contents($appLogFile, '');
            $_SESSION['message'] = 'Logi aplikacji zostały wyczyszczone.';
        }
    } catch (Exception $e) {
        $_SESSION['message'] = 'Błąd podczas czyszczenia logów: ' . $e->getMessage();
        $_SESSION['error'] = true;
    }
    header("Location: " . $_SERVER['PHP_SELF'] . "?section=logs");
    exit();
}
?>
<div class="container">
    <h1>Logi Systemowe</h1>
    <?php if (isset($_SESSION['message'])): ?>
        <div class="message <?php echo isset($_SESSION['error']) ? 'error' : 'success'; ?>">
            <p><?php echo htmlspecialchars($_SESSION['message']); ?></p>
        </div>
        <?php unset($_SESSION['message'], $_SESSION['error']); ?>
    <?php endif; ?>

    <!-- Przyciski akcji -->
    <div class="action-buttons">
        <form method="post" style="display:inline;">
            <input type="hidden" name="log_type" value="error">
            <button type="submit" class="clear-logs-btn">Wyczyść logi błędów</button>
        </form>
        <form method="post" style="display:inline;">
            <input type="hidden" name="log_type" value="app">
            <button type="submit" class="clear-logs-btn">Wyczyść logi aplikacji</button>
        </form>
    </div>

    <!-- Logi błędów -->
    <section>
        <h2>Logi Błędów</h2>
        <div class="log-box">
            <?php if (!empty($errorLogs)): ?>
                <?php foreach ($errorLogs as $line): ?>
                    <pre><?php echo $line; ?></pre>
                <?php endforeach; ?>
            <?php else: ?>
                <p>Brak logów błędów do wyświetlenia.</p>
            <?php endif; ?>
        </div>
    </section>

    <!-- Logi aplikacji -->
    <section>
        <h2>Logi Aplikacji</h2>
        <div class="log-box">
            <?php if (!empty($appLogs)): ?>
                <?php foreach ($appLogs as $line): ?>
                    <pre><?php echo $line; ?></pre>
                <?php endforeach; ?>
            <?php else: ?>
                <p>Brak logów aplikacji do wyświetlenia.</p>
            <?php endif; ?>
        </div>
    </section>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const logTypeFilter = document.getElementById('logTypeFilter');
        const clearLogsBtn = document.getElementById('clearLogsBtn');
        const appLogsBox = document.getElementById('appLogsBox');
        const errorLogsBox = document.getElementById('errorLogsBox');

        // Filtrowanie logów
        logTypeFilter.addEventListener('change', () => {
            const selectedType = logTypeFilter.value;

            if (selectedType === 'app') {
                appLogsBox.classList.remove('hidden');
                errorLogsBox.classList.add('hidden');
                clearLogsBtn.dataset.type = 'app';
                clearLogsBtn.classList.remove('hidden');
            } else {
                appLogsBox.classList.add('hidden');
                errorLogsBox.classList.remove('hidden');
                clearLogsBtn.dataset.type = 'error';
                clearLogsBtn.classList.remove('hidden');
            }
        });

        // Czyszczenie logów
        clearLogsBtn.addEventListener('click', () => {
            const logType = clearLogsBtn.dataset.type;
            if (confirm(`Czy na pewno chcesz wyczyścić logi ${logType === 'app' ? 'aplikacji' : 'błędów'}?`)) {
                fetch(`/ht/includes/clear_logs.php?type=${logType}`)
                    .then(() => location.reload())
                    .catch(err => console.error('Błąd podczas czyszczenia logów:', err));
            }
        });

        // Domyślne wyświetlenie logów aplikacji
        logTypeFilter.value = 'app';
        appLogsBox.classList.remove('hidden');
        clearLogsBtn.dataset.type = 'app';
        clearLogsBtn.classList.remove('hidden');
    });
</script>
