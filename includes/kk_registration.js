// Hamburger menu toggle
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

// Auto-calculate age from birthdate
const birthdateInput = document.getElementById('birthdate');
const ageInput = document.getElementById('age');

if (birthdateInput && ageInput) {
    birthdateInput.addEventListener('change', function () {
        const birthdate = new Date(this.value);
        const today = new Date();
        let age = today.getFullYear() - birthdate.getFullYear();
        const monthDiff = today.getMonth() - birthdate.getMonth();

        if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthdate.getDate())) {
            age--;
        }

        if (age >= 15 && age <= 30) {
            ageInput.value = age;
        }
    });
}

// Form validation before submit
const registrationForm = document.querySelector('form');

if (registrationForm) {
    registrationForm.addEventListener('submit', function (e) {
        const age = parseInt(document.getElementById('age').value);

        if (age < 15 || age > 30) {
            e.preventDefault();
            alert('Age must be between 15 and 30 years old for KK membership.');
            return false;
        }
    });
}

// Show/hide number of times attended based on KK assembly attendance
const attendAssemblyRadios = document.querySelectorAll('input[name="attend_kk_assembly"]');
const numTimesAttendedGroup = document.getElementById('num_times_attended')?.parentElement;

if (attendAssemblyRadios.length > 0 && numTimesAttendedGroup) {
    attendAssemblyRadios.forEach(radio => {
        radio.addEventListener('change', function () {
            if (this.value === 'No') {
                document.getElementById('num_times_attended').value = 0;
                numTimesAttendedGroup.style.opacity = '0.5';
            } else {
                numTimesAttendedGroup.style.opacity = '1';
            }
        });
    });
}

// Contact number formatting (Philippine format)
const contactInput = document.getElementById('contact_number');

if (contactInput) {
    contactInput.addEventListener('input', function (e) {
        // Remove non-numeric characters except + and -
        let value = this.value.replace(/[^\d+\-]/g, '');
        this.value = value;
    });
}

// Smooth scroll for error messages
window.addEventListener('load', function () {
    const errorMessage = document.querySelector('.error-message, .general-error');
    if (errorMessage) {
        errorMessage.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
});
