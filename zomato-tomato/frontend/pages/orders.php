<?php
require_once __DIR__ . '/../../backend/config/config.php';
require_once __DIR__ . '/../../backend/includes/auth.php';

requireLogin();

$userId = getCurrentUserId();
$conn = getDBConnection();

// Fetch all orders
$ordersQuery = "SELECT o.*, r.name as restaurant_name, r.locality as restaurant_locality,
                (SELECT COUNT(*) FROM order_items WHERE order_id = o.order_id) as item_count,
                (SELECT rating FROM reviews WHERE order_id = o.order_id LIMIT 1) as my_rating
                FROM orders o
                JOIN restaurants r ON o.restaurant_id = r.restaurant_id
                WHERE o.user_id = ?
                ORDER BY o.created_at DESC";

$stmt = $conn->prepare($ordersQuery);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

require_once __DIR__ . '/../components/header.php';
?>

<style>
.orders-page {
    padding: 40px 0;
    background-color: var(--bg-light);
    min-height: 70vh;
}

.order-card {
    background-color: white;
    border-radius: 12px;
    padding: 25px;
    margin-bottom: 20px;
    box-shadow: var(--shadow-sm);
    transition: all 0.3s ease;
}

.order-card:hover {
    box-shadow: var(--shadow-md);
}

.order-header {
    display: flex;
    justify-content: space-between;
    align-items: start;
    margin-bottom: 20px;
    padding-bottom: 20px;
    border-bottom: 1px solid var(--border-color);
}

.order-restaurant {
    display: flex;
    align-items: center;
    gap: 15px;
}

.restaurant-thumb {
    width: 60px;
    height: 60px;
    border-radius: 8px;
    background-color: var(--bg-light);
    overflow: hidden;
}

.restaurant-thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.order-status {
    padding: 8px 16px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 600;
}

.order-status.delivered {
    background-color: #e6f7ed;
    color: var(--success-color);
}

.order-status.placed {
    background-color: #fff4e6;
    color: #f59e0b;
}

.order-details {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
}

.order-detail-item {
    font-size: 14px;
}

.detail-label {
    color: var(--text-light);
    font-size: 12px;
    margin-bottom: 5px;
}

.detail-value {
    color: var(--text-primary);
    font-weight: 500;
}

.order-actions {
    display: flex;
    gap: 10px;
}

.btn-small {
    padding: 8px 16px;
    font-size: 13px;
    border-radius: 6px;
}
</style>

<div class="orders-page">
    <div class="container">
        <h1 class="section-title">Order History</h1>
        
        <?php if ($result->num_rows === 0): ?>
            <div style="text-align: center; padding: 80px 20px; background-color: white; border-radius: 12px;">
                <div style="font-size: 80px; margin-bottom: 20px;">📦</div>
                <h2>No orders yet</h2>
                <p style="color: var(--text-secondary); margin: 15px 0 25px;">Start ordering delicious food!</p>
                <a href="<?php echo PAGES_URL; ?>/index.php" class="btn btn-primary">Browse Restaurants</a>
            </div>
        <?php else: ?>
            <?php while ($order = $result->fetch_assoc()): ?>
                <div class="order-card">
                    <div class="order-header">
                        <div class="order-restaurant">
                            <div class="restaurant-thumb">
                                <img src="https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?w=100&h=100&fit=crop" alt="Restaurant">
                            </div>
                            <div>
                                <h3 style="font-size: 18px; margin-bottom: 5px;">
                                    <?php echo htmlspecialchars($order['restaurant_name']); ?>
                                </h3>
                                <p style="color: var(--text-secondary); font-size: 14px;">
                                    <?php echo htmlspecialchars($order['restaurant_locality']); ?>
                                </p>
                            </div>
                        </div>
                        <div class="order-status <?php echo strtolower($order['order_status']); ?>">
                            <?php echo ucfirst($order['order_status']); ?>
                        </div>
                    </div>
                    
                    <div class="order-details">
                        <div class="order-detail-item">
                            <div class="detail-label">ORDER NUMBER</div>
                            <div class="detail-value"><?php echo htmlspecialchars($order['order_number']); ?></div>
                        </div>
                        
                        <div class="order-detail-item">
                            <div class="detail-label">TOTAL AMOUNT</div>
                            <div class="detail-value" style="color: var(--primary-color); font-size: 16px;">
                                <?php echo formatCurrency($order['total_amount']); ?>
                            </div>
                        </div>
                        
                        <div class="order-detail-item">
                            <div class="detail-label">ITEMS</div>
                            <div class="detail-value"><?php echo $order['item_count']; ?> items</div>
                        </div>
                        
                        <div class="order-detail-item">
                            <div class="detail-label">ORDERED ON</div>
                            <div class="detail-value"><?php echo date('d M Y, h:i A', strtotime($order['created_at'])); ?></div>
                        </div>
                    </div>
                    
                    <div class="order-actions">
                        <a href="<?php echo PAGES_URL; ?>/bill.php?order_id=<?php echo $order['order_id']; ?>" 
                           class="btn btn-outline btn-small">View Details</a>
                         <a href="<?php echo PAGES_URL; ?>/restaurant.php?id=<?php echo $order['restaurant_id']; ?>" 
                            class="btn btn-primary btn-small">Reorder</a>
                            
                         <?php if (isset($order['my_rating']) && $order['my_rating']): ?>
                             <a href="<?php echo PAGES_URL; ?>/review.php?order_id=<?php echo $order['order_id']; ?>" 
                                class="btn btn-outline btn-small" style="color: #f59e0b; border-color: #f59e0b;">
                                ★ <?php echo $order['my_rating']; ?>
                             </a>
                         <?php else: ?>
                             <a href="<?php echo PAGES_URL; ?>/review.php?order_id=<?php echo $order['order_id']; ?>" 
                                class="btn btn-outline btn-small">Rate Food</a>
                         <?php endif; ?>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php endif; ?>
    </div>
</div>

<?php
$stmt->close();
$conn->close();
include __DIR__ . '/../components/chatbot-widget.php';
include __DIR__ . '/../components/footer.php';
?>
