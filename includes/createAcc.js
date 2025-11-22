// Hamburger menu toggle
const hamburger = document.getElementById('hamburger');
const navigation = document.getElementById('navigation');
hamburger.addEventListener('click', () => {
    hamburger.classList.toggle('active');
    navigation.classList.toggle('active');
});

// Password toggle functionality (for create form)
function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
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

// Form validation on client side
document.addEventListener('DOMContentLoaded', function () {
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

    // Modal handling for Edit Account
    const modal = document.getElementById("editModal");
    const closeBtn = document.querySelector(".close");
    const editButtons = document.querySelectorAll(".btn-edit");

    if (editButtons.length > 0) {
        editButtons.forEach(button => {
            button.addEventListener("click", () => {
                modal.style.display = "flex";
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
                    roleWarning.style.display = 'block';
                } else {
                    roleSelect.disabled = false;
                    roleWarning.style.display = 'none';
                }
            });
        });
    }


    if (closeBtn) {
        closeBtn.onclick = closeModal;
    }

    window.onclick = function (event) {
        if (event.target == modal) {
            closeModal();
        }
    };

    // Edit Form validation
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

    // Auto-dismiss alerts after 5 seconds
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

// Close modal function
function closeModal() {
    const modal = document.getElementById("editModal");
    if (modal) {
        modal.style.display = "none";
    }
}
