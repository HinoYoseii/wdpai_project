document.addEventListener('DOMContentLoaded', () => {
    loadCategories();
    loadTasks();
    initializeModalHandlers();
    initializeFilterHandlers();
    initializeTaskListHandlers();
});

let currentTaskId = null;
let taskToDelete = null;
let currentFilter = {
    showFinished: false,
    categoryId: null
};
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
    const finishedToggle = document.getElementById('finishedToggle');
    
    if (finishedToggle) {
        finishedToggle.addEventListener('change', (e) => {
            currentFilter.showFinished = e.target.checked;
            loadTasks();
        });
    }

    if (elements.categoryFilter) {
        elements.categoryFilter.addEventListener('change', (e) => {
            currentFilter.categoryId = e.target.value ? parseInt(e.target.value) : null;
            loadTasks();
        });
    }
}

// Tasks
async function loadTasks() {
    try {
        const endpoint = currentFilter.showFinished ? '/getFinishedTasks' : '/getTasks';
        const data = await fetchAPI(endpoint);

        tasks = data.tasks || [];
        
        // Filter by category if selected
        if (currentFilter.categoryId) {
            tasks = tasks.filter(task => task.categoryid == currentFilter.categoryId);
        }
        
        displayTasks();
    } catch (error) {
        elements.todoList.innerHTML = '<p class="error-message">Nie udało się załadować zadań</p>';
    }
}

function displayTasks() {
    if (tasks.length === 0) {
        const message = currentFilter.showFinished 
            ? 'Brak ukończonych zadań.' 
            : 'Brak zadań do wyświetlenia. Dodaj nowe zadanie!';
        elements.todoList.innerHTML = `<p class="empty-message">${message}</p>`;
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

    const deadline = task.deadlinedate ? formatDeadline(task.deadlinedate) : 'Brak terminu';
    const categoryName = task.categoryname || 'Bez kategorii';
    const isPinned = task.ispinned === true || task.ispinned === 't';
    const starIcon = isPinned ? 'star_fill.png' : 'star_empty.png';
    const description = task.taskdescription ? `<p class="description">${escapeHtml(task.taskdescription)}</p>` : '';
    const priorityScore = task.priorityScore ? `<p>${escapeHtml(task.priorityScore)}</p>`: '';

    const actionButtons = currentFilter.showFinished ? `
        <button class="menu-btn" data-action="unfinish" title="Przywróć zadanie">
            <img src="public/assets/undo.png" class="list-icon" alt="ikona">
        </button>
        <button class="menu-btn" data-action="delete" title="Usuń permanentnie">
            <img src="public/assets/delete.png" class="list-icon" alt="ikona">
        </button>
    ` : `
        <button class="menu-btn" data-action="finish" title="Zakończ zadanie">
            <img src="public/assets/check.png" class="list-icon" alt="ikona">
        </button>
        <button class="menu-btn" data-action="edit" title="Edytuj">
            <img src="public/assets/edit.png" class="list-icon" alt="ikona">
        </button>
        <button class="menu-btn" data-action="delete" title="Usuń">
            <img src="public/assets/delete.png" class="list-icon" alt="ikona">
        </button>
    `;

    listItem.innerHTML = `
        <button class="menu-btn" data-action="pin" data-pinned="${isPinned}" title="${isPinned ? 'Odepnij' : 'Przypnij'}">
            <img src="public/assets/${starIcon}" class="list-icon" alt="ikona">
        </button>
        <div class="content">
            <p class="title">${escapeHtml(task.title)}</p>
            ${description}
            <p class="description">Kategoria: ${escapeHtml(categoryName)}</p>
            <p class="description">Termin: ${deadline}</p>
            <p class=description">${priorityScore}</p> 
        </div>
        <div class="action-buttons">
            ${actionButtons}
        </div>
    `;

    return listItem;
}

function formatDeadline(deadlineStr) {
    const deadline = new Date(deadlineStr);
    const now = new Date();
    const diffTime = deadline - now;
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

    if (diffDays < 0) {
        return `Spóźnione o ${Math.abs(diffDays)} dni`;
    } else if (diffDays === 0) {
        return 'Dzisiaj';
    } else if (diffDays === 1) {
        return 'Jutro';
    } else if (diffDays <= 7) {
        return `Za ${diffDays} dni`;
    } else {
        return deadline.toLocaleDateString('pl-PL');
    }
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
            case 'pin':
                const isPinned = button.dataset.pinned === 'true';
                await pinTask(taskId, !isPinned);
                break;
            case 'finish':
                await finishTask(taskId);
                break;
            case 'edit':
                editTask(taskId);
                break;
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
    const addBtn = document.getElementById('addTaskBtn');
    const addBtnMobile = document.getElementById('addTaskBtnMobile');
    const cancelBtn = document.getElementById('cancelBtn');
    const cancelDeleteBtn = document.getElementById('cancelDeleteBtn');
    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');

    addBtnMobile.addEventListener('click', () => openModal());
    addBtn.addEventListener('click', () => openModal());
    cancelBtn.addEventListener('click', closeModal);
    cancelDeleteBtn.addEventListener('click', closeDeleteModal);
    confirmDeleteBtn.addEventListener('click', async (e) => {
        e.preventDefault();
        await deleteTask();
    });

    elements.taskForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        await saveTask();
    });
}

