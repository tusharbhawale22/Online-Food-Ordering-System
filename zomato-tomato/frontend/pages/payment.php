<?php
require_once __DIR__ . '/../../backend/config/config.php';
require_once __DIR__ . '/../../backend/includes/auth.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(SITE_URL . '/cart.php');
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

// Fetch cart items and calculate total
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
    redirect(SITE_URL . '/cart.php');
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
$totalInPaise = intval($total * 100); // Razorpay requires amount in paise

// Generate order number
$orderNumber = 'order_' . uniqid() . time();

// Store order details in session for verification
$_SESSION['pending_order'] = [
    'order_number' => $orderNumber,
    'restaurant_id' => $restaurantId,
    'total_amount' => $total,
    'delivery_address' => "$address, $locality, $pincode",
    'delivery_locality' => $locality,
    'discount_amount' => $discount,
    'cart_items' => $cartItems,
    'customer_name' => $name,
    'customer_phone' => $phone
];

require_once __DIR__ . '/../components/header.php';
?>

<script src="https://checkout.razorpay.com/v1/checkout.js"></script>

<style>
.payment-page {
    padding: 60px 0;
    background-color: var(--bg-light);
    min-height: 70vh;
}

.payment-box {
    max-width: 600px;
    margin: 0 auto;
    background-color: white;
    padding: 40px;
    border-radius: 12px;
    text-align: center;
}

.payment-icon {
    font-size: 80px;
    margin-bottom: 20px;
}

.payment-amount {
    font-size: 42px;
    font-weight: 700;
    color: var(--primary-color);
    margin: 20px 0;
}

.payment-details {
    text-align: left;
    background-color: var(--bg-light);
    padding: 20px;
    border-radius: 8px;
    margin: 25px 0;
}

.detail-row {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    font-size: 14px;
}
</style>

<div class="payment-page">
    <div class="container">
        <div class="payment-box">
            <div class="payment-icon">💳</div>
            <h1>Complete Your Payment</h1>
            <p style="color: var(--text-secondary); margin-top: 10px;">
                You're just one step away from your delicious meal!
            </p>
            
            <div class="payment-amount">
                <?php echo formatCurrency($total); ?>
            </div>
            
            <div class="payment-details">
                <div class="detail-row">
                    <span>Order Number:</span>
                    <strong><?php echo htmlspecialchars($orderNumber); ?></strong>
                </div>
                <div class="detail-row">
                    <span>Delivery to:</span>
                    <strong><?php echo htmlspecialchars($name); ?></strong>
                </div>
                <div class="detail-row">
                    <span>Address:</span>
                    <strong><?php echo htmlspecialchars($locality); ?></strong>
                </div>
            </div>
            
            <button id="payButton" class="btn btn-primary" style="width: 100%; padding: 15px; font-size: 16px;">
                Pay with Razorpay
            </button>
            
            <p style="color: var(--text-light); font-size: 12px; margin-top: 20px;">
                🔒 Secure payment powered by Razorpay
            </p>
        </div>
    </div>
</div>

<script>
document.getElementById('payButton').addEventListener('click', function() {
    const options = {
        key: '<?php echo RAZORPAY_KEY_ID; ?>',
        amount: <?php echo $totalInPaise; ?>,
        currency: 'INR',
        name: 'Tomato',
        description: 'Food Order Payment',
        order_id: '<?php echo $orderNumber; ?>',
        handler: function(response) {
            // Payment successful
            fetch('<?php echo SITE_URL; ?>/api/payment-handler.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=verify&razorpay_payment_id=${response.razorpay_payment_id}&razorpay_order_id=${response.razorpay_order_id}&razorpay_signature=${response.razorpay_signature || ''}`
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    window.location.href = '<?php echo SITE_URL; ?>/order-confirmation.php?order=' + data.order_id;
                } else {
                    alert('Payment verification failed. Please contact support.');
                }
            });
        },
        prefill: {
            name: '<?php echo htmlspecialchars($name); ?>',
            contact: '<?php echo htmlspecialchars($phone); ?>'
        },
        theme: {
            color: '#E23744'
        },
        modal: {
            ondismiss: function() {
                alert('Payment cancelled. You can try again.');
            }
        }
    };
    
    const rzp = new Razorpay(options);
    rzp.open();
});
</script>

<?php
$conn->close();
include __DIR__ . '/../components/chatbot-widget.php';
include __DIR__ . '/../components/footer.php';
?>
