document.addEventListener('DOMContentLoaded', () => {
    loadCategories();
    loadTasks();
    initializeModalHandlers();
    initializeFilterHandlers();
});

let currentTaskId = null;
let taskToDelete = null;
let currentFilter = {
    showFinished: false,
    categoryId: null
};
let userCategories = [];

async function loadCategories() {
    try {
        const response = await fetch('/getCategories', {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json'
            }
        });

        const data = await response.json();

        if (data.status === 'success') {
            userCategories = data.categories || [];
            populateCategoryFilter();
            populateCategoryDropdown();
        }
    } catch (error) {
        console.error('Error loading categories:', error);
    }
}

function populateCategoryFilter() {
    const categoryFilter = document.getElementById('categoryFilter');
    if (!categoryFilter) return;

    categoryFilter.innerHTML = '<option value="">Wszystkie kategorie</option>';
    
    userCategories.forEach(category => {
        const option = document.createElement('option');
        option.value = category.categoryid;
        option.textContent = category.categoryname;
        categoryFilter.appendChild(option);
    });
}

function populateCategoryDropdown() {
    const categorySelect = document.getElementById('taskCategory');
    if (!categorySelect) return;

    categorySelect.innerHTML = '<option value="">Bez kategorii</option>';
    
    userCategories.forEach(category => {
        const option = document.createElement('option');
        option.value = category.categoryid;
        option.textContent = category.categoryname;
        categorySelect.appendChild(option);
    });
}

function initializeFilterHandlers() {
    const finishedToggle = document.getElementById('finishedToggle');
    const categoryFilter = document.getElementById('categoryFilter');

    if (finishedToggle) {
        finishedToggle.addEventListener('change', (e) => {
            currentFilter.showFinished = e.target.checked;
            loadTasks();
        });
    }

    if (categoryFilter) {
        categoryFilter.addEventListener('change', (e) => {
            currentFilter.categoryId = e.target.value ? parseInt(e.target.value) : null;
            loadTasks();
        });
    }
}

async function loadTasks() {
    const todoList = document.getElementById('todoList');
    
    try {
        const endpoint = currentFilter.showFinished ? '/getFinishedTasks' : '/getTasks';
        const response = await fetch(endpoint, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json'
            }
        });

        const responseText = await response.text();
        console.log('Raw response:', responseText);

        let data;
        try {
            data = JSON.parse(responseText);
        } catch (e) {
            console.error('Failed to parse JSON:', e);
            console.error('Response was:', responseText);
            todoList.innerHTML = '<p class="error-message">Błąd formatu odpowiedzi serwera</p>';
            return;
        }

        if (data.status === 'success') {
            let tasks = data.tasks || [];
            
            // Filter by category if selected
            if (currentFilter.categoryId) {
                tasks = tasks.filter(task => task.categoryid == currentFilter.categoryId);
            }
            
            displayTasks(tasks);
        } else {
            console.error('Error from server:', data.message);
            todoList.innerHTML = `<p class="error-message">${data.message || 'Błąd podczas ładowania zadań'}</p>`;
        }
    } catch (error) {
        console.error('Error loading tasks:', error);
        todoList.innerHTML = '<p class="error-message">Nie udało się załadować zadań</p>';
    }
}

