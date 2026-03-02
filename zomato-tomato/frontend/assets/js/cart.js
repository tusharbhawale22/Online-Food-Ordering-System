// Cart Management JavaScript

function addToCart(itemId, itemName) {
    // Check if user is logged in
    fetch('/zomato-tomato/backend/api/cart-handler.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=add&item_id=${itemId}&quantity=1`
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification(`${itemName} added to cart!`, 'success');
                updateCartCount();
            } else {
                if (data.message.includes('login')) {
                    window.location.href = '/zomato-tomato/frontend/pages/login.php?redirect=' + encodeURIComponent(window.location.href);
                } else {
                    showNotification(data.message, 'error');
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Failed to add item to cart', 'error');
        });
}

function updateQuantity(cartId, quantity) {
    if (quantity < 0) return;

    fetch('/zomato-tomato/backend/api/cart-handler.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=update&cart_id=${cartId}&quantity=${quantity}`
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload(); // Reload to update totals
            } else {
                showNotification(data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Failed to update cart', 'error');
        });
}

function removeFromCart(cartId) {
    if (!confirm('Remove this item from cart?')) return;

    fetch('/zomato-tomato/backend/api/cart-handler.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=remove&cart_id=${cartId}`
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                showNotification(data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Failed to remove item', 'error');
        });
}

function updateCartCount() {
    fetch('/zomato-tomato/backend/api/cart-handler.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=count'
    })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.count > 0) {
                // Update cart badge if exists
                const cartBadge = document.getElementById('cartBadge');
                if (cartBadge) {
                    cartBadge.textContent = data.count;
                    cartBadge.style.display = 'block';
                }
            }
        })
        .catch(error => console.error('Error:', error));
}

function showNotification(message, type = 'info') {
    // Create notification element
    const notification = document.createElement('div');
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 25px;
        background-color: ${type === 'success' ? '#48C479' : '#E23744'};
        color: white;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        z-index: 10000;
        animation: slideIn 0.3s ease;
    `;
    notification.textContent = message;

    document.body.appendChild(notification);

    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// Add CSS animations
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(400px);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(400px);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);

// Update cart count on page load
document.addEventListener('DOMContentLoaded', updateCartCount);
