<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = $_POST['action'] ?? '';
$userId = getCurrentUserId();

if ($action === 'verify') {
    $razorpayPaymentId = $_POST['razorpay_payment_id'] ?? '';
    $razorpayOrderId = $_POST['razorpay_order_id'] ?? '';
    
    if (!isset($_SESSION['pending_order'])) {
        echo json_encode(['success' => false, 'message' => 'No pending order found']);
        exit;
    }
    
    $pendingOrder = $_SESSION['pending_order'];
    $conn = getDBConnection();
    
    // Create order in database
    $stmt = $conn->prepare("INSERT INTO orders (order_number, user_id, restaurant_id, total_amount, delivery_address, delivery_locality, payment_method, payment_status, razorpay_order_id, razorpay_payment_id, order_status, discount_amount) VALUES (?, ?, ?, ?, ?, ?, 'razorpay', 'completed', ?, ?, 'placed', ?)");
    
    $stmt->bind_param(
        "siidssssd",
        $pendingOrder['order_number'],
        $userId,
        $pendingOrder['restaurant_id'],
        $pendingOrder['total_amount'],
        $pendingOrder['delivery_address'],
        $pendingOrder['delivery_locality'],
        $razorpayOrderId,
        $razorpayPaymentId,
        $pendingOrder['discount_amount']
    );
    
    if ($stmt->execute()) {
        $orderId = $conn->insert_id;
        $stmt->close();
        
        // Insert order items
        $itemStmt = $conn->prepare("INSERT INTO order_items (order_id, item_id, item_name, quantity, price) VALUES (?, ?, ?, ?, ?)");
        
        foreach ($pendingOrder['cart_items'] as $item) {
            $itemStmt->bind_param(
                "iisid",
                $orderId,
                $item['item_id'],
                $item['item_name'],
                $item['quantity'],
                $item['price']
            );
            $itemStmt->execute();
        }
        $itemStmt->close();
        
        // Clear cart
        $clearStmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
        $clearStmt->bind_param("i", $userId);
        $clearStmt->execute();
        $clearStmt->close();
        
        // Mark user as not new if applicable
        require_once __DIR__ . '/../includes/auth.php';
        markUserAsExisting($userId);
        
        // Clear pending order from session
        unset($_SESSION['pending_order']);
        
        $conn->close();
        
        echo json_encode(['success' => true, 'order_id' => $orderId, 'message' => 'Order placed successfully']);
    } else {
        $stmt->close();
        $conn->close();
        echo json_encode(['success' => false, 'message' => 'Failed to create order']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
?>
