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

$orderId = intval($_POST['order_id'] ?? 0);
$rating = intval($_POST['rating'] ?? 0);
$comment = sanitizeInput($_POST['comment'] ?? '');

// Validation
if ($orderId <= 0 || $rating < 1 || $rating > 5) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

// Verify order belongs to user and is completed
$stmt = $conn->prepare("SELECT order_id, restaurant_id FROM orders WHERE order_id = ? AND user_id = ? AND order_status = 'delivered'");
// Since we don't have a 'delivered' status flow yet (it defaults to 'placed' then usually manually updated, but for this demo let's allow reviewing any order that exists for the user, or maybe check if payment_status is completed)
// Actually, looking at process-order.php, order_status is 'placed' and payment_status is 'completed'. Let's check payment_status.
$stmt = $conn->prepare("SELECT order_id, restaurant_id FROM orders WHERE order_id = ? AND user_id = ? AND payment_status = 'completed'");
$stmt->bind_param("ii", $orderId, $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Order not found or not eligible for review']);
    exit;
}

$order = $result->fetch_assoc();
$restaurantId = $order['restaurant_id'];
$stmt->close();

// Check if already reviewed
$stmt = $conn->prepare("SELECT review_id FROM reviews WHERE order_id = ?");
$stmt->bind_param("i", $orderId);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'You have already reviewed this order']);
    exit;
}
$stmt->close();

// Insert review
$stmt = $conn->prepare("INSERT INTO reviews (order_id, user_id, restaurant_id, rating, comment) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("iiiis", $orderId, $userId, $restaurantId, $rating, $comment);

if ($stmt->execute()) {
    // Update restaurant rating (optional but good feature)
    // Calculate new average
    $avgStmt = $conn->prepare("SELECT AVG(rating) as avg_rating FROM reviews WHERE restaurant_id = ?");
    $avgStmt->bind_param("i", $restaurantId);
    $avgStmt->execute();
    $avgResult = $avgStmt->get_result();
    $newRating = $avgResult->fetch_assoc()['avg_rating'];
    $avgStmt->close();
    
    // Update restaurant table
    if ($newRating) {
        $updateStmt = $conn->prepare("UPDATE restaurants SET rating = ? WHERE restaurant_id = ?");
        $updateStmt->bind_param("di", $newRating, $restaurantId);
        $updateStmt->execute();
        $updateStmt->close();
    }
    
    echo json_encode(['success' => true, 'message' => 'Review submitted successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to submit review']);
}

$stmt->close();
$conn->close();
?>
