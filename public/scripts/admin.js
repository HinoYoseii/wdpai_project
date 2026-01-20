let userToDelete = null;

const deleteModal = document.getElementById('deleteModal');
const userList = document.getElementById('userList');
const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
const cancelDeleteBtn = document.getElementById('cancelDeleteBtn');

// Click event na przycisku usunięcia, otwarcie delete modal
userList.addEventListener('click', (e) => {
    const deleteBtn = e.target.closest('[data-action="delete"]');
    if (!deleteBtn) return;

    const listItem = deleteBtn.closest('.list-item');
    userToDelete = listItem.querySelector('.description').textContent;
    deleteModal.style.display = 'flex';
});

// Zamknięcie delete modal
cancelDeleteBtn.addEventListener('click', () => {
    deleteModal.style.display = 'none';
    userToDelete = null;
});

// Usuwanie użytkownika
confirmDeleteBtn.addEventListener('click', async () => {
    if (!userToDelete) return;

    try {
        const response = await fetch('/deleteUserByEmail', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ email: userToDelete })
        });

        await response.json();

        if (response.ok) {
            location.reload();
        } else {
            alert('Nie udało się usunąć użytkownika');
        }
    } catch (error) {
        alert('Wystąpił błąd podczas usuwania użytkownika');
    }
});