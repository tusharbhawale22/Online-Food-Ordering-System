// Main JavaScript for Tomato

// Search functionality
const searchInput = document.getElementById('searchInput');
const searchBtn = document.getElementById('searchBtn');

if (searchBtn) {
    searchBtn.addEventListener('click', performSearch);
}

if (searchInput) {
    searchInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
            performSearch();
        }
    });
}

function performSearch() {
    const query = searchInput.value.trim();
    if (query) {
        window.location.href = `/zomato-tomato/frontend/pages/search.php?q=${encodeURIComponent(query)}`;
    }
}

// Location selector
const locationSelector = document.getElementById('locationSelector');
const selectedLocation = document.getElementById('selectedLocation');

if (locationSelector) {
    locationSelector.addEventListener('click', () => {
        const localities = [
            'Kolkata',
            'Southern Avenue',
            'Park Street',
            'Salt Lake',
            'Ballygunge',
            'Alipore',
            'Jadavpur',
            'Howrah',
            'Dum Dum'
        ];

        const currentIndex = localities.indexOf(selectedLocation.textContent);
        const nextIndex = (currentIndex + 1) % localities.length;
        selectedLocation.textContent = localities[nextIndex];

        // Update URL with locality filter if on search page
        if (window.location.pathname.includes('search.php')) {
            const url = new URL(window.location.href);
            url.searchParams.set('locality', localities[nextIndex]);
            window.location.href = url.toString();
        }
    });
}

// User profile dropdown
const userProfile = document.getElementById('userProfile');
const userDropdown = document.getElementById('userDropdown');

if (userProfile && userDropdown) {
    userProfile.addEventListener('click', () => {
        userDropdown.style.display = userDropdown.style.display === 'none' ? 'block' : 'none';
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', (e) => {
        if (!userProfile.contains(e.target)) {
            userDropdown.style.display = 'none';
        }
    });
}

// Smooth scroll for anchor links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth'
            });
        }
    });
});

// Image lazy loading
if ('IntersectionObserver' in window) {
    const imageObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                img.src = img.dataset.src;
                img.classList.remove('lazy');
                imageObserver.unobserve(img);
            }
        });
    });

    document.querySelectorAll('img.lazy').forEach(img => {
        imageObserver.observe(img);
    });
}

// Form validation helper
function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

function validatePhone(phone) {
    const re = /^[0-9]{10}$/;
    return re.test(phone.replace(/\s/g, ''));
}

// Add to favorites (placeholder)
function addToFavorites(restaurantId) {
    console.log('Adding restaurant to favorites:', restaurantId);
    showNotification('Added to favorites!', 'success');
}

// Share restaurant (placeholder)
function shareRestaurant(restaurantId, restaurantName) {
    if (navigator.share) {
        navigator.share({
            title: restaurantName,
            text: `Check out ${restaurantName} on Tomato!`,
            url: window.location.href
        }).catch(err => console.log('Error sharing:', err));
    } else {
        // Fallback: copy link to clipboard
        navigator.clipboard.writeText(window.location.href);
        showNotification('Link copied to clipboard!', 'success');
    }
}

// Initialize tooltips (if needed)
document.addEventListener('DOMContentLoaded', () => {
    // Add any initialization code here
    console.log('Tomato app initialized');
});
