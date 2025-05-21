document.addEventListener('DOMContentLoaded', () => {
    const table = document.querySelector('#deviceTable');

    if (!table) return;

    // Funkcja do logowania zdarzeń na serwerze
    const logEvent = (event, details) => {
        fetch('/ht/includes/log_event.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({ event, details }),
        }).catch(err => console.error('Błąd logowania zdarzenia:', err));
    };

    table.addEventListener('click', (e) => {
        const target = e.target;
        const row = target.closest('tr');

        if (!row) return;

        // Pobranie informacji o roli użytkownika
        const userRole = document.querySelector('meta[name="user-role"]').getAttribute('content');

        // Obsługa przycisku "Edytuj"
        if (target.classList.contains('edit-btn')) {
            if (!['admin', 'write'].includes(userRole)) {
                console.error('Brak uprawnień do edycji.');
                logEvent('UNAUTHORIZED_EDIT', `Próba edycji rekordu ID: ${row.dataset.id}`);
                return;
            }

            logEvent('EDIT_CLICKED', `Rekord ID: ${row.dataset.id}`);
            row.querySelectorAll('td[data-column]').forEach(td => {
                const column = td.dataset.column;
                if (column !== 'id') {
                    const value = td.textContent.trim();
                    td.innerHTML = `<input type="text" name="${column}" value="${value}">`;
                }
            });
            toggleButtons(row, true);
        }

        // Obsługa przycisku "Usuń"
        if (target.classList.contains('delete-btn')) {
            e.stopPropagation(); // Zatrzymuje propagację zdarzenia
            if (!['admin', 'write'].includes(userRole)) {
                console.error('Brak uprawnień do usuwania.');
                logEvent('UNAUTHORIZED_DELETE', `Próba usunięcia rekordu ID: ${row.dataset.id}`);
                return;
            }
        
            const confirmDelete = confirm('Czy na pewno chcesz usunąć ten rekord?');
            if (confirmDelete) {
                logEvent('DELETE_CONFIRMED', `Rekord ID: ${row.dataset.id}`);
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('id', row.dataset.id);
        
                fetch(window.location.href, {
                    method: 'POST',
                    body: formData,
                }).then(() => location.reload());
            }
        }

        // Obsługa przycisków "Zapisz" i "Anuluj"
        if (target.classList.contains('save-btn')) {
            logEvent('SAVE_CLICKED', `Rekord ID: ${row.dataset.id}`);
            const formData = new FormData();
            formData.append('action', 'edit');
            formData.append('id', row.dataset.id);

            row.querySelectorAll('td[data-column] input').forEach(input => {
                formData.append(input.name, input.value);
            });

            fetch(window.location.href, {
                method: 'POST',
                body: formData,
            }).then(() => location.reload());
        }

        if (target.classList.contains('cancel-btn')) {
            logEvent('CANCEL_CLICKED', `Anulowano edycję rekordu ID: ${row.dataset.id}`);
            location.reload();
        }
    });

    function toggleButtons(row, editing) {
        row.querySelector('.edit-btn').classList.toggle('hidden', editing);
        row.querySelector('.delete-btn').classList.toggle('hidden', editing);
        row.querySelector('.save-btn').classList.toggle('hidden', !editing);
        row.querySelector('.cancel-btn').classList.toggle('hidden', !editing);
    }
});


// Kod przycisk Dark/Light mode-------------------------------------------------------------------
const themeSwitch = document.getElementById('theme-switch');
const currentTheme = localStorage.getItem('theme') || 'light-mode';

document.body.classList.add(currentTheme);

themeSwitch.addEventListener('click', () => {
    document.body.classList.toggle('dark-mode');
    document.body.classList.toggle('light-mode');

    const newTheme = document.body.classList.contains('dark-mode') ? 'dark-mode' : 'light-mode';
    localStorage.setItem('theme', newTheme);
});
