<?php
require_once __DIR__ . '/../../backend/config/config.php';

$restaurantId = intval($_GET['id'] ?? 0);

if ($restaurantId === 0) {
    redirect(SITE_URL . '/index.php');
}

$conn = getDBConnection();

// Fetch restaurant details
$stmt = $conn->prepare("SELECT * FROM restaurants WHERE restaurant_id = ? AND is_active = TRUE");
$stmt->bind_param("i", $restaurantId);
$stmt->execute();
$result = $stmt->get_result();
$restaurant = $result->fetch_assoc();
$stmt->close();

if (!$restaurant) {
    redirect(SITE_URL . '/index.php');
}

// Fetch menu items
$menuQuery = "SELECT * FROM menu_items WHERE restaurant_id = ? AND is_available = TRUE ORDER BY category, item_name";
$stmt = $conn->prepare($menuQuery);
$stmt->bind_param("i", $restaurantId);
$stmt->execute();
$menuResult = $stmt->get_result();

// Group menu items by category
$menuByCategory = [];
while ($item = $menuResult->fetch_assoc()) {
    $category = $item['category'] ?? 'Other';
    if (!isset($menuByCategory[$category])) {
        $menuByCategory[$category] = [];
    }
    $menuByCategory[$category][] = $item;
}
$stmt->close();

// Fetch reviews
$reviewsQuery = "SELECT r.*, u.full_name, u.is_new_user FROM reviews r 
                 JOIN users u ON r.user_id = u.user_id 
                 WHERE r.restaurant_id = ? 
                 ORDER BY r.created_at DESC";
$stmt = $conn->prepare($reviewsQuery);
$stmt->bind_param("i", $restaurantId);
$stmt->execute();
$reviewsResult = $stmt->get_result();
$reviews = [];
$totalRating = 0;
$reviewCount = 0;

while ($review = $reviewsResult->fetch_assoc()) {
    $reviews[] = $review;
    $totalRating += $review['rating'];
    $reviewCount++;
}
$stmt->close();

// Calculate dynamic rating
if ($reviewCount > 0) {
    $restaurant['rating'] = $totalRating / $reviewCount;
} else {
    $restaurant['rating'] = 3.0; // Default rating if no reviews
}

$conn->close();

require_once __DIR__ . '/../components/header.php';
?>

<?php
// Determine hero image
$heroImage = "https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?w=1200&h=400&fit=crop";
if (!empty($restaurant['image_url'])) {
    $localImagePath = FRONTEND_PATH . '/assets/' . $restaurant['image_url'];
    if (file_exists($localImagePath)) {
        $heroImage = ASSETS_URL . '/' . $restaurant['image_url'];
    }
}
?>
<style>
.restaurant-hero {
    position: relative;
    height: 400px;
    background: linear-gradient(rgba(0, 0, 0, 0.3), rgba(0, 0, 0, 0.5)), url('<?php echo $heroImage; ?>');
    background-size: cover;
    background-position: center;
    display: flex;
    align-items: flex-end;
    color: white;
}

.restaurant-hero-content {
    padding: 40px 0;
}

.restaurant-title {
    font-size: 42px;
    font-weight: 700;
    margin-bottom: 10px;
}

.restaurant-meta {
    display: flex;
    gap: 30px;
    align-items: center;
    font-size: 16px;
}

.breadcrumb {
    padding: 20px 0;
    color: var(--text-light);
    font-size: 14px;
}

.breadcrumb a {
    color: var(--text-secondary);
}

.breadcrumb a:hover {
    color: var(--primary-color);
}

.restaurant-tabs {
    display: flex;
    gap: 40px;
    border-bottom: 2px solid var(--border-color);
    padding: 20px 0 0;
    margin-bottom: 30px;
}

.tab {
    padding-bottom: 15px;
    cursor: pointer;
    color: var(--text-secondary);
    font-weight: 500;
    border-bottom: 3px solid transparent;
    transition: all 0.3s ease;
}

.tab.active {
    color: var(--primary-color);
    border-bottom-color: var(--primary-color);
}

.safety-measures {
    background-color: var(--bg-light);
    padding: 30px;
    border-radius: 12px;
    margin-bottom: 30px;
}

.safety-measures h3 {
    margin-bottom: 20px;
    font-size: 18px;
}

