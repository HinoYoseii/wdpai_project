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
            throw new Error(data.message || 'Błąd operacji');
        }
    } catch (error) {
        console.error('API Error:', error);
        showError(error.message || 'Nie udało się wykonać operacji');
        throw error;
    }
}

function showError(message) {
    alert(message);
}

function initializeFilterHandlers() {
    if (elements.categoryFilter) {
        elements.categoryFilter.addEventListener('change', (e) => {
            const selectedCategoryId = e.target.value;
            filterTasks(selectedCategoryId);
        });
    }
}

function filterTasks(categoryId) {
    const taskItems = elements.todoList.querySelectorAll('.list-item');
    
    taskItems.forEach(item => {
        if (!categoryId || item.dataset.categoryId === categoryId) {
            item.style.display = '';
        } else {
            item.style.display = 'none';
        }
    });

    // Check if any tasks are visible
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

function initializeTaskListHandlers() {
    elements.todoList.addEventListener('click', async (e) => {
        const button = e.target.closest('.menu-btn');
        if (!button) return;

        const listItem = button.closest('.list-item');
        const taskId = parseInt(listItem.dataset.taskId);
        const action = button.dataset.action;

        switch (action) {
            case 'delete':
                confirmDeleteTask(taskId);
                break;
            case 'unfinish':
                await unfinishTask(taskId);
                break;
        }
    });
}

function initializeModalHandlers() {
    const cancelDeleteBtn = document.getElementById('cancelDeleteBtn');
    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');

    cancelDeleteBtn.addEventListener('click', closeDeleteModal);
    confirmDeleteBtn.addEventListener('click', async (e) => {
        e.preventDefault();
        await deleteTask();
    });
}

function confirmDeleteTask(taskId) {
    taskToDelete = taskId;
    elements.deleteModal.style.display = 'flex';
}

function closeDeleteModal() {
    elements.deleteModal.style.display = 'none';
    taskToDelete = null;
}

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

async function unfinishTask(taskId) {
    try {
        await fetchAPI('/unfinishTask', {
            method: 'POST',
            body: JSON.stringify({ taskId: parseInt(taskId) })
        });

        location.reload();
    } catch (error) {}
}