<?php
require_once __DIR__ . '/../../backend/config/config.php';
require_once __DIR__ . '/../components/header.php';

// Fetch featured restaurants
$conn = getDBConnection();
$restaurantsQuery = "SELECT r.*, COALESCE(AVG(rev.rating), 3) as rating 
                     FROM restaurants r 
                     LEFT JOIN reviews rev ON r.restaurant_id = rev.restaurant_id 
                     WHERE r.is_active = TRUE 
                     GROUP BY r.restaurant_id 
                     ORDER BY rating DESC LIMIT 8";
$restaurantsResult = $conn->query($restaurantsQuery);
?>

<section class="hero">
    <div class="container">
        <h1 class="hero-title">tomato</h1>
        <p class="hero-subtitle">Discover the best food & drinks in Kolkata</p>
        
        <div class="hero-quote-container" style="margin-top: 50px; display: flex; justify-content: center; perspective: 1000px;">
            <div class="glass-card" style="
                background: rgba(25, 25, 25, 0.4);
                backdrop-filter: blur(16px);
                -webkit-backdrop-filter: blur(16px);
                border: 1px solid rgba(255, 255, 255, 0.15);
                padding: 40px 60px;
                border-radius: 24px;
                box-shadow: 0 15px 35px rgba(0, 0, 0, 0.4);
                max-width: 850px;
                text-align: center;
                animation: float 6s ease-in-out infinite;
            ">
                <style>
                    @keyframes float {
                        0% { transform: translateY(0px); }
                        50% { transform: translateY(-10px); }
                        100% { transform: translateY(0px); }
                    }
                </style>
                <p style="
                    font-size: 32px;
                    font-weight: 400;
                    color: #ffffff;
                    margin: 0;
                    line-height: 1.4;
                    font-family: 'Playfair Display', serif; 
                    /* Fallback to serif if Google Fonts not loaded, but system serif usually looks okay */
                    font-style: italic;
                    text-shadow: 0 4px 10px rgba(0,0,0,0.5);
                    letter-spacing: 0.5px;
                ">
                    "One cannot think well, love well, sleep well, if one has not dined well."
                </p>
                <div style="
                    margin-top: 20px;
                    width: 60px;
                    height: 2px;
                    background: #ff7e8b; /* Tomato color */
                    margin-left: auto;
                    margin-right: auto;
                    margin-bottom: 20px;
                "></div>
                <div style="
                    font-size: 14px;
                    color: rgba(255, 255, 255, 0.8);
                    font-weight: 600;
                    text-transform: uppercase;
                    letter-spacing: 3px;
                ">Virginia Woolf</div>
            </div>
        </div>
    </div>
</section>

<section class="categories">
    <div class="container">
        <div class="categories-grid">
            <div class="category-card" onclick="window.location.href='search.php?type=dine-in'">
                <img src="https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?w=400&h=300&fit=crop" alt="Go out for a meal" class="category-image">
                <h3 class="category-title">Go out for a meal</h3>
            </div>
            
            <div class="category-card" onclick="window.location.href='search.php?type=nightlife'">
                <img src="https://images.unsplash.com/photo-1566417713940-fe7c737a9ef2?w=400&h=300&fit=crop" alt="Nightlife & Clubs" class="category-image">
                <h3 class="category-title">Nightlife & Clubs</h3>
            </div>
            
            <div class="category-card" onclick="window.location.href='search.php?type=pro'">
                <img src="https://images.unsplash.com/photo-1414235077428-338989a2e8c0?w=400&h=300&fit=crop" alt="Tomato Pro" class="category-image">
                <h3 class="category-title">Tomato Pro</h3>
            </div>
            
            <div class="category-card" onclick="window.location.href='search.php?type=delivery'">
                <img src="https://images.unsplash.com/photo-1526367790999-0150786686a2?w=400&h=300&fit=crop" alt="Order Food Online" class="category-image">
                <h3 class="category-title">Order Food Online</h3>
            </div>
        </div>
    </div>
</section>

<section class="restaurants">
    <div class="container">
        <h2 class="section-title">Popular Restaurants in Kolkata</h2>
        
        <div class="restaurant-grid">
            <?php while ($restaurant = $restaurantsResult->fetch_assoc()): ?>
                <div class="restaurant-card" onclick="window.location.href='restaurant.php?id=<?php echo $restaurant['restaurant_id']; ?>'">
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
    </div>
</section>

<?php
$conn->close();
include __DIR__ . '/../components/chatbot-widget.php';
include __DIR__ . '/../components/footer.php';
?>
