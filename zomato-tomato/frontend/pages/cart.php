<?php
require_once __DIR__ . '/../../backend/config/config.php';
require_once __DIR__ . '/../../backend/includes/auth.php';

requireLogin();

$userId = getCurrentUserId();
$conn = getDBConnection();

// Fetch cart items with details
$cartQuery = "SELECT c.*, mi.item_name, mi.price, mi.is_veg, r.restaurant_id, r.name as restaurant_name, r.locality
              FROM cart c
              JOIN menu_items mi ON c.item_id = mi.item_id
              JOIN restaurants r ON mi.restaurant_id = r.restaurant_id
              WHERE c.user_id = ?
              ORDER BY c.added_at DESC";

$stmt = $conn->prepare($cartQuery);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

$cartItems = [];
$subtotal = 0;
$restaurantId = null;
$restaurantName = '';
$restaurantLocality = '';

while ($item = $result->fetch_assoc()) {
    $cartItems[] = $item;
    $subtotal += $item['price'] * $item['quantity'];
    if (!$restaurantId) {
        $restaurantId = $item['restaurant_id'];
        $restaurantName = $item['restaurant_name'];
        $restaurantLocality = $item['locality'];
    }
}

$stmt->close();
// $conn->close(); // Moved to end of file

$deliveryFee = 40.00;
$taxes = $subtotal * 0.05; // 5% tax
$total = $subtotal + $deliveryFee + $taxes;

require_once __DIR__ . '/../components/header.php';
?>

<style>
.cart-page {
    padding: 40px 0;
    background-color: var(--bg-light);
    min-height: 70vh;
}

.cart-layout {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 30px;
}

.empty-cart {
    text-align: center;
    padding: 80px 20px;
    background-color: white;
    border-radius: 12px;
}

.empty-cart-icon {
    font-size: 80px;
    margin-bottom: 20px;
}

