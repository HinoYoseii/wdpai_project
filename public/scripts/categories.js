document.addEventListener('DOMContentLoaded', () => {
    initializeModalHandlers();
    initializeCategoryListHandlers();
});

let currentCategoryId = null;
let categoryToDelete = null;

const elements = {
    get categoriesList() { return document.getElementById('categoriesList'); },
    get categoryModal() { return document.getElementById('categoryModal'); },
    get deleteModal() { return document.getElementById('deleteModal'); },
    get categoryForm() { return document.getElementById('categoryForm'); }
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

function initializeModalHandlers() {
    const addBtn = document.getElementById('addCategoryBtn');
    const addBtnMobile = document.getElementById('addCategoryBtnMobile');
    const cancelBtn = document.getElementById('cancelBtn');
    const cancelDeleteBtn = document.getElementById('cancelDeleteBtn');
    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');

    addBtn.addEventListener('click', () => openModal());
    addBtnMobile.addEventListener('click', () => openModal());
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
        location.reload();
    } catch (error) {
        // Error already handled by fetchAPI
    }
}

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
        location.reload();
    } catch (error) {
        // Error already handled by fetchAPI
    }
}