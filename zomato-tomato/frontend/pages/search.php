<?php
require_once __DIR__ . '/../../backend/config/config.php';

$searchQuery = sanitizeInput($_GET['q'] ?? '');
$type = sanitizeInput($_GET['type'] ?? '');
$locality = sanitizeInput($_GET['locality'] ?? '');

$conn = getDBConnection();

// Build search query
// Build search query
$sql = "SELECT r.*, 
        (SELECT COUNT(*) FROM menu_items mi WHERE mi.restaurant_id = r.restaurant_id) as menu_count,
        COALESCE(AVG(rev.rating), 3) as rating
        FROM restaurants r
        LEFT JOIN menu_items mi ON r.restaurant_id = mi.restaurant_id
        LEFT JOIN reviews rev ON r.restaurant_id = rev.restaurant_id
        WHERE r.is_active = TRUE";

$params = [];
$types = '';

if ($searchQuery) {
    $sql .= " AND (r.name LIKE ? OR r.cuisine_type LIKE ? OR mi.item_name LIKE ?)";
    $searchParam = "%$searchQuery%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $types .= 'sss';
}

if ($type) {
    if ($type === 'nightlife') {
        // Nightlife: Peter Cat and Spice Kraft
        $sql .= " AND r.name IN ('Peter Cat', 'Spice Kraft')";
    } elseif ($type === 'dine-in') {
        // Go out for a meal: Marbella's, Roastery Coffee House, and Retro Cafe
        // Note: Escaping single quote for SQL
        $sql .= " AND r.name IN ('Marbella\'s', 'Roastery Coffee House', 'Retro Cafe')";
    } elseif ($type === 'pro') {
        // Tomato Pro: Marbella's only
        $sql .= " AND r.name = 'Marbella\'s'";
    } elseif ($type === 'delivery') {
        // Online Food Delivery: All (no filter needed beyond is_active)
    }
}

if ($locality) {
    $sql .= " AND r.locality = ?";
    $params[] = $locality;
    $types .= 's';
}

$sort = sanitizeInput($_GET['sort'] ?? 'rating');

// ... existing query building ...

$orderBy = "rating DESC"; // Default
switch ($sort) {
    case 'cost_low':
        $orderBy = "r.average_cost_for_two ASC";
        break;
    case 'cost_high':
        $orderBy = "r.average_cost_for_two DESC";
        break;
    case 'rating':
    default:
        $orderBy = "rating DESC";
        break;
}

$sql .= " GROUP BY r.restaurant_id ORDER BY " . $orderBy;

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

require_once __DIR__ . '/../components/header.php';
?>

<style>
.search-header {
    background-color: var(--bg-light);
    padding: 30px 0;
}

.search-filters {
    display: flex;
    gap: 15px;
    margin-top: 20px;
    flex-wrap: wrap;
}

.filter-btn {
    padding: 10px 20px;
    border: 1px solid var(--border-color);
    background-color: white;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.filter-btn:hover, .filter-btn.active {
    border-color: var(--primary-color);
    background-color: var(--primary-color);
    color: white;
}

.search-results {
    padding: 40px 0;
}

.results-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
}

.results-count {
    color: var(--text-secondary);
    font-size: 14px;
}

.sort-dropdown {
    padding: 10px 15px;
    border: 1px solid var(--border-color);
    border-radius: 8px;
    cursor: pointer;
}
</style>

<div class="search-header">
    <div class="container">
        <h1 class="section-title">
            <?php if ($searchQuery): ?>
                Search results for "<?php echo htmlspecialchars($searchQuery); ?>"
            <?php elseif ($type): ?>
                <?php echo ucfirst(str_replace('-', ' ', $type)); ?>
            <?php else: ?>
                All Restaurants
            <?php endif; ?>
        </h1>
        
        <div class="search-filters">
            <button class="filter-btn" onclick="filterByLocality('')">All Localities</button>
            <button class="filter-btn" onclick="filterByLocality('Southern Avenue')">Southern Avenue</button>
            <button class="filter-btn" onclick="filterByLocality('Park Street')">Park Street</button>
            <button class="filter-btn" onclick="filterByLocality('Salt Lake')">Salt Lake</button>
            <button class="filter-btn" onclick="filterByLocality('Ballygunge')">Ballygunge</button>
        </div>
    </div>
</div>

<div class="search-results">
    <div class="container">
        <div class="results-header">
            <span class="results-count"><?php echo $result->num_rows; ?> restaurants found</span>
            <select class="sort-dropdown" onchange="sortResults(this.value)">
                <option value="rating">Sort by Rating</option>
                <option value="cost_low">Cost: Low to High</option>
                <option value="cost_high">Cost: High to Low</option>
            </select>
        </div>
        
        <div class="restaurant-grid">
            <?php while ($restaurant = $result->fetch_assoc()): ?>
                <div class="restaurant-card" onclick="window.location.href='<?php echo PAGES_URL; ?>/restaurant.php?id=<?php echo $restaurant['restaurant_id']; ?>'">
                    <?php
                    $imageUrl = "https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?w=400&h=300&fit=crop";
                    if (!empty($restaurant['image_url'])) {
                        $localImagePath = FRONTEND_PATH . '/assets/' . $restaurant['image_url'];
                        if (file_exists($localImagePath)) {
                            $imageUrl = ASSETS_URL . '/' . $restaurant['image_url'];
                        }
                    }
                    ?>
                    <img src="<?php echo $imageUrl; ?>" alt="<?php echo htmlspecialchars($restaurant['name']); ?>" class="restaurant-image">
                    
                    <div class="restaurant-info">
                        <div class="restaurant-header">
                            <h3 class="restaurant-name"><?php echo htmlspecialchars($restaurant['name']); ?></h3>
                            <div class="rating">
                                <span class="rating-star">★</span>
                                <span><?php echo number_format($restaurant['rating'], 1); ?></span>
                            </div>
                        </div>
                        
                        <p class="restaurant-cuisine"><?php echo htmlspecialchars($restaurant['cuisine_type']); ?></p>
                        <p class="restaurant-locality"><?php echo htmlspecialchars($restaurant['locality']); ?></p>
                        <p class="restaurant-cost">₹<?php echo number_format($restaurant['average_cost_for_two']); ?> for two</p>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
        
        <?php if ($result->num_rows === 0): ?>
            <div style="text-align: center; padding: 60px 0;">
                <h2 style="color: var(--text-secondary); margin-bottom: 10px;">No restaurants found</h2>
                <p style="color: var(--text-light);">Try adjusting your search or filters</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function filterByLocality(locality) {
    const url = new URL(window.location.href);
    if (locality) {
        url.searchParams.set('locality', locality);
    } else {
        url.searchParams.delete('locality');
    }
    window.location.href = url.toString();
}

function sortResults(sortBy) {
    const url = new URL(window.location.href);
    url.searchParams.set('sort', sortBy);
    window.location.href = url.toString();
}
</script>

<?php
$stmt->close();
$conn->close();
include __DIR__ . '/../components/chatbot-widget.php';
include __DIR__ . '/../components/footer.php';
?>
