<?php
require_once __DIR__ . '/../../backend/config/config.php';
require_once __DIR__ . '/../../backend/includes/auth.php';

requireLogin();

$userId = getCurrentUserId();
$currentUser = getCurrentUser();
$conn = getDBConnection();

// Fetch cart items
$cartQuery = "SELECT c.*, mi.item_name, mi.price, r.restaurant_id, r.name as restaurant_name
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


// $conn->close(); // Moved to end of file

$deliveryFee = 40.00;
$taxes = $subtotal * 0.05;
$discount = 0;
$total = $subtotal + $deliveryFee + $taxes - $discount;

require_once __DIR__ . '/../components/header.php';
?>

<style>
.checkout-page {
    padding: 40px 0;
    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
    min-height: 80vh;
}

.checkout-layout {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 30px;
}

.checkout-section {
    background: white;
    padding: 35px;
    border-radius: 16px;
    margin-bottom: 20px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.checkout-section:hover {
    transform: translateY(-2px);
    box-shadow: 0 15px 40px rgba(0, 0, 0, 0.12);
}

.checkout-section h2 {
    font-size: 24px;
    margin-bottom: 25px;
    color: #2d3748;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 12px;
}

.checkout-section h2::before {
    content: '📍';
    font-size: 28px;
}

.form-group {
    position: relative;
    margin-bottom: 20px;
}

.form-label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #4a5568;
    font-size: 14px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.form-input {
    width: 100%;
    padding: 14px 16px;
    border: 2px solid #e2e8f0;
    border-radius: 10px;
    font-size: 15px;
    transition: all 0.3s ease;
    background-color: #f7fafc;
}

.form-input:focus {
    outline: none;
    border-color: var(--primary-color);
    background-color: white;
    box-shadow: 0 0 0 3px rgba(226, 55, 68, 0.1);
}

.form-input::placeholder {
    color: #a0aec0;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
}

.input-icon {
    position: absolute;
    right: 16px;
    top: 42px;
    font-size: 18px;
    opacity: 0.5;
}

.offer-card {
    border: 2px dashed var(--border-color);
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 10px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.offer-card:hover {
    border-color: var(--primary-color);
    background-color: rgba(226, 55, 68, 0.05);
}

.offer-card.selected {
    border-color: var(--primary-color);
    background-color: rgba(226, 55, 68, 0.1);
    border-style: solid;
}

.offer-code {
    font-weight: 700;
    color: var(--primary-color);
    font-size: 16px;
}

@media (max-width: 768px) {
    .checkout-layout {
        grid-template-columns: 1fr;
    }
    .form-row {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="checkout-page">
    <div class="container">
        <h1 class="section-title">Checkout</h1>
        
        <form id="checkoutForm" method="POST" action="<?php echo API_URL; ?>/process-order.php">
            <div class="checkout-layout">
                <div>
                    <div class="checkout-section">
                        <h2>Delivery Address</h2>
                        
                        <div class="form-group">
                            <label class="form-label">👤 Full Name</label>
                            <input type="text" name="name" class="form-input" required 
                                   value="<?php echo htmlspecialchars($currentUser['full_name']); ?>"
                                   placeholder="Enter your full name">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">📱 Phone Number</label>
                            <input type="tel" name="phone" class="form-input" required 
                                   value="<?php echo htmlspecialchars($currentUser['phone'] ?? ''); ?>"
                                   placeholder="Enter 10-digit mobile number">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">🏠 Complete Address</label>
                            <textarea name="address" class="form-input" rows="3" required 
                                      placeholder="House No., Building Name, Street" style="resize: vertical;"></textarea>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">📍 Locality</label>
                                <select name="locality" class="form-input" required>
                                    <option value="">Select locality</option>
                                    <option value="Southern Avenue" <?php echo ($currentUser['locality'] === 'Southern Avenue') ? 'selected' : ''; ?>>Southern Avenue</option>
                                    <option value="Park Street" <?php echo ($currentUser['locality'] === 'Park Street') ? 'selected' : ''; ?>>Park Street</option>
                                    <option value="Salt Lake" <?php echo ($currentUser['locality'] === 'Salt Lake') ? 'selected' : ''; ?>>Salt Lake</option>
                                    <option value="Ballygunge" <?php echo ($currentUser['locality'] === 'Ballygunge') ? 'selected' : ''; ?>>Ballygunge</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">📮 Pincode</label>
                                <input type="text" name="pincode" class="form-input" required 
                                       pattern="[0-9]{6}" placeholder="700001" maxlength="6">
                            </div>
                        </div>
                    </div>
                    

                </div>
                
                <div>
                    <div class="cart-summary">
                        <h3 style="margin-bottom: 20px;">Order Summary</h3>
                        
                        <?php foreach ($cartItems as $item): ?>
                            <div class="summary-row" style="font-size: 14px; padding: 8px 0;">
                                <span><?php echo $item['quantity']; ?>x <?php echo htmlspecialchars($item['item_name']); ?></span>
                                <span><?php echo formatCurrency($item['price'] * $item['quantity']); ?></span>
                            </div>
                        <?php endforeach; ?>
                        
                        <hr style="margin: 15px 0; border: none; border-top: 1px solid var(--border-color);">
                        
                        <div class="summary-row">
                            <span>Subtotal</span>
                            <span id="subtotal"><?php echo formatCurrency($subtotal); ?></span>
                        </div>
                        
                        <div class="summary-row">
                            <span>Delivery Fee</span>
                            <span><?php echo formatCurrency($deliveryFee); ?></span>
                        </div>
                        
                        <div class="summary-row">
                            <span>Taxes & Charges</span>
                            <span id="taxes"><?php echo formatCurrency($taxes); ?></span>
                        </div>
                        

                        
                        <div class="summary-row summary-total">
                            <span>Total</span>
                            <span id="total"><?php echo formatCurrency($total); ?></span>
                        </div>
                        
                        <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 20px;">
                            Proceed to Payment
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
let subtotal = <?php echo $subtotal; ?>;
let deliveryFee = <?php echo $deliveryFee; ?>;
let taxes = <?php echo $taxes; ?>;
let discount = 0;

</script>

<?php
include __DIR__ . '/../components/chatbot-widget.php';
include __DIR__ . '/../components/footer.php';

if (isset($conn)) {
    $conn->close();
}
?>
