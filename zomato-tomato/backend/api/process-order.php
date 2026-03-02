<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$userId = getCurrentUserId();
$conn = getDBConnection();

// Get form data
$name = sanitizeInput($_POST['name'] ?? '');
$phone = sanitizeInput($_POST['phone'] ?? '');
$address = sanitizeInput($_POST['address'] ?? '');
$locality = sanitizeInput($_POST['locality'] ?? '');
$pincode = sanitizeInput($_POST['pincode'] ?? '');
$offerCode = sanitizeInput($_POST['offer_code'] ?? '');

// Fetch cart items
$cartQuery = "SELECT c.*, mi.item_name, mi.price, r.restaurant_id
              FROM cart c
              JOIN menu_items mi ON c.item_id = mi.item_id
              JOIN restaurants r ON mi.restaurant_id = r.restaurant_id
              WHERE c.user_id = ?";

$stmt = $conn->prepare($cartQuery);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

$cartItems = [];
$subtotal = 0;
$restaurantId = null;

while ($item = $result->fetch_assoc()) {
    $cartItems[] = $item;
    $subtotal += $item['price'] * $item['quantity'];
    if (!$restaurantId) {
        $restaurantId = $item['restaurant_id'];
    }
}
$stmt->close();

if (empty($cartItems)) {
    echo json_encode(['success' => false, 'message' => 'Cart is empty']);
    exit;
}

$deliveryFee = 40.00;
$taxes = $subtotal * 0.05;
$discount = 0;

// Apply offer if provided
if ($offerCode) {
    $stmt = $conn->prepare("SELECT * FROM offers WHERE offer_code = ? AND is_active = TRUE");
    $stmt->bind_param("s", $offerCode);
    $stmt->execute();
    $offerResult = $stmt->get_result();
    
    if ($offerResult->num_rows > 0) {
        $offer = $offerResult->fetch_assoc();
        if ($offer['discount_type'] === 'percentage') {
            $discount = ($subtotal * $offer['discount_value']) / 100;
            if ($offer['max_discount'] && $discount > $offer['max_discount']) {
                $discount = $offer['max_discount'];
            }
        } else {
            $discount = $offer['discount_value'];
        }
    }
    $stmt->close();
}

$total = $subtotal + $deliveryFee + $taxes - $discount;

// Generate order number
$orderNumber = 'ORD' . strtoupper(uniqid()) . time();

// Create order
$fullAddress = "$address, $locality, $pincode";
$stmt = $conn->prepare("INSERT INTO orders (order_number, user_id, restaurant_id, total_amount, delivery_address, delivery_locality, payment_method, payment_status, order_status, discount_amount) VALUES (?, ?, ?, ?, ?, ?, 'cash', 'completed', 'placed', ?)");
$stmt->bind_param("siidssd", $orderNumber, $userId, $restaurantId, $total, $fullAddress, $locality, $discount);

if ($stmt->execute()) {
    $orderId = $stmt->insert_id;
    $stmt->close();
    
    // Insert order items
    $itemStmt = $conn->prepare("INSERT INTO order_items (order_id, item_id, quantity, price) VALUES (?, ?, ?, ?)");
    
    foreach ($cartItems as $item) {
        $itemStmt->bind_param("iiid", $orderId, $item['item_id'], $item['quantity'], $item['price']);
        $itemStmt->execute();
    }
    $itemStmt->close();
    
    // Clear cart
    $clearStmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
    $clearStmt->bind_param("i", $userId);
    $clearStmt->execute();
    $clearStmt->close();
    
    // Redirect to bill page
    header("Location: " . PAGES_URL . "/bill.php?order_id=" . $orderId);
    exit;
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to create order']);
}

$conn->close();
?>
