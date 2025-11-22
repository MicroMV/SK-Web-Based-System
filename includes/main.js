// Hamburger menu toggle
const hamburger = document.getElementById('hamburger');
const navigation = document.getElementById('navigation');

hamburger.addEventListener('click', () => {
    hamburger.classList.toggle('active');
    navigation.classList.toggle('active');
});

// Close menu when clicking outside
document.addEventListener('click', (e) => {
    if (!hamburger.contains(e.target) && !navigation.contains(e.target)) {
        hamburger.classList.remove('active');
        navigation.classList.remove('active');
    }
});

// Announcement Carousel
const swiper = new Swiper('.announcement-carousel', {
    loop: true,
    grabCursor: true,
    spaceBetween: 20,
    centeredSlides: true,
    effect: 'coverflow',
    slidesPerView: 'auto',
    coverflowEffect: {
        rotate: 25,
        stretch: 0,
        depth: 150,
        modifier: 1.2,
        slideShadows: true
    },
    autoplay: {
        delay: 4000,
        disableOnInteraction: false
    },
    pagination: {
        el: '.swiper-pagination',
        clickable: true
    },
    navigation: {
        nextEl: '.swiper-button-next',
        prevEl: '.swiper-button-prev'
    },
    breakpoints: {
        768: { slidesPerView: 1.2 },
        1024: { slidesPerView: 2 }
    }
});

// Achievement Carousel 
const achSwiper = new Swiper('.achievements-carousel', {
    loop: true,
    grabCursor: true,
    centeredSlides: true,
    spaceBetween: 24,
    effect: 'coverflow',
    slidesPerView: 'auto',
    coverflowEffect: {
        rotate: 15,
        stretch: 0,
        depth: 120,
        modifier: 1,
        slideShadows: true
    },
    autoplay: {
        delay: 3000, 
        disableOnInteraction: false
    },
    pagination: {
        el: '.achievements-carousel .swiper-pagination',
        clickable: true
    },
    breakpoints: {
        768: { slidesPerView: 1.2 },
        1024: { slidesPerView: 2 }
    }
});

// Modal functionality for announcements
const modal = document.getElementById('announcementModal');
const modalTitle = document.getElementById('modalTitle');
const modalImage = document.getElementById('modalImage');
const modalContent = document.getElementById('modalContent');
const modalUser = document.getElementById('modalUser');
const modalDate = document.getElementById('modalDate');
const closeModal = document.querySelector('.close-modal');

// Add click event to all announcement cards
document.querySelectorAll('.AnnContent').forEach(card => {
    card.addEventListener('click', function() {
        const title = this.dataset.title;
        const content = this.dataset.content;
        const user = this.dataset.user;
        const date = this.dataset.date;
        const imgSrc = this.dataset.image;

        modalTitle.textContent = title;
        modalContent.textContent = content;
        modalUser.textContent = `Posted by: ${user}`;
        modalDate.textContent = `Published on ${date}`;

        if (imgSrc && imgSrc.trim() !== '') {
            modalImage.src = imgSrc;
            modalImage.style.display = 'block';
        } else {
            modalImage.style.display = 'none';
            modalImage.src = '';
        }

        modal.style.display = 'block';
        document.body.style.overflow = 'hidden';
    });
});

// Close modal
closeModal.addEventListener('click', () => {
    modal.style.display = 'none';
    document.body.style.overflow = 'auto';
});

window.addEventListener('click', (e) => {
    if (e.target === modal) {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
});
