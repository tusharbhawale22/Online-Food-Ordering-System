<?php
require_once __DIR__ . '/../../backend/config/config.php';
require_once __DIR__ . '/../../backend/includes/auth.php';

requireLogin();

$userId = getCurrentUserId();
$conn = getDBConnection();

// Fetch user details
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// Fetch order count
$stmt = $conn->prepare("SELECT COUNT(*) as order_count FROM orders WHERE user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$orderStats = $result->fetch_assoc();
$stmt->close();

$conn->close();

require_once __DIR__ . '/../components/header.php';
?>

<style>
.profile-page {
    background-color: var(--bg-light);
    min-height: 100vh;
}

.profile-header {
    background: linear-gradient(rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.7)), url('https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?w=1200&h=300&fit=crop');
    background-size: cover;
    background-position: center;
    padding: 60px 0;
    color: white;
}

.profile-info {
    display: flex;
    align-items: center;
    gap: 30px;
}

.profile-avatar {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    background-color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 48px;
    color: var(--primary-color);
    font-weight: 700;
    border: 5px solid white;
}

.profile-details h1 {
    font-size: 36px;
    margin-bottom: 10px;
}

.profile-stats {
    display: flex;
    gap: 40px;
    margin-top: 20px;
}

.stat-item {
    text-align: center;
}

.stat-number {
    font-size: 28px;
    font-weight: 700;
}

.stat-label {
    font-size: 14px;
    opacity: 0.9;
}

.profile-tabs {
    background-color: white;
    box-shadow: var(--shadow-sm);
}

.profile-tabs-inner {
    display: flex;
    gap: 50px;
    padding: 0;
}

.profile-tab {
    padding: 20px 0;
    cursor: pointer;
    color: var(--text-secondary);
    font-weight: 500;
    border-bottom: 3px solid transparent;
    transition: all 0.3s ease;
}

.profile-tab.active {
    color: var(--primary-color);
    border-bottom-color: var(--primary-color);
}

.profile-content {
    padding: 40px 0;
}

.info-card {
    background-color: white;
    padding: 30px;
    border-radius: 12px;
    box-shadow: var(--shadow-sm);
}

.info-row {
    display: grid;
    grid-template-columns: 200px 1fr;
    padding: 15px 0;
    border-bottom: 1px solid var(--border-color);
}

.info-row:last-child {
    border-bottom: none;
}

.info-label {
    color: var(--text-secondary);
    font-weight: 500;
}

.info-value {
    color: var(--text-primary);
}
</style>

<div class="profile-page">
    <div class="profile-header">
        <div class="container">
            <div class="profile-info">
                <div class="profile-avatar">
                    <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                </div>
                <div class="profile-details">
                    <h1><?php echo htmlspecialchars($user['full_name']); ?></h1>
                </div>
            </div>
        </div>
    </div>
    
    <div class="profile-content">
        <div class="container">
            <h2 class="section-title">Account Information</h2>
            
            <div class="info-card">
                <div class="info-row">
                    <div class="info-label">Full Name</div>
                    <div class="info-value"><?php echo htmlspecialchars($user['full_name']); ?></div>
                </div>
                
                <div class="info-row">
                    <div class="info-label">Email Address</div>
                    <div class="info-value"><?php echo htmlspecialchars($user['email']); ?></div>
                </div>
                
                <div class="info-row">
                    <div class="info-label">Phone Number</div>
                    <div class="info-value"><?php echo htmlspecialchars($user['phone'] ?? 'Not provided'); ?></div>
                </div>
                
                <div class="info-row">
                    <div class="info-label">Locality</div>
                    <div class="info-value"><?php echo htmlspecialchars($user['locality'] ?? 'Not specified'); ?></div>
                </div>
                
                <div class="info-row">
                    <div class="info-label">City</div>
                    <div class="info-value"><?php echo htmlspecialchars($user['city']); ?></div>
                </div>
                
                <div class="info-row">
                    <div class="info-label">Member Since</div>
                    <div class="info-value"><?php echo date('F Y', strtotime($user['created_at'])); ?></div>
                </div>
            </div>
            
            <div style="margin-top: 30px;">
                <a href="<?php echo PAGES_URL; ?>/orders.php" class="btn btn-primary">View Order History</a>
            </div>
        </div>
    </div>
</div>

<?php
include __DIR__ . '/../components/chatbot-widget.php';
include __DIR__ . '/../components/footer.php';
?>
