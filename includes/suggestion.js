// Hamburger menu toggle
const hamburger = document.getElementById('hamburger');
const navigation = document.getElementById('navigation');

if (hamburger && navigation) {
    hamburger.addEventListener('click', () => {
        hamburger.classList.toggle('active');
        navigation.classList.toggle('active');
    });
}

// Character counter for message textarea
function updateCharCount() {
    const message = document.getElementById('message');
    const charCount = document.getElementById('charCount');
    if (message && charCount) {
        charCount.textContent = message.value.length;
    }
}

// Toggle identity section based on anonymous checkbox
function toggleIdentity() {
    const checkbox = document.getElementById('anonymous');
    const identitySection = document.getElementById('identityFields');
    const nameField = document.getElementById('name');
    const emailField = document.getElementById('email');

    if (checkbox && identitySection) {
        if (checkbox.checked) {
            identitySection.style.display = 'none';
            if (nameField) nameField.removeAttribute('required');
            if (emailField) emailField.value = '';
            if (nameField) nameField.value = '';
        } else {
            identitySection.style.display = 'block';
            if (nameField) nameField.setAttribute('required', 'required');
        }
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    // Update character count
    const messageField = document.getElementById('message');
    if (messageField) {
        updateCharCount();
        messageField.addEventListener('input', updateCharCount);
    }

    // Anonymous checkbox handler
    const anonymousCheckbox = document.getElementById('anonymous');
    if (anonymousCheckbox) {
        anonymousCheckbox.addEventListener('change', toggleIdentity);
        
        // Initialize state if checked on page load
        if (anonymousCheckbox.checked) {
            toggleIdentity();
        }
    }

    // Smooth scroll for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            const targetId = this.getAttribute('href');
            if (targetId !== '#') {
                e.preventDefault();
                const target = document.querySelector(targetId);
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            }
        });
    });

    // Form validation enhancement
    const form = document.getElementById('suggestionForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            const anonymousCheckbox = document.getElementById('anonymous');
            const nameField = document.getElementById('name');
            
            // If not anonymous, ensure name is filled
            if (!anonymousCheckbox.checked && nameField && !nameField.value.trim()) {
                e.preventDefault();
                nameField.focus();
                alert('Please enter your name or check "Submit Anonymously"');
                return false;
            }
        });
    }

    // Auto-hide success/error messages after 5 seconds
    const alerts = document.querySelectorAll('.alert');
    if (alerts.length > 0) {
        setTimeout(() => {
            alerts.forEach(alert => {
                alert.style.transition = 'opacity 0.5s';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            });
        }, 5000);
    }
});

// Close alerts manually (if you add close buttons later)
function closeAlert(element) {
    element.style.transition = 'opacity 0.3s';
    element.style.opacity = '0';
    setTimeout(() => element.remove(), 300);
}
