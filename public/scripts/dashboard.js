document.addEventListener('DOMContentLoaded', () => {
    loadTasks();
});

async function loadTasks() {
    const todoList = document.getElementById('todoList');
    
    try {
        const response = await fetch('/getTasks', {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json'
            }
        });

        // Log the raw response for debugging
        const responseText = await response.text();
        console.log('Raw response:', responseText);

        // Try to parse as JSON
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
            displayTasks(data.tasks);
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
        todoList.innerHTML = '<p class="empty-message">Brak zadań do wyświetlenia. Dodaj nowe zadanie!</p>';
        return;
    }

    todoList.innerHTML = '';

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
    const isOverdue = task.deadlinedate && new Date(task.deadlinedate) < new Date();
    const deadlineClass = isOverdue ? 'overdue' : '';

    listItem.innerHTML = `
        <button class="menu-btn" onclick="pinTask(${task.taskid})">
            <img src="public/assets/star_empty.png" class="list-icon" alt="ikona">
        </button>
        <div class="content">
            <p class="title">${escapeHtml(task.taskdescription)}</p>
            <p class="description ${deadlineClass}">
                <span>Termin: ${deadline}</span>
                ${task.priorityScore ? `<span class="priority-badge">Priorytet: ${task.priorityScore}</span>` : ''}
            </p>
        </div>
        <div class="menu-buttons">
            <button class="menu-btn" onclick="finishTask(${task.taskid})">
                <img src="public/assets/check.png" class="list-icon" alt="ikona">
            </button>
            <button class="menu-btn" onclick="editTask(${task.taskid})">
                <img src="public/assets/edit.png" class="list-icon" alt="ikona">
            </button>
            <button class="menu-btn" onclick="deleteTask(${task.taskid})">
                <img src="public/assets/delete.png" class="list-icon" alt="ikona">
            </button>
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
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function pinTask(taskId) {
    console.log('Pin for task:', taskId);
}

function finishTask(taskId) {
    console.log('Finish for task:', taskId);
}

function editTask(taskId) {
    console.log('Edit for task:', taskId);
}

function deleteTask(taskId) {
    console.log('Delete for task:', taskId);
}