@media (max-width: 768px) {
    .cart-layout {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="cart-page">
    <div class="container">
        <h1 class="section-title">Your Cart</h1>
        
        <?php if (empty($cartItems)): ?>
            <div class="empty-cart">
                <div class="empty-cart-icon">🛒</div>
                <h2>Your cart is empty</h2>
                <p style="color: var(--text-secondary); margin: 15px 0 25px;">Add some delicious items to get started!</p>
                <a href="<?php echo PAGES_URL; ?>/index.php" class="btn btn-primary">Browse Restaurants</a>
            </div>
        <?php else: ?>
            <div class="cart-layout">
                <div class="cart-items">
                    <div style="background-color: white; padding: 20px; border-radius: 12px; margin-bottom: 15px;">
                        <h3 style="margin-bottom: 5px;"><?php echo htmlspecialchars($restaurantName); ?></h3>
                        <p style="color: var(--text-secondary); font-size: 14px;"><?php echo htmlspecialchars($restaurantLocality); ?></p>
                    </div>
                    
                    <?php foreach ($cartItems as $item): ?>
                        <div class="cart-item">
                            <div>
                                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                                    <div class="veg-indicator <?php echo $item['is_veg'] ? 'veg' : 'non-veg'; ?>">
                                        <div class="veg-dot"></div>
                                    </div>
                                    <h3 class="menu-item-name"><?php echo htmlspecialchars($item['item_name']); ?></h3>
                                </div>
                                <p class="menu-item-price"><?php echo formatCurrency($item['price']); ?></p>
                            </div>
                            
                            <div style="display: flex; align-items: center; gap: 20px;">
                                <div class="quantity-controls">
                                    <button class="quantity-btn" onclick="updateQuantity(<?php echo $item['cart_id']; ?>, <?php echo $item['quantity'] - 1; ?>)">-</button>
                                    <span><?php echo $item['quantity']; ?></span>
                                    <button class="quantity-btn" onclick="updateQuantity(<?php echo $item['cart_id']; ?>, <?php echo $item['quantity'] + 1; ?>)">+</button>
                                </div>
                                <span style="font-weight: 600; min-width: 80px; text-align: right;">
                                    <?php echo formatCurrency($item['price'] * $item['quantity']); ?>
                                </span>
                                <button class="quantity-btn" onclick="removeFromCart(<?php echo $item['cart_id']; ?>)" style="color: var(--primary-color);">🗑️</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="cart-summary">
                    <h3 style="margin-bottom: 20px;">Bill Details</h3>
                    
                    <div class="summary-row">
                        <span>Subtotal</span>
                        <span><?php echo formatCurrency($subtotal); ?></span>
                    </div>
                    
                    <div class="summary-row">
                        <span>Delivery Fee</span>
                        <span><?php echo formatCurrency($deliveryFee); ?></span>
                    </div>

                    <?php
                    // Coupon Logic
                    $discount = 0;
                    $couponCode = '';
                    $couponMessage = '';
                    $couponError = '';

                    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply_coupon'])) {
                        $code = strtoupper(trim($_POST['coupon_code']));
                        
                        // Check if user is new (0 orders)
                        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM orders WHERE user_id = ?");
                        $stmt->bind_param("i", $userId);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        $orderHistory = $result->fetch_assoc();
                        $orderCount = $orderHistory['count'];
                        $stmt->close();

                        if ($code === 'WELCOME50') {
                            if ($orderCount == 0) {
                                $discount = $subtotal * 0.50;
                                $couponCode = 'WELCOME50';
                                $couponMessage = 'WELCOME50 applied! 50% off';
                                $_SESSION['applied_coupon'] = $couponCode;
                            } else {
                                $couponError = 'Offer valid only for new users on first order';
                            }
                        } elseif ($code === 'FIRST100') {
                            if ($orderCount == 0) {
                                if ($subtotal >= 100) {
                                    $discount = 100;
                                    $couponCode = 'FIRST100';
                                    $couponMessage = 'FIRST100 applied! Flat ₹100 off';
                                    $_SESSION['applied_coupon'] = $couponCode;
                                } else {
                                     $couponError = 'Minimum cart value ₹100 required';
                                }
                            } else {
                                $couponError = 'Offer valid only for new users on first order';
                            }
                        } elseif ($code === 'SAVE20') {
                            if ($subtotal > 500) {
                                $discount = $subtotal * 0.20;
                                $couponCode = 'SAVE20';
                                $couponMessage = 'SAVE20 applied! 20% off';
                                $_SESSION['applied_coupon'] = $couponCode;
                            } else {
                                $couponError = 'Cart value must be above ₹500';
                            }
                        } elseif ($code === '') {
                             // Removed
                        } else {
                            $couponError = 'Invalid coupon code';
                        }
                    }
                    
                    // Recalculate totals
                    $taxes = ($subtotal - $discount) * 0.05;
                    $total = ($subtotal - $discount) + $deliveryFee + $taxes;
                    ?>

                    <!-- Coupon Input -->
                    <div style="margin: 20px 0; border-top: 1px dashed var(--border-color); border-bottom: 1px dashed var(--border-color); padding: 15px 0;">
                        <form method="POST" style="display: flex; gap: 10px;">
                            <input type="text" name="coupon_code" value="<?php echo htmlspecialchars($couponCode); ?>" placeholder="Enter coupon code" style="flex: 1; padding: 8px 12px; border: 1px solid var(--border-color); border-radius: 6px; text-transform: uppercase;">
                            <button type="submit" name="apply_coupon" value="1" style="background: var(--text-primary); color: white; padding: 8px 15px; border-radius: 6px; font-weight: 600;">Apply</button>
                        </form>
                        <?php if ($couponMessage): ?>
                            <div style="color: var(--success-color); font-size: 13px; margin-top: 8px;">✅ <?php echo $couponMessage; ?></div>
                        <?php endif; ?>
                        <?php if ($couponError): ?>
                            <div style="color: var(--primary-color); font-size: 13px; margin-top: 8px;">❌ <?php echo $couponError; ?></div>
                        <?php endif; ?>
                        
                        <div style="margin-top: 15px;">
                            <div style="font-size: 12px; font-weight: 600; color: var(--text-secondary); margin-bottom: 5px;">AVAILABLE OFFERS</div>
                            <div style="display: flex; gap: 8px; overflow-x: auto; padding-bottom: 5px;">
                                <div onclick="document.querySelector('input[name=coupon_code]').value='WELCOME50'; document.querySelector('button[name=apply_coupon]').click();" style="border: 1px solid var(--border-color); padding: 8px; border-radius: 6px; min-width: 120px; cursor: pointer;">
                                    <div style="color: var(--primary-color); font-weight: 700; font-size: 12px;">WELCOME50</div>
                                    <div style="font-size: 10px; color: var(--text-secondary);">New User: 50% Off</div>
                                </div>
                                <div onclick="document.querySelector('input[name=coupon_code]').value='FIRST100'; document.querySelector('button[name=apply_coupon]').click();" style="border: 1px solid var(--border-color); padding: 8px; border-radius: 6px; min-width: 120px; cursor: pointer;">
                                    <div style="color: var(--primary-color); font-weight: 700; font-size: 12px;">FIRST100</div>
                                    <div style="font-size: 10px; color: var(--text-secondary);">New User: Flat ₹100</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if ($discount > 0): ?>
                        <div class="summary-row">
                            <span style="color: var(--success-color);">Coupon Discount</span>
                            <span style="color: var(--success-color);">-<?php echo formatCurrency($discount); ?></span>
                        </div>
                    <?php endif; ?>

                    <div class="summary-row">
                        <span>Taxes & Charges</span>
                        <span><?php echo formatCurrency($taxes); ?></span>
                    </div>
                    
                    <div class="summary-row summary-total">
                        <span>Total</span>
                        <span><?php echo formatCurrency($total); ?></span>
                    </div>
                    
                    <a href="<?php echo PAGES_URL; ?>/checkout.php" class="btn btn-primary" style="width: 100%; text-align: center; margin-top: 20px; display: block;">
                        Proceed to Checkout
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="<?php echo ASSETS_URL; ?>/js/cart.js"></script>

<?php
include __DIR__ . '/../components/chatbot-widget.php';
include __DIR__ . '/../components/footer.php';

if (isset($conn)) {
    $conn->close();
}
?>