function displayTasks(tasks) {
    const todoList = document.getElementById('todoList');
    
    if (tasks.length === 0) {
        const message = currentFilter.showFinished 
            ? 'Brak ukończonych zadań.' 
            : 'Brak zadań do wyświetlenia. Dodaj nowe zadanie!';
        todoList.innerHTML = `<p class="empty-message">${message}</p>`;
        return;
    }

    todoList.innerHTML = '';

    // Sort: pinned first, then by priority or date
    tasks.sort((a, b) => {
        if (a.ispinned && !b.ispinned) return -1;
        if (!a.ispinned && b.ispinned) return 1;
        
        // If both pinned or both unpinned, sort by priority score
        if (a.priorityscore && b.priorityscore) {
            return b.priorityscore - a.priorityscore;
        }
        
        return 0;
    });

    tasks.forEach(task => {
        const listItem = createTaskElement(task);
        todoList.appendChild(listItem);
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

    const isFinished = currentFilter.showFinished;

    let actionButtons;
    if (isFinished) {
        actionButtons = `
            <button class="menu-btn" onclick="unfinishTask(${task.taskid})" title="Przywróć zadanie">
                <img src="public/assets/check.png" class="list-icon" alt="ikona">
            </button>
            <button class="menu-btn" onclick="deleteTask(${task.taskid})" title="Usuń permanentnie">
                <img src="public/assets/delete.png" class="list-icon" alt="ikona">
            </button>
        `;
    } else {
        actionButtons = `
            <button class="menu-btn" onclick="finishTask(${task.taskid})" title="Zakończ zadanie">
                <img src="public/assets/check.png" class="list-icon" alt="ikona">
            </button>
            <button class="menu-btn" onclick="editTask(${task.taskid})" title="Edytuj">
                <img src="public/assets/edit.png" class="list-icon" alt="ikona">
            </button>
            <button class="menu-btn" onclick="deleteTask(${task.taskid})" title="Usuń">
                <img src="public/assets/delete.png" class="list-icon" alt="ikona">
            </button>
        `;
    }

    listItem.innerHTML = `
        <button class="menu-btn" onclick="pinTask(${task.taskid}, ${!isPinned})" title="${isPinned ? 'Odepnij' : 'Przypnij'}">
            <img src="public/assets/${starIcon}" class="list-icon" alt="ikona">
        </button>
        <div class="content">
            <p class="title">${escapeHtml(task.title)}</p>
            ${description}
            <p class="description">Kategoria: ${escapeHtml(categoryName)}</p>
            <p class="description">Termin: ${deadline}</p>
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

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function initializeModalHandlers() {
    const modal = document.getElementById('taskModal');
    const deleteModal = document.getElementById('deleteModal');
    const addBtn = document.getElementById('addTaskBtn');
    const closeBtn = document.querySelector('.close');
    const cancelBtn = document.getElementById('cancelBtn');
    const form = document.getElementById('taskForm');
    const cancelDeleteBtn = document.getElementById('cancelDeleteBtn');
    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');

    if (addBtn) {
        addBtn.addEventListener('click', () => {
            openModal();
        });
    }

    if (closeBtn) {
        closeBtn.addEventListener('click', () => {
            closeModal();
        });
    }

    if (cancelBtn) {
        cancelBtn.addEventListener('click', () => {
            closeModal();
        });
    }

    window.addEventListener('click', (event) => {
        if (event.target === modal) {
            closeModal();
        }
        if (event.target === deleteModal) {
            closeDeleteModal();
        }
    });

    if (form) {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            await saveTask();
        });
    }

    if (cancelDeleteBtn) {
        cancelDeleteBtn.addEventListener('click', () => {
            closeDeleteModal();
        });
    }

    if (confirmDeleteBtn) {
        confirmDeleteBtn.addEventListener('click', async () => {
            await confirmDelete();
        });
    }
}

function openModal(taskId = null) {
    const modal = document.getElementById('taskModal');
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
        clearTaskForm();
    }

    modal.style.display = 'block';
    document.getElementById('taskTitle').focus();
}

function closeModal() {
    const modal = document.getElementById('taskModal');
    modal.style.display = 'none';
    currentTaskId = null;
    clearTaskForm();
}

function clearTaskForm() {
    document.getElementById('taskTitle').value = '';
    document.getElementById('taskDescription').value = '';
    document.getElementById('taskCategory').value = '';
    document.getElementById('taskDeadline').value = '';
    document.getElementById('taskFun').value = 'medium';
    document.getElementById('taskDifficulty').value = 'medium';
    document.getElementById('taskImportance').value = 'medium';
    document.getElementById('taskTime').value = 'medium';
}

async function loadTaskData(taskId) {
    try {
        const response = await fetch(`/getTask?taskId=${taskId}`, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json'
            }
        });

        const data = await response.json();

        if (data.status === 'success' && data.task) {
            const task = data.task;
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
        }
    } catch (error) {
        console.error('Error loading task data:', error);
        alert('Nie udało się załadować danych zadania');
    }
}

async function saveTask() {
    const taskId = document.getElementById('taskId').value;
    const title = document.getElementById('taskTitle').value.trim();
    const description = document.getElementById('taskDescription').value.trim();
    const categoryId = document.getElementById('taskCategory').value;
    const deadline = document.getElementById('taskDeadline').value;
    const fun = document.getElementById('taskFun').value;
    const difficulty = document.getElementById('taskDifficulty').value;
    const importance = document.getElementById('taskImportance').value;
    const time = document.getElementById('taskTime').value;

    if (!title) {
        alert('Tytuł zadania jest wymagany');
        return;
    }

    const url = taskId ? '/updateTask' : '/createTask';
    const payload = {
        title,
        taskDescription: description || null,
        categoryId: categoryId ? parseInt(categoryId) : null,
        deadlineDate: deadline || null,
        fun,
        difficulty,
        importance,
        time
    };

    if (taskId) {
        payload.taskId = parseInt(taskId);
    }

    try {
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(payload)
        });

        const data = await response.json();

        if (data.status === 'success') {
            closeModal();
            await loadTasks();
        } else {
            alert(data.message || 'Błąd podczas zapisywania zadania');
        }
    } catch (error) {
        console.error('Error saving task:', error);
        alert('Nie udało się zapisać zadania');
    }
}

function editTask(taskId) {
    openModal(taskId);
}

function confirmDeleteTask(taskId) {
    taskToDelete = taskId;
    const deleteModal = document.getElementById('deleteModal');
    deleteModal.style.display = 'block';
}

function closeDeleteModal() {
    const deleteModal = document.getElementById('deleteModal');
    deleteModal.style.display = 'none';
    taskToDelete = null;
}

async function confirmDelete() {
    if (!taskToDelete) return;
    await deleteTask(taskToDelete);
}

async function deleteTask(taskId) {
    if (!confirm('Czy na pewno chcesz usunąć to zadanie?')) {
        return;
    }

    try {
        const response = await fetch('/deleteTask', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ taskId: parseInt(taskId) })
        });

        const data = await response.json();

        if (data.status === 'success') {
            closeDeleteModal();
            await loadTasks();
        } else {
            alert(data.message || 'Błąd podczas usuwania zadania');
        }
    } catch (error) {
        console.error('Error deleting task:', error);
        alert('Nie udało się usunąć zadania');
    }
}

async function pinTask(taskId, shouldPin) {
    try {
        const response = await fetch('/pinTask', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ 
                taskId: parseInt(taskId),
                isPinned: shouldPin
            })
        });

        const data = await response.json();

        if (data.status === 'success') {
            await loadTasks();
        } else {
            alert(data.message || 'Błąd podczas przypinania zadania');
        }
    } catch (error) {
        console.error('Error pinning task:', error);
        alert('Nie udało się przypiąć zadania');
    }
}

async function finishTask(taskId) {
    try {
        const response = await fetch('/finishTask', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ taskId: parseInt(taskId) })
        });

        const data = await response.json();

        if (data.status === 'success') {
            await loadTasks();
            if (data.message) {
                console.log(data.message);
            }
        } else {
            alert(data.message || 'Błąd podczas kończenia zadania');
        }
    } catch (error) {
        console.error('Error finishing task:', error);
        alert('Nie udało się zakończyć zadania');
    }
}

async function unfinishTask(taskId) {
    try {
        const response = await fetch('/unfinishTask', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ taskId: parseInt(taskId) })
        });

        const data = await response.json();

        if (data.status === 'success') {
            await loadTasks();
        } else {
            alert(data.message || 'Błąd podczas przywracania zadania');
        }
    } catch (error) {
        console.error('Error unfinishing task:', error);
        alert('Nie udało się przywrócić zadania');
    }
}