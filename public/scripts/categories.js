// categories.js - Refactored
document.addEventListener('DOMContentLoaded', () => {
    loadCategories();
    initializeModalHandlers();
    initializeCategoryListHandlers();
});

let currentCategoryId = null;
let categoryToDelete = null;

// Cache DOM elements
const elements = {
    get categoriesList() { return document.getElementById('categoriesList'); },
    get categoryModal() { return document.getElementById('categoryModal'); },
    get deleteModal() { return document.getElementById('deleteModal'); },
    get categoryForm() { return document.getElementById('categoryForm'); }
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
        displayCategories(data.categories);
    } catch (error) {
        elements.categoriesList.innerHTML = '<p class="error-message">Nie udało się załadować kategorii</p>';
    }
}

function displayCategories(categories) {
    if (!categories || categories.length === 0) {
        elements.categoriesList.innerHTML = '<p class="empty-message">Brak kategorii. Dodaj swoją pierwszą kategorię!</p>';
        return;
    }

    elements.categoriesList.innerHTML = '';

    categories.forEach(category => {
        const categoryItem = createCategoryElement(category);
        elements.categoriesList.appendChild(categoryItem);
    });
}

function createCategoryElement(category) {
    const categoryItem = document.createElement('div');
    categoryItem.className = 'list-item';
    categoryItem.dataset.categoryId = category.categoryid;
    categoryItem.dataset.categoryName = category.categoryname;

    categoryItem.innerHTML = `
        <div class="content">
            <h3 class="category-name">${escapeHtml(category.categoryname)}</h3>
        </div>
        <div class="action-buttons">
            <button class="menu-btn" data-action="edit" title="Edytuj">
                <img src="public/assets/edit.png" class="list-icon" alt="ikona">
            </button>
            <button class="menu-btn" data-action="delete" title="Usuń">
                <img src="public/assets/delete.png" class="list-icon" alt="ikona">
            </button>
        </div>
    `;

    return categoryItem;
}

// Event delegation for category list
function initializeCategoryListHandlers() {
    elements.categoriesList.addEventListener('click', (e) => {
        const button = e.target.closest('.menu-btn');
        if (!button) return;

        const listItem = button.closest('.list-item');
        const categoryId = parseInt(listItem.dataset.categoryId);
        const categoryName = listItem.dataset.categoryName;
        const action = button.dataset.action;

        switch (action) {
            case 'edit':
                editCategory(categoryId, categoryName);
                break;
            case 'delete':
                confirmDeleteCategory(categoryId);
                break;
        }
    });
}

// Modal handlers
function initializeModalHandlers() {
    const addBtn = document.getElementById('addCategoryBtn');
    const cancelBtn = document.getElementById('cancelBtn');
    const cancelDeleteBtn = document.getElementById('cancelDeleteBtn');
    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');

    addBtn.addEventListener('click', () => openModal());
    cancelBtn.addEventListener('click', closeModal);
    cancelDeleteBtn.addEventListener('click', closeDeleteModal);
    confirmDeleteBtn.addEventListener('click', async (e) => {
        e.preventDefault();
        await deleteCategory();
    });

    elements.categoryForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        await saveCategory();
    });
}

function openModal(categoryId = null, categoryName = '') {
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

    elements.categoryModal.style.display = 'flex';
    categoryNameInput.focus();
}

function closeModal() {
    elements.categoryModal.style.display = 'none';
    currentCategoryId = null;
}

function editCategory(categoryId, categoryName) {
    openModal(categoryId, categoryName);
}

async function saveCategory() {
    const categoryId = document.getElementById('categoryId').value;
    const categoryName = document.getElementById('categoryName').value.trim();

    if (!categoryName) {
        showError('Nazwa kategorii jest wymagana');
        return;
    }

    const url = categoryId ? '/updateCategory' : '/createCategory';
    const payload = categoryId 
        ? { categoryId: parseInt(categoryId), categoryName }
        : { categoryName };

    try {
        await fetchAPI(url, {
            method: 'POST',
            body: JSON.stringify(payload)
        });

        closeModal();
        await loadCategories();
    } catch (error) {
        // Error already handled by fetchAPI
    }
}

// Delete modal
function confirmDeleteCategory(categoryId) {
    categoryToDelete = categoryId;
    elements.deleteModal.style.display = 'flex';
}

function closeDeleteModal() {
    elements.deleteModal.style.display = 'none';
    categoryToDelete = null;
}

async function deleteCategory() {
    if (!categoryToDelete) return;

    try {
        await fetchAPI('/deleteCategory', {
            method: 'POST',
            body: JSON.stringify({ categoryId: categoryToDelete })
        });

        closeDeleteModal();
        await loadCategories();
    } catch (error) {
        // Error already handled by fetchAPI
    }
}