.safety-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
}

.safety-item {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 14px;
}

.safety-icon {
    font-size: 24px;
}

.tab-content {
    display: none;
}

.tab-content.active {
    display: block;
}

.review-card {
    background: white;
    padding: 20px;
    border-radius: 12px;
    margin-bottom: 15px;
    border: 1px solid var(--border-color);
}

.review-header {
    display: flex;
    justify-content: space-between;
    margin-bottom: 10px;
}

.reviewer-name {
    font-weight: 600;
    color: var(--text-primary);
}

.review-date {
    font-size: 12px;
    color: var(--text-light);
}

.review-rating {
    background-color: var(--primary-color);
    color: white;
    padding: 2px 6px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 600;
}

.review-text {
    color: var(--text-secondary);
    line-height: 1.5;
}
#map {
    height: 300px;
    width: 100%;
    border-radius: 12px;
    margin-top: 20px;
    margin-bottom: 20px;
    z-index: 1;
}
</style>

<div class="breadcrumb">
    <div class="container">
        <a href="<?php echo PAGES_URL; ?>/index.php">Home</a> / 
        <a href="<?php echo PAGES_URL; ?>/index.php">Kolkata</a> / 
        <a href="#"><?php echo htmlspecialchars($restaurant['locality']); ?></a> / 
        <a href="#">122A</a> / 
        <span><?php echo htmlspecialchars($restaurant['name']); ?></span>
    </div>
</div>

<div class="restaurant-hero">
    <div class="container">
        <div class="restaurant-hero-content">
            <h1 class="restaurant-title"><?php echo htmlspecialchars($restaurant['name']); ?></h1>
            <div class="restaurant-meta">
                <div class="rating">
                    <span class="rating-star">★</span>
                    <span><?php echo number_format($restaurant['rating'], 1); ?></span>
                </div>
                <span><?php echo htmlspecialchars($restaurant['cuisine_type']); ?></span>
                <span><?php echo htmlspecialchars($restaurant['locality']); ?></span>
            </div>
        </div>
    </div>
</div>

