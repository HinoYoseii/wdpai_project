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

// Inicjalizacja przycisków menu przy kategoriach
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
                openModal(categoryId, categoryName);
                break;
            case 'delete':
                categoryToDelete = categoryId;
                elements.deleteModal.style.display = 'flex';
                break;
        }
    });
}

// Inicjalizacja okien modalnych add edit delete
function initializeModalHandlers() {
    document.getElementById('addCategoryBtn').addEventListener('click', () => openModal());
    document.getElementById('addCategoryBtnMobile').addEventListener('click', () => openModal());
    document.getElementById('cancelBtn').addEventListener('click', closeModal);
    document.getElementById('cancelDeleteBtn').addEventListener('click', closeDeleteModal);
    document.getElementById('confirmDeleteBtn').addEventListener('click', async (e) => {
        e.preventDefault(); 
        await deleteCategory();
    });

    elements.categoryForm.addEventListener('submit', async (e) => {
        e.preventDefault(); 
        await saveCategory();
    });
}

// Otwórz add/edit modal
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

// Zamknij add/edit modal
function closeModal() {
    elements.categoryModal.style.display = 'none';
    currentCategoryId = null;
}

// Zamknij delete modal
function closeDeleteModal() {
    elements.deleteModal.style.display = 'none';
    categoryToDelete = null;
}

// Zapisz kategorię
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
    } catch (error) {}
}

// Usuń kategorię
async function deleteCategory() {
    if (!categoryToDelete) return;

    try {
        await fetchAPI('/deleteCategory', {
            method: 'POST',
            body: JSON.stringify({ categoryId: categoryToDelete })
        });

        closeDeleteModal();
        location.reload();
    } catch (error) {}
}