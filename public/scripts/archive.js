document.addEventListener('DOMContentLoaded', () => {
    initializeModalHandlers();
    initializeFilterHandlers();
    initializeTaskListHandlers();
});

let taskToDelete = null;

const elements = {
    get todoList() { return document.getElementById('todoList'); },
    get deleteModal() { return document.getElementById('deleteModal'); },
    get categoryFilter() { return document.getElementById('categoryFilter'); }
};

// Fetch API z obsługą błędów
async function fetchAPI(url, options = {}) {
    try {
        const response = await fetch(url, {
            headers: {
                'Content-Type': 'application/json',
                ...options.headers
            },
            ...options
        });

        const data = await response.json();
        
        if (data.status === 'success') {
            return data;
        } else {
            throw new Error(data.message);
        }
    } catch (error) {
        console.error('API Error:', error);
        alert(error.message || 'Nie udało się wykonać operacji');
        throw error;
    }
}

// Inicjalizacja filtru dla każdej kategorii
function initializeFilterHandlers() {
    if (elements.categoryFilter) {
        elements.categoryFilter.addEventListener('change', (e) => {
            const selectedCategoryId = e.target.value;
            filterTasks(selectedCategoryId);
        });
    }
}

// Filtrowanie zadań
function filterTasks(categoryId) {
    const taskItems = elements.todoList.querySelectorAll('.list-item');
    
    taskItems.forEach(item => {
        if (!categoryId || item.dataset.categoryId === categoryId) {
            item.style.display = '';
        } else {
            item.style.display = 'none';
        }
    });

    const visibleTasks = Array.from(taskItems).filter(item => item.style.display !== 'none');
    const emptyMessage = elements.todoList.querySelector('.empty-message');
    
    if (visibleTasks.length === 0 && taskItems.length > 0) {
        if (!emptyMessage) {
            const msg = document.createElement('p');
            msg.className = 'empty-message';
            msg.textContent = 'Brak zadań w tej kategorii.';
            elements.todoList.appendChild(msg);
        }
    } else if (emptyMessage && visibleTasks.length > 0) {
        emptyMessage.remove();
    }
}

// Inicjalizacja przycisków przy zadaniach
function initializeTaskListHandlers() {
    elements.todoList.addEventListener('click', async (e) => {
        const button = e.target.closest('.menu-btn');
        if (!button) return;

        const listItem = button.closest('.list-item');
        const taskId = parseInt(listItem.dataset.taskId);
        const action = button.dataset.action;

        switch (action) {
            case 'delete':
                taskToDelete = taskId;
                elements.deleteModal.style.display = 'flex';
                break;
            case 'unfinish':
                await unfinishTask(taskId);
                break;
        }
    });
}

// Inicjalizacja obsługi delete modal
function initializeModalHandlers() {
    document.getElementById('cancelDeleteBtn').addEventListener('click', closeDeleteModal);
    document.getElementById('confirmDeleteBtn').addEventListener('click', async (e) => {
        e.preventDefault();
        await deleteTask();
    });
}

// Zamknij delete modal
function closeDeleteModal() {
    elements.deleteModal.style.display = 'none';
    taskToDelete = null;
}

// Usuń task
async function deleteTask() {
    if (!taskToDelete) return;

    try {
        await fetchAPI('/deleteTask', {
            method: 'POST',
            body: JSON.stringify({ taskId: taskToDelete })
        });

        closeDeleteModal();
        location.reload();
    } catch (error) {}
}

// Cofnij zakończenie taska
async function unfinishTask(taskId) {
    try {
        await fetchAPI('/unfinishTask', {
            method: 'POST',
            body: JSON.stringify({ taskId: parseInt(taskId) })
        });

        location.reload();
    } catch (error) {}
}