function openModal(taskId = null) {
    const modalTitle = document.getElementById('modalTitle');
    const taskIdInput = document.getElementById('taskId');

    currentTaskId = taskId;
    
    if (taskId) {
        modalTitle.textContent = 'Edytuj zadanie';
        taskIdInput.value = taskId;
        loadTaskData(taskId);
    } else {
        modalTitle.textContent = 'Dodaj zadanie';
        taskIdInput.value = '';
        elements.taskForm.reset();
    }

    elements.taskModal.style.display = 'flex';
    document.getElementById('taskTitle').focus();
}

function closeModal() {
    elements.taskModal.style.display = 'none';
    currentTaskId = null;
}

function loadTaskData(taskId) {
    const task = tasks.find(t => t.taskid === taskId);
    
    if (task) {
        document.getElementById('taskTitle').value = task.title || '';
        document.getElementById('taskDescription').value = task.taskdescription || '';
        document.getElementById('taskCategory').value = task.categoryid || '';
        
        if (task.deadlinedate) {
            const date = new Date(task.deadlinedate);
            const localDate = new Date(date.getTime() - date.getTimezoneOffset() * 60000);
            document.getElementById('taskDeadline').value = localDate.toISOString().slice(0, 16);
        }
        
        document.getElementById('taskFun').value = task.fun || 'medium';
        document.getElementById('taskDifficulty').value = task.difficulty || 'medium';
        document.getElementById('taskImportance').value = task.importance || 'medium';
        document.getElementById('taskTime').value = task.time || 'medium';
    } else {
        console.error('Task not found in local array:', taskId);
    }
}

async function saveTask() {
    const taskId = document.getElementById('taskId').value;
    const title = document.getElementById('taskTitle').value.trim();

    if (!title) {
        showError('Tytuł zadania jest wymagany');
        return;
    }

    const url = taskId ? '/updateTask' : '/createTask';
    const payload = {
        title,
        taskDescription: document.getElementById('taskDescription').value.trim() || null,
        categoryId: document.getElementById('taskCategory').value ? parseInt(document.getElementById('taskCategory').value) : null,
        deadlineDate: document.getElementById('taskDeadline').value || null,
        fun: document.getElementById('taskFun').value,
        difficulty: document.getElementById('taskDifficulty').value,
        importance: document.getElementById('taskImportance').value,
        time: document.getElementById('taskTime').value
    };

    if (taskId) {
        payload.taskId = parseInt(taskId);
    }

    try {
        await fetchAPI(url, {
            method: 'POST',
            body: JSON.stringify(payload)
        });

        closeModal();
        await loadTasks();
    } catch (error) {
        // Error already handled by fetchAPI
    }
}

function editTask(taskId) {
    openModal(taskId);
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
async function pinTask(taskId, shouldPin) {
    try {
        await fetchAPI('/pinTask', {
            method: 'POST',
            body: JSON.stringify({ 
                taskId: parseInt(taskId),
                isPinned: shouldPin
            })
        });

        await loadTasks();
    } catch (error) {
        // Error already handled by fetchAPI
    }
}

async function finishTask(taskId) {
    try {
        const data = await fetchAPI('/finishTask', {
            method: 'POST',
            body: JSON.stringify({ taskId: parseInt(taskId) })
        });

        await loadTasks();
        if (data.message) {
            console.log(data.message);
        }
    } catch (error) {
        // Error already handled by fetchAPI
    }
}

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