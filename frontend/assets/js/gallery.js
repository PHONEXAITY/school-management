// Gallery functionality

function filterGallery(category) {
    const items = document.querySelectorAll('.gallery-item');
    const buttons = document.querySelectorAll('.gallery-filter-btn');

    // Reset all buttons
    buttons.forEach(btn => {
        btn.classList.remove('active', 'bg-blue-600', 'text-white');
        btn.classList.add('bg-gray-200', 'text-gray-700');
    });

    // Set active button
    const currentButton = event.target.closest('.gallery-filter-btn');
    if (currentButton) {
        currentButton.classList.remove('bg-gray-200', 'text-gray-700');
        currentButton.classList.add('active', 'bg-blue-600', 'text-white');
    }

    // Filter items
    items.forEach(item => {
        if (category === 'all' || item.classList.contains(category)) {
            item.style.display = 'block';
            item.style.animation = 'fadeInUp 0.5s ease-out';
        } else {
            item.style.display = 'none';
        }
    });
}