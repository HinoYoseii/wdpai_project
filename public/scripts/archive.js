document.addEventListener('DOMContentLoaded', () => {
    loadCategories();
    loadTasks();
    initializeModalHandlers();
    initializeFilterHandlers();
    initializeTaskListHandlers();
});

let currentTaskId = null;
let taskToDelete = null;
let currentFilter = null;
let userCategories = [];
let tasks = [];

// Cache DOM elements
const elements = {
    get todoList() { return document.getElementById('todoList'); },
    get taskModal() { return document.getElementById('taskModal'); },
    get deleteModal() { return document.getElementById('deleteModal'); },
    get taskForm() { return document.getElementById('taskForm'); },
    get categoryFilter() { return document.getElementById('categoryFilter'); },
    get categorySelect() { return document.getElementById('taskCategory'); }
};

// Utility functions
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

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Categories
async function loadCategories() {
    try {
        const data = await fetchAPI('/getCategories');
        userCategories = data.categories || [];
        populateCategoryFilter();
        populateCategoryDropdown();
    } catch (error) {
        console.error('Error loading categories:', error);
    }
}

function populateCategoryFilter() {
    if (!elements.categoryFilter) return;

    elements.categoryFilter.innerHTML = '<option value="">Wszystkie kategorie</option>';
    
    userCategories.forEach(category => {
        const option = document.createElement('option');
        option.value = category.categoryid;
        option.textContent = category.categoryname;
        elements.categoryFilter.appendChild(option);
    });
}

function populateCategoryDropdown() {
    if (!elements.categorySelect) return;

    elements.categorySelect.innerHTML = '<option value="">Bez kategorii</option>';
    
    userCategories.forEach(category => {
        const option = document.createElement('option');
        option.value = category.categoryid;
        option.textContent = category.categoryname;
        elements.categorySelect.appendChild(option);
    });
}

// Filters
function initializeFilterHandlers() {
    if (elements.categoryFilter) {
        elements.categoryFilter.addEventListener('change', (e) => {
            currentFilter = e.target.value ? parseInt(e.target.value) : null;
            loadTasks();
        });
    }
}

// Tasks
async function loadTasks() {
    try {
        const data = await fetchAPI('/getFinishedTasks');

        tasks = data.tasks || [];
        
        // Filter by category if selected
        if (currentFilter) {
            tasks = tasks.filter(task => task.categoryid == currentFilter);
        }
        
        displayTasks();
    } catch (error) {
        elements.todoList.innerHTML = '<p class="error-message">Nie udało się załadować zadań</p>';
    }
}

function displayTasks() {
    if (tasks.length === 0) {
        elements.todoList.innerHTML = `<p class="empty-message">Brak ukończonych zadań.</p>`;
        return;
    }

    elements.todoList.innerHTML = '';

    tasks.forEach(task => {
        const listItem = createTaskElement(task);
        elements.todoList.appendChild(listItem);
    });
}

function createTaskElement(task) {
    const listItem = document.createElement('div');
    listItem.className = 'list-item';
    listItem.dataset.taskId = task.taskid;

    const deadline = task.deadlinedate || 'Brak terminu';
    const categoryName = task.categoryname || 'Bez kategorii';
    const description = task.taskdescription ? `<p class="description">${escapeHtml(task.taskdescription)}</p>` : '';

    listItem.innerHTML = `
        <div class="content">
            <p class="title">${escapeHtml(task.title)}</p>
            ${description}
            <p class="description">Kategoria: ${escapeHtml(categoryName)}</p>
            <p class="description">Termin: ${escapeHtml(deadline)}</p>
        </div>
        <div class="action-buttons">
            <button class="menu-btn" data-action="unfinish" title="Przywróć zadanie">
                <img src="public/assets/undo.png" class="list-icon" alt="ikona">
            </button>
            <button class="menu-btn" data-action="delete" title="Usuń permanentnie">
                <img src="public/assets/delete.png" class="list-icon" alt="ikona">
            </button>
        </div>
    `;

    return listItem;
}

// Event delegation for task list
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

// Modal handlers
function initializeModalHandlers() {
    const cancelDeleteBtn = document.getElementById('cancelDeleteBtn');
    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');

    cancelDeleteBtn.addEventListener('click', closeDeleteModal);
    confirmDeleteBtn.addEventListener('click', async (e) => {
        e.preventDefault();
        await deleteTask();
    });
}

// Delete modal
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
        await loadTasks();
    } catch (error) {
        // Error already handled by fetchAPI
    }
}

// Task actions
async function unfinishTask(taskId) {
    try {
        await fetchAPI('/unfinishTask', {
            method: 'POST',
            body: JSON.stringify({ taskId: parseInt(taskId) })
        });

        await loadTasks();
    } catch (error) {
        // Error already handled by fetchAPI
    }
}