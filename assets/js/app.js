document.addEventListener('DOMContentLoaded', function() {
    // Auto-uppercase item codes on input
    const itemCodeInput = document.getElementById('itemCode');
    if (itemCodeInput) {
        itemCodeInput.addEventListener('input', function() {
            this.value = this.value.toUpperCase();
        });
    }
});