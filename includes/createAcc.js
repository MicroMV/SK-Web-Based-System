// Hamburger menu toggle
const hamburger = document.getElementById('hamburger');
const navigation = document.getElementById('navigation');

if (hamburger && navigation) {
    hamburger.addEventListener('click', () => {
        hamburger.classList.toggle('active');
        navigation.classList.toggle('active');
    });
}

// Password toggle functionality (works for BOTH create and edit forms)
function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    if (!field) return; // Safety check
    
    const icon = field.nextElementSibling;

    if (field.type === "password") {
        field.type = "text";
        icon.classList.remove("fa-eye");
        icon.classList.add("fa-eye-slash");
    } else {
        field.type = "password";
        icon.classList.remove("fa-eye-slash");
        icon.classList.add("fa-eye");
    }
}

// DOMContentLoaded - All initialization code
document.addEventListener('DOMContentLoaded', function () {
    
    // ===== PASSWORD TOGGLE EVENT LISTENERS FOR CREATE FORM =====
    const togglePasswordBtn = document.getElementById('togglePassword');
    const toggleConfirmPasswordBtn = document.getElementById('toggleConfirmPassword');
    
    // Password field toggle
    if (togglePasswordBtn) {
        togglePasswordBtn.addEventListener('click', function() {
            const passwordField = document.getElementById('password');
            if (passwordField) {
                if (passwordField.type === 'password') {
                    passwordField.type = 'text';
                    this.classList.remove('fa-eye');
                    this.classList.add('fa-eye-slash');
                } else {
                    passwordField.type = 'password';
                    this.classList.remove('fa-eye-slash');
                    this.classList.add('fa-eye');
                }
            }
        });
    }
    
    // Confirm Password field toggle - FIXED ID
    if (toggleConfirmPasswordBtn) {
        toggleConfirmPasswordBtn.addEventListener('click', function() {
            const confirmField = document.getElementById('confirm_password');
            
            if (confirmField) {
                if (confirmField.type === 'password') {
                    confirmField.type = 'text';
                    this.classList.remove('fa-eye');
                    this.classList.add('fa-eye-slash');
                } else {
                    confirmField.type = 'password';
                    this.classList.remove('fa-eye-slash');
                    this.classList.add('fa-eye');
                }
            }
        });
    }

    // ===== CREATE ACCOUNT FORM VALIDATION =====
    const createForm = document.getElementById('createAccountForm');
    if (createForm) {
        createForm.addEventListener('submit', function (e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;

            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match!');
                return false;
            }

            if (password.length < 8) {
                e.preventDefault();
                alert('Password must be at least 8 characters long!');
                return false;
            }
        });
    }

    // ===== EDIT MODAL HANDLING =====
    const modal = document.getElementById("editModal");
    const closeBtn = document.querySelector(".close");
    const editButtons = document.querySelectorAll(".btn-edit");

    if (editButtons.length > 0) {
        editButtons.forEach(button => {
            button.addEventListener("click", () => {
                if (modal) {
                    modal.style.display = "flex";
                }
                
                const isLastAdmin = button.dataset.islastadmin === 'true';

                document.getElementById("edit_user_id").value = button.dataset.id;
                document.getElementById("edit_username").value = button.dataset.username;
                document.getElementById("edit_full_name").value = button.dataset.fullname;
                document.getElementById("edit_role").value = button.dataset.role;
                document.getElementById("edit_password").value = "";
                document.getElementById("edit_confirm_password").value = "";

                // Disable role change if last admin
                const roleSelect = document.getElementById("edit_role");
                const roleWarning = document.getElementById("role_warning");

                if (isLastAdmin) {
                    roleSelect.disabled = true;
                    if (roleWarning) roleWarning.style.display = 'block';
                } else {
                    roleSelect.disabled = false;
                    if (roleWarning) roleWarning.style.display = 'none';
                }
            });
        });
    }

    // Close modal button
    if (closeBtn) {
        closeBtn.onclick = closeModal;
    }

    // Close modal when clicking outside
    window.onclick = function (event) {
        if (event.target == modal) {
            closeModal();
        }
    };

    // ===== EDIT FORM VALIDATION =====
    const editForm = document.getElementById("editForm");
    if (editForm) {
        editForm.addEventListener("submit", function (event) {
            const password = document.getElementById("edit_password").value;
            const confirmPassword = document.getElementById("edit_confirm_password").value;

            if (password) {
                const pattern = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/;

                if (!pattern.test(password)) {
                    alert("Password must be at least 8 characters with uppercase, lowercase, and numbers.");
                    event.preventDefault();
                    return false;
                }

                if (password !== confirmPassword) {
                    alert("Passwords do not match.");
                    event.preventDefault();
                    return false;
                }
            }
        });
    }

    // ===== AUTO-DISMISS ALERTS =====
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s';
            alert.style.opacity = '0';
            setTimeout(() => {
                alert.remove();
            }, 500);
        }, 5000); // 5 seconds
    });
});

// ===== CLOSE MODAL FUNCTION =====
function closeModal() {
    const modal = document.getElementById("editModal");
    if (modal) {
        modal.style.display = "none";
    }
}