<div class="container">
    <div class="restaurant-tabs">
        <div class="tab" onclick="switchTab('overview')">Overview</div>
        <div class="tab active" onclick="switchTab('order')">Order Online</div>
        <div class="tab" onclick="switchTab('reviews')">Reviews</div>
        <div class="tab" onclick="switchTab('menu')">Menu</div>
    </div>
    
    <div class="safety-measures">
        <h3>About This Place</h3>
        <div class="safety-list" style="margin-bottom: 30px; padding-bottom: 30px; border-bottom: 1px solid var(--border-color);">
            <ul style="display: flex; flex-direction: column; gap: 12px; padding-left: 20px; color: var(--text-secondary); line-height: 1.6;">
                <li>Well Sanitized Kitchen</li>
                <li>Daily Temperature Checks for Staff</li>
                <li>Rider Hand Wash & Sanitization</li>
                <li>Contactless Delivery Available</li>
                <li>Regular Deep Cleaning of Premises</li>
                <li>Mandatory Masks for All Staff Members</li>
            </ul>
        </div>

        <h3 style="margin-bottom: 20px; font-size: 18px;">Restaurant Details</h3>
        <div class="details-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 25px;">
            <div class="detail-item">
                <h4 style="font-size: 14px; text-transform: uppercase; color: var(--text-light); margin-bottom: 8px;">Call</h4>
                <p style="font-size: 16px; color: var(--primary-color); font-weight: 500;">+91 99999 99999</p>
            </div>
            <div class="detail-item">
                <h4 style="font-size: 14px; text-transform: uppercase; color: var(--text-light); margin-bottom: 8px;">Timings</h4>
                <p style="font-size: 16px; color: var(--text-primary);">11:00 AM – 11:00 PM</p>
            </div>
            <div class="detail-item">
                <h4 style="font-size: 14px; text-transform: uppercase; color: var(--text-light); margin-bottom: 8px;">Average Cost</h4>
                <p style="font-size: 16px; color: var(--text-primary);">₹<?php echo number_format($restaurant['average_cost_for_two']); ?> for two people (approx.)</p>
            </div>
            <div class="detail-item">
                <h4 style="font-size: 14px; text-transform: uppercase; color: var(--text-light); margin-bottom: 8px;">Location</h4>
                <p style="font-size: 16px; color: var(--text-primary); line-height: 1.5;"><?php echo htmlspecialchars($restaurant['address'] ?? $restaurant['locality'] . ', Kolkata'); ?></p>
            </div>
        </div>
        
        <div id="map"></div>
        
        <div class="action-buttons" style="display: flex; gap: 15px; margin-top: 30px;">
            <button onclick="copyDirection('<?php echo htmlspecialchars($restaurant['address'] ?? $restaurant['locality'] . ', Kolkata', ENT_QUOTES); ?>')" class="btn btn-outline" style="border: 1px solid var(--border-color); padding: 10px 20px; border-radius: 8px; background: white; color: var(--text-secondary); cursor: pointer; display: flex; align-items: center; gap: 8px; transition: all 0.2s;">
                <span>📋</span> Copy Direction
            </button>
            <button onclick="shareRestaurant()" class="btn btn-outline" style="border: 1px solid var(--border-color); padding: 10px 20px; border-radius: 8px; background: white; color: var(--text-secondary); cursor: pointer; display: flex; align-items: center; gap: 8px; transition: all 0.2s;">
                <span>🔗</span> Share
            </button>
            <button onclick="showRoute()" class="btn btn-outline" style="border: 1px solid var(--border-color); padding: 10px 20px; border-radius: 8px; background: white; color: var(--text-secondary); cursor: pointer; display: flex; align-items: center; gap: 8px; transition: all 0.2s;">
                <span>🗺️</span> Show Route
            </button>
        </div>
    </div>
    
    <!-- Toast Notification -->
    <div id="toast" style="visibility: hidden; min-width: 250px; background-color: #333; color: #fff; text-align: center; border-radius: 8px; padding: 12px; position: fixed; z-index: 1000; bottom: 30px; left: 50%; transform: translateX(-50%); opacity: 0; transition: opacity 0.3s, bottom 0.3s; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
        Message
    </div>

    <script>
    function showToast(message) {
        var toast = document.getElementById("toast");
        toast.innerText = message;
        toast.style.visibility = "visible";
        toast.style.opacity = "1";
        toast.style.bottom = "50px";
        
        setTimeout(function(){ 
            toast.style.opacity = "0";
            toast.style.bottom = "30px";
            setTimeout(() => { toast.style.visibility = "hidden"; }, 300);
        }, 3000);
    }

    function copyDirection(address) {
        navigator.clipboard.writeText(address).then(function() {
            showToast("Address copied to clipboard!");
        }, function(err) {
            console.error('Async: Could not copy text: ', err);
            // Fallback for older browsers
            var textArea = document.createElement("textarea");
            textArea.value = address;
            document.body.appendChild(textArea);
            textArea.select();
            try {
                document.execCommand('copy');
                showToast("Address copied to clipboard!");
            } catch (err) {
                showToast("Failed to copy address");
            }
            document.body.removeChild(textArea);
        });
    }

    function shareRestaurant() {
        if (navigator.share) {
            navigator.share({
                title: '<?php echo htmlspecialchars($restaurant['name'], ENT_QUOTES); ?> on Tomato',
                text: 'Check out this amazing restaurant!',
                url: window.location.href
            }).then(() => {
                console.log('Thanks for sharing!');
            }).catch(console.error);
        } else {
            // Fallback
            navigator.clipboard.writeText(window.location.href).then(function() {
                showToast("Link copied to clipboard!");
            }, function(err) {
                showToast("Failed to copy link");
            });
        }
    }
    </script>
    
    <div id="order-content" class="tab-content active">
        <div class="menu-section">
            <?php foreach ($menuByCategory as $category => $items): ?>
                <h2 class="section-title"><?php echo htmlspecialchars($category); ?></h2>
                <div class="menu-grid">
                    <?php foreach ($items as $item): ?>
                        <div class="menu-item">
                            <div class="menu-item-info">
                                <div class="menu-item-header">
                                    <div class="veg-indicator <?php echo $item['is_veg'] ? 'veg' : 'non-veg'; ?>">
                                        <div class="veg-dot"></div>
                                    </div>
                                    <h3 class="menu-item-name"><?php echo htmlspecialchars($item['item_name']); ?></h3>
                                </div>
                                <p class="menu-item-description"><?php echo htmlspecialchars($item['description'] ?? ''); ?></p>
                                <p class="menu-item-price"><?php echo formatCurrency($item['price']); ?></p>
                                <button class="add-to-cart-btn" onclick="addToCart(<?php echo $item['item_id']; ?>, '<?php echo htmlspecialchars($item['item_name']); ?>')">
                                    Add to Cart
                                </button>
                            </div>
                            <?php if ($item['image_url']): 
                                $imgSrc = $item['image_url'];
                                if (strpos($imgSrc, 'http') !== 0) {
                                    $imgSrc = ASSETS_URL . '/' . $imgSrc;
                                }
                            ?>
                                <img src="<?php echo htmlspecialchars($imgSrc); ?>" alt="<?php echo htmlspecialchars($item['item_name']); ?>" class="menu-item-image">
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div id="reviews-content" class="tab-content">
        <h2 class="section-title">Reviews</h2>
        <?php if (empty($reviews)): ?>
            <div style="text-align: center; padding: 40px; color: var(--text-secondary);">
                <div style="font-size: 40px; margin-bottom: 15px;">📝</div>
                <p>No reviews yet. Be the first to review!</p>
            </div>
        <?php else: ?>
            <?php foreach ($reviews as $review): ?>
                <div class="review-card">
                    <div class="review-header">
                        <div style="display: flex; gap: 10px; align-items: center;">
                            <div class="reviewer-name"><?php echo htmlspecialchars($review['full_name']); ?></div>
                            <div class="review-rating"><?php echo $review['rating']; ?> ★</div>
                        </div>
                        <div class="review-date"><?php echo date('d M Y', strtotime($review['created_at'])); ?></div>
                    </div>
                    <div class="review-text">
                        <?php echo htmlspecialchars($review['comment']); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div id="overview-content" class="tab-content">
        <div style="padding: 40px; text-align: center; color: var(--text-secondary);">
            Overview content coming soon...
        </div>
    </div>

    <!-- Menu Content -->
    <div id="menu-content" class="tab-content">
        <h2 class="section-title">Full Menu</h2>
        <div class="menu-section">
            <?php foreach ($menuByCategory as $category => $items): ?>
                <h3 style="margin-top: 30px; margin-bottom: 20px; font-size: 20px; color: var(--text-primary); border-bottom: 2px solid var(--primary-color); display: inline-block; padding-bottom: 5px;"><?php echo htmlspecialchars($category); ?></h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px;">
                    <?php foreach ($items as $item): ?>
                        <div style="border: 1px solid var(--border-color); border-radius: 12px; overflow: hidden; background: white; transition: transform 0.2s; box-shadow: 0 4px 6px rgba(0,0,0,0.05);" onmouseover="this.style.transform='translateY(-5px)'" onmouseout="this.style.transform='translateY(0)'">
                            <?php if ($item['image_url']): 
                                $imgSrc = $item['image_url'];
                                if (strpos($imgSrc, 'http') !== 0) {
                                    $imgSrc = ASSETS_URL . '/' . $imgSrc;
                                }
                            ?>
                                <div style="height: 180px; overflow: hidden;">
                                    <img src="<?php echo htmlspecialchars($imgSrc); ?>" alt="<?php echo htmlspecialchars($item['item_name']); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                </div>
                            <?php endif; ?>
                            <div style="padding: 15px;">
                                <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 10px;">
                                    <h4 style="margin: 0; font-size: 16px; font-weight: 600; color: var(--text-primary);"><?php echo htmlspecialchars($item['item_name']); ?></h4>
                                    <span style="background: var(--bg-light); padding: 4px 8px; border-radius: 4px; font-size: 13px; font-weight: 600; color: var(--primary-color);"><?php echo formatCurrency($item['price']); ?></span>
                                </div>
                                <p style="margin: 0; font-size: 13px; color: var(--text-light); line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;"><?php echo htmlspecialchars($item['description'] ?? ''); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var address = "<?php echo htmlspecialchars($restaurant['address'] ?? $restaurant['locality'] . ', Kolkata', ENT_QUOTES); ?>";
    var restaurantName = "<?php echo htmlspecialchars($restaurant['name'], ENT_QUOTES); ?>";
    var apiKey = "pk.75a2c3ebe2ef60298a00e7fa5ab8b389";

    // Initialize map
    var map = L.map('map').setView([22.5726, 88.3639], 13); // Default to Kolkata

    // Add LocationIQ tiles
    L.tileLayer('https://{s}-tiles.locationiq.com/v3/streets/r/{z}/{x}/{y}.png?key=' + apiKey, {
        attribution: '&copy; <a href="https://locationiq.com/?ref=maps">LocationIQ</a> &copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        maxZoom: 18
    }).addTo(map);

    // Geocode address
    var url = `https://us1.locationiq.com/v1/search.php?key=${apiKey}&q=${encodeURIComponent(address)}&format=json`;

    fetch(url)
        .then(response => response.json())
        .then(data => {
            if (data && data.length > 0) {
                var lat = parseFloat(data[0].lat);
                var lon = parseFloat(data[0].lon);
                
                window.restaurantLat = lat;
                window.restaurantLon = lon;
                window.mapInstance = map;

                map.setView([lat, lon], 16);
                L.marker([lat, lon]).addTo(map)
                    .bindPopup("<b>" + restaurantName + "</b><br>" + address)
                    .openPopup();
            } else {
                console.log("Address not found, showing default location.");
                showToast("Address could not be located on map");
            }
        })
        .catch(error => {
            console.error("Error geocoding address:", error);
        });
});

