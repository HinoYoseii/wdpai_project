let userToDelete = null;

const deleteModal = document.getElementById('deleteModal');
const userList = document.getElementById('userList');
const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
const cancelDeleteBtn = document.getElementById('cancelDeleteBtn');

userList.addEventListener('click', (e) => {
    const deleteBtn = e.target.closest('[data-action="delete"]');
    if (!deleteBtn) return;

    const listItem = deleteBtn.closest('.list-item');
    const email = listItem.querySelector('.description').textContent;
    
    confirmDeleteUser(email);
});

function confirmDeleteUser(email) {
    userToDelete = email;
    deleteModal.style.display = 'flex';
}

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

        const data = await response.json();

        if (response.ok) {
            location.reload();
        } else {
            alert('Błąd: ' + (data.message || 'Nie udało się usunąć użytkownika'));
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Wystąpił błąd podczas usuwania użytkownika');
    }
});

cancelDeleteBtn.addEventListener('click', () => {
    deleteModal.style.display = 'none';
    userToDelete = null;
});