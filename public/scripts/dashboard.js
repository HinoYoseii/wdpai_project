document.addEventListener('DOMContentLoaded', () => {
    initializeModalHandlers();
    initializeFilterHandlers();
    initializeTaskListHandlers();
    formatAllDeadlines();
});

let currentTaskId = null;
let taskToDelete = null;
let allTasks = [];

const elements = {
    get todoList() { return document.getElementById('todoList'); },
    get taskModal() { return document.getElementById('taskModal'); },
    get deleteModal() { return document.getElementById('deleteModal'); },
    get taskForm() { return document.getElementById('taskForm'); },
    get categoryFilter() { return document.getElementById('categoryFilter'); }
};

// Store initial tasks data
document.addEventListener('DOMContentLoaded', () => {
    const taskItems = document.querySelectorAll('.list-item[data-task-id]');
    allTasks = Array.from(taskItems).map(item => {
        return {
            taskid: parseInt(item.dataset.taskId),
            categoryid: item.dataset.categoryId ? parseInt(item.dataset.categoryId) : null,
            title: item.querySelector('.title').textContent,
            taskdescription: item.querySelector('.description')?.textContent || null,
            deadlinedate: item.dataset.deadline || null,
            fun: item.dataset.fun,
            difficulty: item.dataset.difficulty,
            importance: item.dataset.importance,
            time: item.dataset.time
        };
    });
});

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

function formatAllDeadlines() {
    document.querySelectorAll('.deadline-value').forEach(el => {
        const deadlineStr = el.textContent;
        if (deadlineStr && deadlineStr !== 'Brak terminu') {
            el.textContent = formatDeadline(deadlineStr);
        }
    });
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

    const visibleTasks = Array.from(taskItems).filter(item => item.style.display !== 'none');
    const emptyMessage = elements.todoList.querySelector('.empty-message');
    
    if (visibleTasks.length === 0) {
        if (!emptyMessage) {
            const msg = document.createElement('p');
            msg.className = 'empty-message';
            msg.textContent = 'Brak zadań w tej kategorii.';
            elements.todoList.appendChild(msg);
        }
    } else if (emptyMessage) {
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
            case 'pin':
                const isPinned = button.dataset.pinned === 'true';
                await pinTask(taskId, !isPinned);
                break;
            case 'finish':
                await finishTask(taskId);
                break;
            case 'edit':
                editTask(taskId, listItem);
                break;
            case 'delete':
                confirmDeleteTask(taskId);
                break;
        }
    });
}

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

function openModal(taskData = null) {
    const modalTitle = document.getElementById('modalTitle');
    const taskIdInput = document.getElementById('taskId');

    currentTaskId = taskData ? taskData.taskid : null;
    
    if (taskData) {
        console.log(taskData);
        modalTitle.textContent = 'Edytuj zadanie';
        taskIdInput.value = taskData.taskid;
        document.getElementById('taskTitle').value = taskData.title || '';
        document.getElementById('taskDescription').value = taskData.taskdescription || '';
        document.getElementById('taskCategory').value = taskData.categoryid || '';
        
        if (taskData.deadlinedate) {
            const date = new Date(taskData.deadlinedate);
            const localDate = new Date(date.getTime() - date.getTimezoneOffset() * 60000);
            document.getElementById('taskDeadline').value = localDate.toISOString().slice(0, 16);
        }
        document.getElementById('taskFun').value = taskData.fun || '2';
        document.getElementById('taskDifficulty').value = taskData.difficulty || '2';
        document.getElementById('taskImportance').value = taskData.importance || '2';
        document.getElementById('taskTime').value = taskData.time || '2';
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

function editTask(taskId, listItem) {
    const taskData = {
        taskid: taskId,
        title: listItem.querySelector('.title').textContent,
        taskdescription: listItem.querySelectorAll('.description')[0]?.textContent || '',
        categoryid: listItem.dataset.categoryId || '',
        deadlinedate: listItem.dataset.deadline || null,
        fun: listItem.dataset.fun,
        difficulty: listItem.dataset.difficulty,
        importance: listItem.dataset.importance,
        time: listItem.dataset.time
    };
    
    openModal(taskData);
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
        location.reload();
    } catch (error) {}
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

async function pinTask(taskId, shouldPin) {
    try {
        await fetchAPI('/pinTask', {
            method: 'POST',
            body: JSON.stringify({ 
                taskId: parseInt(taskId),
                isPinned: shouldPin
            })
        });

        location.reload();
    } catch (error) {}
}

async function finishTask(taskId) {
    try {
        await fetchAPI('/finishTask', {
            method: 'POST',
            body: JSON.stringify({ taskId: parseInt(taskId) })
        });

        location.reload();
    } catch (error) {}
}