function showRoute() {
    if (!navigator.geolocation) {
        showToast("Geolocation is not supported by your browser");
        return;
    }

    if (!window.restaurantLat || !window.restaurantLon) {
        showToast("Restaurant location not found yet");
        return;
    }

    showToast("Getting your location...");

    navigator.geolocation.getCurrentPosition(function(position) {
        var userLat = position.coords.latitude;
        var userLon = position.coords.longitude;
        var apiKey = "pk.75a2c3ebe2ef60298a00e7fa5ab8b389";
        var map = window.mapInstance;

        // Add user marker
        L.marker([userLat, userLon]).addTo(map)
            .bindPopup("<b>You are here</b>")
            .openPopup();

        // Get directions
        var directionsUrl = `https://us1.locationiq.com/v1/directions/driving/${userLon},${userLat};${window.restaurantLon},${window.restaurantLat}?key=${apiKey}&overview=full&geometries=geojson`;

        fetch(directionsUrl)
            .then(response => response.json())
            .then(data => {
                if (data.code === 'Ok' && data.routes && data.routes.length > 0) {
                    var geometry = data.routes[0].geometry;
                    
                    // Remove existing route layer if any
                    if (window.routeLayer) {
                        map.removeLayer(window.routeLayer);
                    }

                    // Add new route layer
                    window.routeLayer = L.geoJSON(geometry, {
                        style: {
                            color: "blue",
                            weight: 5,
                            opacity: 0.7
                        }
                    }).addTo(map);

                    // Fit map to show the whole route
                    map.fitBounds(window.routeLayer.getBounds(), {padding: [50, 50]});
                    showToast("Route calculated!");
                } else {
                    showToast("Could not find a route");
                }
            })
            .catch(error => {
                console.error("Error fetching directions:", error);
                showToast("Error fetching directions");
            });

    }, function(error) {
        console.error("Geolocation error:", error);
        showToast("Unable to retrieve your location");
    });
}


// Tab functionality
function switchTab(tabName) {
    // Hide all contents
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.remove('active');
    });
    
    // Show selected content
    const contentId = tabName + '-content';
    const selectedContent = document.getElementById(contentId);
    if (selectedContent) {
        selectedContent.classList.add('active');
    }
    
    // Update active tab buttons
    document.querySelectorAll('.tab').forEach(tab => {
        tab.classList.remove('active');
        if (tab.innerText.toLowerCase().includes(tabName.toLowerCase()) || 
           (tabName === 'order' && tab.innerText === 'Order Online')) {
            tab.classList.add('active');
        }
    });
}
</script>

<script src="<?php echo ASSETS_URL; ?>/js/cart.js"></script>

<?php
include __DIR__ . '/../components/chatbot-widget.php';
include __DIR__ . '/../components/footer.php';
?>
