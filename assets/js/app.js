// Close modal when clicking overlay
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal-overlay')) {
        e.target.classList.remove('show');
    }
});

// Close modal with Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        document.querySelectorAll('.modal-overlay.show').forEach(function(el) {
            el.classList.remove('show');
        });
    }
});

// Auto-hide alerts after 5 seconds
document.querySelectorAll('.alert').forEach(function(alert) {
    setTimeout(function() {
        alert.style.transition = 'opacity 0.5s';
        alert.style.opacity = '0';
        setTimeout(function() { alert.remove(); }, 500);
    }, 5000);
});

// Format currency input
document.addEventListener('input', function(e) {
    if (e.target.classList.contains('currency-input')) {
        let value = e.target.value.replace(/[^0-9]/g, '');
        if (value) {
            e.target.value = parseInt(value, 10).toLocaleString('id-ID');
        } else {
            e.target.value = '';
        }
    }
});

// Strip dots before form submit
document.querySelectorAll('form').forEach(function(form) {
    form.addEventListener('submit', function() {
        form.querySelectorAll('.currency-input').forEach(function(input) {
            input.value = input.value.replace(/\./g, '');
        });
    });
});

document.querySelectorAll('input[type="number"]').forEach(function(input) {
    input.addEventListener('wheel', function(e) {
        e.preventDefault();
    });
});
