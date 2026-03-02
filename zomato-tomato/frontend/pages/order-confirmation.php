<?php
require_once __DIR__ . '/../../backend/config/config.php';
require_once __DIR__ . '/../../backend/includes/auth.php';

requireLogin();

$orderId = intval($_GET['order'] ?? 0);
$userId = getCurrentUserId();

if ($orderId === 0) {
    redirect(SITE_URL . '/index.php');
}

$conn = getDBConnection();

// Fetch order details
$stmt = $conn->prepare("SELECT o.*, r.name as restaurant_name, r.locality as restaurant_locality
                        FROM orders o
                        JOIN restaurants r ON o.restaurant_id = r.restaurant_id
                        WHERE o.order_id = ? AND o.user_id = ?");
$stmt->bind_param("ii", $orderId, $userId);
$stmt->execute();
$result = $stmt->get_result();
$order = $result->fetch_assoc();
$stmt->close();

if (!$order) {
    redirect(SITE_URL . '/index.php');
}

// Fetch order items
$itemsStmt = $conn->prepare("SELECT * FROM order_items WHERE order_id = ?");
$itemsStmt->bind_param("i", $orderId);
$itemsStmt->execute();
$itemsResult = $itemsStmt->get_result();
$orderItems = [];
while ($item = $itemsResult->fetch_assoc()) {
    $orderItems[] = $item;
}
$itemsStmt->close();
$conn->close();

require_once __DIR__ . '/../components/header.php';
?>

<style>
.confirmation-page {
    padding: 60px 0;
    background-color: var(--bg-light);
    min-height: 70vh;
}

.confirmation-box {
    max-width: 700px;
    margin: 0 auto;
    background-color: white;
    padding: 50px;
    border-radius: 12px;
    text-align: center;
}

.success-icon {
    width: 100px;
    height: 100px;
    background: linear-gradient(135deg, var(--success-color), #36a85e);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 30px;
    font-size: 50px;
    color: white;
}

.order-summary-box {
    background-color: var(--bg-light);
    padding: 30px;
    border-radius: 12px;
    margin: 30px 0;
    text-align: left;
}

.summary-header {
    display: flex;
    justify-content: space-between;
    align-items: start;
    margin-bottom: 20px;
    padding-bottom: 20px;
    border-bottom: 2px solid var(--border-color);
}

.order-item {
    display: flex;
    justify-content: space-between;
    padding: 10px 0;
    font-size: 14px;
}
</style>

<div class="confirmation-page">
    <div class="container">
        <div class="confirmation-box">
            <div class="success-icon">✓</div>
            
            <h1 style="font-size: 32px; margin-bottom: 15px;">Your order has been accepted</h1>
            <p style="color: var(--text-secondary); font-size: 16px;">
                We've received your order and it's being prepared!
            </p>
            
            <div class="order-summary-box">
                <div class="summary-header">
                    <div>
                        <h2 style="font-size: 20px; margin-bottom: 8px;"><?php echo htmlspecialchars($order['restaurant_name']); ?></h2>
                        <p style="color: var(--text-secondary); font-size: 14px;">
                            <?php echo htmlspecialchars($order['restaurant_locality']); ?>
                        </p>
                    </div>
                    <div style="text-align: right;">
                        <div style="font-size: 12px; color: var(--text-light); margin-bottom: 5px;">ORDER NUMBER</div>
                        <div style="font-weight: 700; color: var(--primary-color);">
                            <?php echo htmlspecialchars($order['order_number']); ?>
                        </div>
                    </div>
                </div>
                
                <h3 style="margin-bottom: 15px; font-size: 16px;">Order Items</h3>
                <?php foreach ($orderItems as $item): ?>
                    <div class="order-item">
                        <span>
                            <span style="color: var(--text-light);"><?php echo $item['quantity']; ?>x</span>
                            <?php echo htmlspecialchars($item['item_name']); ?>
                        </span>
                        <span style="font-weight: 600;">
                            <?php echo formatCurrency($item['price'] * $item['quantity']); ?>
                        </span>
                    </div>
                <?php endforeach; ?>
                
                <div style="border-top: 2px solid var(--border-color); margin-top: 15px; padding-top: 15px;">
                    <div class="order-item" style="font-size: 16px; font-weight: 700;">
                        <span>Total Amount</span>
                        <span style="color: var(--primary-color);">
                            <?php echo formatCurrency($order['total_amount']); ?>
                        </span>
                    </div>
                </div>
                
                <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid var(--border-color);">
                    <div style="font-size: 12px; color: var(--text-light); margin-bottom: 5px;">DELIVERY ADDRESS</div>
                    <div style="font-size: 14px;">
                        <?php echo htmlspecialchars($order['delivery_address']); ?>
                    </div>
                </div>
                
                <div style="margin-top: 15px;">
                    <div style="font-size: 12px; color: var(--text-light); margin-bottom: 5px;">ORDERED ON</div>
                    <div style="font-size: 14px;">
                        <?php echo date('d M Y, h:i A', strtotime($order['created_at'])); ?>
                    </div>
                </div>
            </div>
            
            <div style="display: flex; gap: 15px; justify-content: center; margin-top: 30px;">
                <a href="<?php echo PAGES_URL; ?>/index.php" class="btn btn-primary" style="padding: 15px 40px; font-size: 16px;">Continue Shopping</a>
                <a href="<?php echo PAGES_URL; ?>/orders.php" class="btn btn-outline">View All Orders</a>
            </div>
            
            <p style="color: var(--text-secondary); font-size: 14px; margin-top: 30px;">
                📱 You'll receive updates about your order via SMS
            </p>
        </div>
    </div>
</div>

<?php
include __DIR__ . '/../components/chatbot-widget.php';
include __DIR__ . '/../components/footer.php';
?>
