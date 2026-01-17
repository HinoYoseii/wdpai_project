const preferencesForm = document.querySelector('.form-preferences');

preferencesForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const formData = new FormData(preferencesForm);
    
    try {
        const response = await fetch('/updatePrefs', {method: 'POST',body: formData});
        
        const data = await response.json();
        
        if (response.ok && data.status === 'success') {
            alert('Preferencje zostały zapisane!');
        } else {
            alert('Błąd: ' + (data.message || 'Nie udało się zapisać preferencji'));
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Wystąpił błąd podczas zapisywania preferencji');
    }
});

document.addEventListener('DOMContentLoaded', function() {
    const rangeInputs = document.querySelectorAll('input[type="range"]');
    
    rangeInputs.forEach(function(input) {
        const valueDisplay = input.previousElementSibling;
        
        input.addEventListener('input', function() {
            valueDisplay.textContent = "Mnożnik x" + this.value;
        });
    });
});