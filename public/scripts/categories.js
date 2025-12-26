document.addEventListener('DOMContentLoaded', () => {
    loadCategories();
    initializeModalHandlers();
});

let currentCategoryId = null;
let categoryToDelete = null;

async function loadCategories() {
    const categoriesList = document.getElementById('categoriesList');
    
    try {
        const response = await fetch('/getCategories', {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json'
            }
        });

        const data = await response.json();

        if (data.status === 'success') {
            displayCategories(data.categories);
        } else {
            categoriesList.innerHTML = '<p class="error-message">Błąd podczas ładowania kategorii</p>';
        }
    } catch (error) {
        console.error('Error loading categories:', error);
        categoriesList.innerHTML = '<p class="error-message">Nie udało się załadować kategorii</p>';
    }
}

function displayCategories(categories) {
    const categoriesList = document.getElementById('categoriesList');
    
    if (!categories || categories.length === 0) {
        categoriesList.innerHTML = '<p class="empty-message">Brak kategorii. Dodaj swoją pierwszą kategorię!</p>';
        return;
    }

    categoriesList.innerHTML = '';

    categories.forEach(category => {
        const categoryItem = createCategoryElement(category);
        categoriesList.appendChild(categoryItem);
    });
}

function createCategoryElement(category) {
    const categoryItem = document.createElement('div');
    categoryItem.className = 'list-item';
    categoryItem.dataset.categoryId = category.categoryid;

    categoryItem.innerHTML = `
        <div class="content">
            <h3 class="category-name">${escapeHtml(category.categoryname)}</h3>
        </div>
        <div class="action-buttons">
            <button class="menu-btn" onclick="editCategory(${category.categoryid}, '${escapeHtml(category.categoryname)}')">
                <img src="public/assets/edit.png" class="list-icon" alt="ikona">
            </button>
            <button class="menu-btn" onclick="confirmDeleteCategory(${category.categoryid})">
                <img src="public/assets/delete.png" class="list-icon" alt="ikona">
            </button>
        </div>
    `;

    return categoryItem;
}

function initializeModalHandlers() {
    const modal = document.getElementById('categoryModal');
    const deleteModal = document.getElementById('deleteModal');
    const addBtn = document.getElementById('addCategoryBtn');
    const closeBtn = document.querySelector('.close');
    const cancelBtn = document.getElementById('cancelBtn');
    const form = document.getElementById('categoryForm');
    const cancelDeleteBtn = document.getElementById('cancelDeleteBtn');
    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');

    addBtn.addEventListener('click', () => {
        openModal();
    });

    if (closeBtn) {
        closeBtn.addEventListener('click', () => {
            closeModal();
        });
    }

    cancelBtn.addEventListener('click', () => {
        closeModal();
    });

    window.addEventListener('click', (event) => {
        if (event.target === modal) {
            closeModal();
        }
        if (event.target === deleteModal) {
            closeDeleteModal();
        }
    });

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        await saveCategory();
    });

    cancelDeleteBtn.addEventListener('click', () => {
        closeDeleteModal();
    });

    confirmDeleteBtn.addEventListener('click', async () => {
        await deleteCategory();
    });
}

function openModal(categoryId = null, categoryName = '') {
    const modal = document.getElementById('categoryModal');
    const modalTitle = document.getElementById('modalTitle');
    const categoryIdInput = document.getElementById('categoryId');
    const categoryNameInput = document.getElementById('categoryName');

    currentCategoryId = categoryId;
    
    if (categoryId) {
        modalTitle.textContent = 'Edytuj kategorię';
        categoryIdInput.value = categoryId;
        categoryNameInput.value = categoryName;
    } else {
        modalTitle.textContent = 'Dodaj kategorię';
        categoryIdInput.value = '';
        categoryNameInput.value = '';
    }

    modal.style.display = 'block';
    categoryNameInput.focus();
}

function closeModal() {
    const modal = document.getElementById('categoryModal');
    modal.style.display = 'none';
    currentCategoryId = null;
}

function editCategory(categoryId, categoryName) {
    openModal(categoryId, categoryName);
}

async function saveCategory() {
    const categoryId = document.getElementById('categoryId').value;
    const categoryName = document.getElementById('categoryName').value.trim();

    if (!categoryName) {
        alert('Nazwa kategorii jest wymagana');
        return;
    }

    const url = categoryId ? '/updateCategory' : '/createCategory';
    const payload = categoryId 
        ? { categoryId: parseInt(categoryId), categoryName }
        : { categoryName };

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
            await loadCategories();
        } else {
            alert(data.message || 'Błąd podczas zapisywania kategorii');
        }
    } catch (error) {
        console.error('Error saving category:', error);
        alert('Nie udało się zapisać kategorii');
    }
}

function confirmDeleteCategory(categoryId) {
    categoryToDelete = categoryId;
    const deleteModal = document.getElementById('deleteModal');
    deleteModal.style.display = 'block';
}

function closeDeleteModal() {
    const deleteModal = document.getElementById('deleteModal');
    deleteModal.style.display = 'none';
    categoryToDelete = null;
}

async function deleteCategory() {
    if (!categoryToDelete) return;

    try {
        const response = await fetch('/deleteCategory', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ categoryId: categoryToDelete })
        });

        const data = await response.json();

        if (data.status === 'success') {
            closeDeleteModal();
            await loadCategories();
        } else {
            alert(data.message || 'Błąd podczas usuwania kategorii');
        }
    } catch (error) {
        console.error('Error deleting category:', error);
        alert('Nie udało się usunąć kategorii');
    }
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}