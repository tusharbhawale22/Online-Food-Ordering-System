<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please login to continue']);
    exit;
}

$action = $_POST['action'] ?? '';
$userId = getCurrentUserId();
$conn = getDBConnection();

switch ($action) {
    case 'add':
        $itemId = intval($_POST['item_id'] ?? 0);
        $quantity = intval($_POST['quantity'] ?? 1);
        
        if ($itemId === 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid item']);
            exit;
        }
        
        // Check if item already in cart
        $stmt = $conn->prepare("SELECT cart_id, quantity FROM cart WHERE user_id = ? AND item_id = ?");
        $stmt->bind_param("ii", $userId, $itemId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Update quantity
            $row = $result->fetch_assoc();
            $newQuantity = $row['quantity'] + $quantity;
            $stmt->close();
            
            $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE cart_id = ?");
            $stmt->bind_param("ii", $newQuantity, $row['cart_id']);
            $stmt->execute();
        } else {
            // Insert new item
            $stmt->close();
            $stmt = $conn->prepare("INSERT INTO cart (user_id, item_id, quantity) VALUES (?, ?, ?)");
            $stmt->bind_param("iii", $userId, $itemId, $quantity);
            $stmt->execute();
        }
        
        $stmt->close();
        echo json_encode(['success' => true, 'message' => 'Item added to cart']);
        break;
        
    case 'update':
        $cartId = intval($_POST['cart_id'] ?? 0);
        $quantity = intval($_POST['quantity'] ?? 1);
        
        if ($quantity <= 0) {
            // Remove item if quantity is 0 or less
            $stmt = $conn->prepare("DELETE FROM cart WHERE cart_id = ? AND user_id = ?");
            $stmt->bind_param("ii", $cartId, $userId);
        } else {
            $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE cart_id = ? AND user_id = ?");
            $stmt->bind_param("iii", $quantity, $cartId, $userId);
        }
        
        $stmt->execute();
        $stmt->close();
        echo json_encode(['success' => true, 'message' => 'Cart updated']);
        break;
        
    case 'remove':
        $cartId = intval($_POST['cart_id'] ?? 0);
        
        $stmt = $conn->prepare("DELETE FROM cart WHERE cart_id = ? AND user_id = ?");
        $stmt->bind_param("ii", $cartId, $userId);
        $stmt->execute();
        $stmt->close();
        echo json_encode(['success' => true, 'message' => 'Item removed from cart']);
        break;
        
    case 'count':
        $stmt = $conn->prepare("SELECT SUM(quantity) as total FROM cart WHERE user_id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        echo json_encode(['success' => true, 'count' => intval($row['total'] ?? 0)]);
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

$conn->close();
?>
