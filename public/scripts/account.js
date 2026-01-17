document.addEventListener('DOMContentLoaded', function() {
    const rangeInputs = document.querySelectorAll('input[type="range"]');
    
    rangeInputs.forEach(function(input) {
        const valueDisplay = input.previousElementSibling;
        
        input.addEventListener('input', function() {
            valueDisplay.textContent = this.value;
        });
    });
});