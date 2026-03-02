<?php
require_once __DIR__ . '/../../backend/config/config.php';
require_once __DIR__ . '/../../backend/includes/auth.php';

requireLogin();

$orderId = intval($_GET['order_id'] ?? 0);

if ($orderId === 0) {
    redirect(SITE_URL . '/index.php');
}

$userId = getCurrentUserId();
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
$itemsStmt = $conn->prepare("SELECT oi.*, mi.item_name 
                             FROM order_items oi 
                             JOIN menu_items mi ON oi.item_id = mi.item_id 
                             WHERE oi.order_id = ?");
$itemsStmt->bind_param("i", $orderId);
$itemsStmt->execute();
$itemsResult = $itemsStmt->get_result();

$orderItems = [];
while ($item = $itemsResult->fetch_assoc()) {
    $orderItems[] = $item;
}
$itemsStmt->close();

// Get user details
$currentUser = getCurrentUser();

$conn->close();

require_once __DIR__ . '/../components/header.php';
?>

<style>
.bill-page {
    padding: 40px 0;
    background-color: var(--bg-light);
    min-height: 80vh;
}

.bill-container {
    max-width: 800px;
    margin: 0 auto;
    background: white;
    border-radius: 16px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
    overflow: hidden;
}

.bill-header {
    background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
    color: white;
    padding: 40px;
    text-align: center;
}

.success-icon {
    font-size: 80px;
    margin-bottom: 20px;
    animation: scaleIn 0.5s ease;
}

@keyframes scaleIn {
    from {
        transform: scale(0);
    }
    to {
        transform: scale(1);
    }
}

.bill-header h1 {
    font-size: 32px;
    margin-bottom: 10px;
}

.bill-header p {
    font-size: 16px;
    opacity: 0.9;
}

.bill-content {
    padding: 40px;
}

.bill-section {
    margin-bottom: 30px;
    padding-bottom: 30px;
    border-bottom: 2px dashed var(--border-color);
}

.bill-section:last-child {
    border-bottom: none;
    margin-bottom: 0;
}

.bill-section h3 {
    font-size: 18px;
    color: var(--text-primary);
    margin-bottom: 15px;
    font-weight: 600;
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 15px;
}

.info-item {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.info-label {
    font-size: 12px;
    color: var(--text-secondary);
    text-transform: uppercase;
    font-weight: 600;
}

.info-value {
    font-size: 16px;
    color: var(--text-primary);
}

.items-table {
    width: 100%;
    border-collapse: collapse;
}

.items-table th {
    background-color: var(--bg-light);
    padding: 12px;
    text-align: left;
    font-size: 14px;
    font-weight: 600;
    color: var(--text-primary);
}

.items-table td {
    padding: 12px;
    border-bottom: 1px solid var(--border-color);
    font-size: 14px;
}

.items-table tr:last-child td {
    border-bottom: none;
}

.bill-summary {
    background-color: var(--bg-light);
    padding: 20px;
    border-radius: 12px;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    font-size: 15px;
}

.summary-row.total {
    font-size: 20px;
    font-weight: 700;
    color: var(--primary-color);
    padding-top: 15px;
    margin-top: 15px;
    border-top: 2px solid var(--border-color);
}

.bill-actions {
    display: flex;
    gap: 15px;
    margin-top: 30px;
}

.bill-actions .btn {
    flex: 1;
}

@media print {
    .bill-actions,
    header,
    footer,
    .chatbot-widget {
        display: none !important;
    }
    
    .bill-page {
        padding: 0;
        background: white;
    }
    
    .bill-container {
        box-shadow: none;
    }
}

@media (max-width: 768px) {
    .info-grid {
        grid-template-columns: 1fr;
    }
    
    .bill-actions {
        flex-direction: column;
    }
}
</style>

<div class="bill-page">
    <div class="container">
        <div class="bill-container">
            <div class="bill-header">
                <div class="success-icon">✅</div>
                <h1>Payment Successful!</h1>
                <p>Your order has been placed successfully</p>
            </div>
            
            <div class="bill-content">
                <!-- Order Information -->
                <div class="bill-section">
                    <h3>📋 Order Details</h3>
                    <div class="info-grid">
                        <div class="info-item">
                            <span class="info-label">Order Number</span>
                            <span class="info-value"><?php echo htmlspecialchars($order['order_number']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Order Date</span>
                            <span class="info-value"><?php echo date('d M Y, h:i A', strtotime($order['created_at'] ?? $order['order_date'] ?? 'now')); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Restaurant</span>
                            <span class="info-value"><?php echo htmlspecialchars($order['restaurant_name']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Status</span>
                            <span class="info-value" style="color: var(--success-color);">✓ <?php echo ucfirst($order['order_status']); ?></span>
                        </div>
                    </div>
                </div>
                
                <!-- Customer Information -->
                <div class="bill-section">
                    <h3>👤 Customer Details</h3>
                    <div class="info-grid">
                        <div class="info-item">
                            <span class="info-label">Name</span>
                            <span class="info-value"><?php echo htmlspecialchars($currentUser['full_name']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Phone</span>
                            <span class="info-value"><?php echo htmlspecialchars($currentUser['phone']); ?></span>
                        </div>
                        <div class="info-item" style="grid-column: 1 / -1;">
                            <span class="info-label">Delivery Address</span>
                            <span class="info-value"><?php echo htmlspecialchars($order['delivery_address']); ?></span>
                        </div>
                    </div>
                </div>
                
                <!-- Order Items -->
                <div class="bill-section">
                    <h3>🍽️ Order Items</h3>
                    <table class="items-table">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th style="text-align: center;">Qty</th>
                                <th style="text-align: right;">Price</th>
                                <th style="text-align: right;">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orderItems as $item): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                                    <td style="text-align: center;"><?php echo $item['quantity']; ?></td>
                                    <td style="text-align: right;"><?php echo formatCurrency($item['price']); ?></td>
                                    <td style="text-align: right;"><?php echo formatCurrency($item['price'] * $item['quantity']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Bill Summary -->
                <div class="bill-section">
                    <h3>💰 Payment Summary</h3>
                    <div class="bill-summary">
                        <?php
                        $subtotal = $order['total_amount'] - 40 - ($order['total_amount'] - 40) * 0.05 + $order['discount_amount'];
                        $deliveryFee = 40;
                        $taxes = ($order['total_amount'] - 40 - $order['discount_amount']) * 0.05;
                        ?>
                        <div class="summary-row">
                            <span>Subtotal</span>
                            <span><?php echo formatCurrency($subtotal); ?></span>
                        </div>
                        <div class="summary-row">
                            <span>Delivery Fee</span>
                            <span><?php echo formatCurrency($deliveryFee); ?></span>
                        </div>
                        <div class="summary-row">
                            <span>Taxes & Charges (5%)</span>
                            <span><?php echo formatCurrency($taxes); ?></span>
                        </div>
                        <?php if ($order['discount_amount'] > 0): ?>
                            <div class="summary-row" style="color: var(--success-color);">
                                <span>Discount</span>
                                <span>-<?php echo formatCurrency($order['discount_amount']); ?></span>
                            </div>
                        <?php endif; ?>
                        <div class="summary-row total">
                            <span>Total Paid</span>
                            <span><?php echo formatCurrency($order['total_amount']); ?></span>
                        </div>
                    </div>
                </div>
                
                <!-- Actions -->
                <div class="bill-actions">
                    <button onclick="window.print()" class="btn btn-outline">🖨️ Print Bill</button>
                    <a href="<?php echo PAGES_URL; ?>/orders.php" class="btn btn-primary">View All Orders</a>
                    <a href="<?php echo PAGES_URL; ?>/index.php" class="btn btn-outline">Continue Shopping</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
include __DIR__ . '/../components/chatbot-widget.php';
include __DIR__ . '/../components/footer.php';
?>
