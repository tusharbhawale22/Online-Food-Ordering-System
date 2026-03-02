<?php
require_once __DIR__ . '/../../backend/config/config.php';
require_once __DIR__ . '/../../backend/includes/auth.php';

requireLogin();

$orderId = intval($_GET['order_id'] ?? 0);
if ($orderId === 0) {
    redirect(PAGES_URL . '/orders.php');
}

$userId = getCurrentUserId();
$conn = getDBConnection();

// Fetch order details to show what is being reviewed
$stmt = $conn->prepare("SELECT o.*, r.name as restaurant_name, r.image_url 
                        FROM orders o 
                        JOIN restaurants r ON o.restaurant_id = r.restaurant_id 
                        WHERE o.order_id = ? AND o.user_id = ?");
$stmt->bind_param("ii", $orderId, $userId);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order) {
    redirect(PAGES_URL . '/orders.php');
}

// Check if already reviewed
$stmt = $conn->prepare("SELECT * FROM reviews WHERE order_id = ?");
$stmt->bind_param("i", $orderId);
$stmt->execute();
$existingReview = $stmt->get_result()->fetch_assoc();
$stmt->close();

require_once __DIR__ . '/../components/header.php';
?>

<style>
.review-page {
    padding: 60px 0;
    min-height: 80vh;
    background-color: var(--bg-light);
}

.review-card {
    background: white;
    max-width: 600px;
    margin: 0 auto;
    padding: 40px;
    border-radius: 12px;
    box-shadow: var(--shadow-md);
    text-align: center;
}

.restaurant-info {
    margin-bottom: 30px;
}

.restaurant-img {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    object-fit: cover;
    margin-bottom: 15px;
    border: 4px solid var(--bg-light);
}

.star-rating {
    display: flex;
    justify-content: center;
    gap: 10px;
    margin-bottom: 25px;
    flex-direction: row-reverse;
}

.star-rating input {
    display: none;
}

.star-rating label {
    font-size: 40px;
    color: #ddd;
    cursor: pointer;
    transition: color 0.2s;
}

.star-rating input:checked ~ label,
.star-rating label:hover,
.star-rating label:hover ~ label {
    color: #ffd700;
}

.comment-box {
    width: 100%;
    padding: 15px;
    border: 2px solid var(--border-color);
    border-radius: 8px;
    resize: vertical;
    min-height: 120px;
    margin-bottom: 25px;
    font-family: inherit;
}

.comment-box:focus {
    outline: none;
    border-color: var(--primary-color);
}

.review-submitted {
    text-align: center;
    padding: 40px;
}

.success-icon {
    font-size: 60px;
    margin-bottom: 20px;
    display: block;
}
</style>

<div class="review-page">
    <div class="container">
        <div class="review-card">
            <?php if ($existingReview): ?>
                <div class="review-submitted">
                    <span class="success-icon">✅</span>
                    <h2>Thanks for your feedback!</h2>
                    <p style="color: var(--text-secondary); margin: 15px 0;">You rated <strong><?php echo htmlspecialchars($order['restaurant_name']); ?></strong> <?php echo $existingReview['rating']; ?> stars.</p>
                    <div style="background: var(--bg-light); padding: 15px; border-radius: 8px; margin-top: 20px; font-style: italic;">
                        "<?php echo htmlspecialchars($existingReview['comment']); ?>"
                    </div>
                    <a href="<?php echo PAGES_URL; ?>/orders.php" class="btn btn-primary" style="margin-top: 30px;">Back to Orders</a>
                </div>
            <?php else: ?>
                <div class="restaurant-info">
                    <img src="<?php echo $order['image_url'] ? SITE_URL . '/' . $order['image_url'] : 'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?w=200&h=200&fit=crop'; ?>" alt="Restaurant" class="restaurant-img">
                    <h2>Rate your experience</h2>
                    <p style="color: var(--text-secondary);">How was the food from <strong><?php echo htmlspecialchars($order['restaurant_name']); ?></strong>?</p>
                </div>

                <form id="reviewForm" onsubmit="submitReview(event)">
                    <input type="hidden" name="order_id" value="<?php echo $orderId; ?>">
                    
                    <div class="star-rating">
                        <input type="radio" id="star5" name="rating" value="5" required><label for="star5">★</label>
                        <input type="radio" id="star4" name="rating" value="4"><label for="star4">★</label>
                        <input type="radio" id="star3" name="rating" value="3"><label for="star3">★</label>
                        <input type="radio" id="star2" name="rating" value="2"><label for="star2">★</label>
                        <input type="radio" id="star1" name="rating" value="1"><label for="star1">★</label>
                    </div>

                    <textarea name="comment" class="comment-box" placeholder="Write your review here... (Optional)"></textarea>

                    <button type="submit" class="btn btn-primary" style="width: 100%;">Submit Review</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function submitReview(e) {
    e.preventDefault();
    
    const form = e.target;
    const formData = new FormData(form);
    
    fetch('<?php echo API_URL; ?>/submit-review.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Review submitted successfully!');
            location.reload();
        } else {
            alert(data.message || 'Something went wrong');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to submit review');
    });
}
</script>

<?php
include __DIR__ . '/../components/footer.php';
?>
