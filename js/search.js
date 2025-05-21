document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('searchInput');
    const table = document.querySelector('#deviceTable');

    if (searchInput) {
        searchInput.addEventListener('input', function () {
            const query = this.value.trim().toLowerCase();
            const tableType = this.getAttribute('data-type');

            if (!tableType) {
                console.error('Nie podano typu tabeli w atrybucie data-type.');
                return;
            }

            fetch(`/ht/includes/search_bar.php?type=${encodeURIComponent(tableType)}&search_query=${encodeURIComponent(query)}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! Status: ${response.status}`);
                    }
                    return response.text();
                })
                .then(data => {
                    const tableBody = document.querySelector('#deviceTable tbody');
                    if (tableBody) {
                        tableBody.innerHTML = data.trim() || `<tr><td colspan="100%">Brak wyników</td></tr>`;
                    } else {
                        console.error('Nie znaleziono tabeli do aktualizacji.');
                    }
                })
                .catch(error => {
                    console.error('Błąd wyszukiwania:', error);
                });
        });
    }

    // Dodaj obsługę dynamicznego filtrowania wyników
    if (table) {
        searchInput.addEventListener('input', function () {
            const query = this.value.trim().toLowerCase();
            const rows = table.querySelectorAll('tbody tr');

            rows.forEach(row => {
                const text = row.innerText.toLowerCase();
                const exportColumn = row.cells.length > 4 ? row.cells[4]?.innerText.toLowerCase() : ''; // Sprawdzenie, czy kolumna istnieje
                
                const searchMatch = text.includes(query) || 
                                    (query === 'tak' && exportColumn === 'tak') || 
                                    (query === 'nie' && exportColumn === 'nie');

                row.style.display = searchMatch ? "" : "none";
            });
        });
    }
});
