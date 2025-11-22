// Toggle password visibility
const togglePassword = document.getElementById('togglePassword');
const passwordInput = document.getElementById('password');

if (togglePassword && passwordInput) {
    togglePassword.addEventListener('click', function () {
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);

        // Toggle active class
        this.classList.toggle('active');

        const eyeIcon = this.querySelector('.eye-icon');
        if (type === 'text') {
            eyeIcon.innerHTML = `
                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                <line x1="1" y1="1" x2="23" y2="23"></line>
            `;
        } else {
            eyeIcon.innerHTML = `
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                <circle cx="12" cy="12" r="3"></circle>
            `;
        }
    });
}

// Hamburger menu
const hamburger = document.getElementById('hamburger');
const navigation = document.getElementById('navigation');

if (hamburger && navigation) {
    hamburger.addEventListener('click', () => {
        hamburger.classList.toggle('active');
        navigation.classList.toggle('active');
    });
}

// Close menu when clicking outside
document.addEventListener('click', (e) => {
    if (navigation && hamburger) {
        if (!navigation.contains(e.target) && !hamburger.contains(e.target)) {
            navigation.classList.remove('active');
            hamburger.classList.remove('active');
        }
    }
});

// Auto-hide error banner
const errorBanner = document.querySelector('.error-banner');
if (errorBanner) {
    setTimeout(() => {
        errorBanner.style.transition = 'opacity 0.5s, transform 0.5s';
        errorBanner.style.opacity = '0';
        errorBanner.style.transform = 'translateY(-10px)';
        setTimeout(() => {
            errorBanner.style.display = 'none';
        }, 500);
    }, 5000);
}
