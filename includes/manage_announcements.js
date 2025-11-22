const modal = document.getElementById('announcementModal');

function openModal() {
    document.getElementById('modalTitle').innerText = "Add Announcement";
    document.getElementById('announcement_id').value = '';
    document.getElementById('title').value = '';
    document.getElementById('content').value = '';
    document.getElementById('image').value = '';
    updateCharCount('title', 'titleCount', 200);
    updateCharCount('content', 'contentCount', 5000);
    modal.style.display = 'block';
}

function closeModal() {
    modal.style.display = 'none';
}

window.onclick = function (e) {
    if (e.target == modal) {
        closeModal();
    }
}

function editAnnouncement(data) {
    document.getElementById('modalTitle').innerText = "Edit Announcement";
    document.getElementById('announcement_id').value = data.announcement_id;
    document.getElementById('title').value = data.title;
    document.getElementById('content').value = data.content;
    updateCharCount('title', 'titleCount', 200);
    updateCharCount('content', 'contentCount', 5000);
    modal.style.display = 'block';
}

// Character counter
function updateCharCount(fieldId, countId, maxLength) {
    const field = document.getElementById(fieldId);
    const counter = document.getElementById(countId);
    const currentLength = field.value.length;
    counter.textContent = currentLength + '/' + maxLength;

    // Color coding
    counter.classList.remove('warning', 'danger');
    if (currentLength > maxLength * 0.9) {
        counter.classList.add('danger');
    } else if (currentLength > maxLength * 0.75) {
        counter.classList.add('warning');
    }
}

// Image validation
function validateImage(input) {
    if (input.files && input.files[0]) {
        const file = input.files[0];
        const fileSize = file.size / 1024 / 1024; // in MB
        const validTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];

        if (fileSize > 5) {
            alert('Image size must be less than 5MB!');
            input.value = '';
            return false;
        }

        if (!validTypes.includes(file.type)) {
            alert('Only JPG, PNG, and GIF images are allowed!');
            input.value = '';
            return false;
        }
    }
}

document.getElementById('hamburger').addEventListener('click', () => {
    document.getElementById('navigation').classList.toggle('active');
    document.getElementById('hamburger').classList.toggle('active');
});
