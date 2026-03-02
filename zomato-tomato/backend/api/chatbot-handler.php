<?php
require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json');

$action = $_POST['action'] ?? '';

$conn = getDBConnection();

if ($action === 'recommend') {
    // Get location-based and preference-based recommendations
    $location = sanitizeInput($_POST['location'] ?? '');
    $preference = sanitizeInput($_POST['preference'] ?? '');
    $priceRange = sanitizeInput($_POST['price_range'] ?? 'any');
    $dietary = sanitizeInput($_POST['dietary'] ?? 'both');
    
    $recommendations = [];
    
    // Map preferences to cuisine types and menu categories
    $preferenceMapping = [
        'spicy' => [
            'cuisines' => ['North Indian', 'South Indian', 'Chinese'],
            'keywords' => ['spicy', 'chili', 'masala', 'hot']
        ],
        'fast food' => [
            'cuisines' => ['Continental', 'American'],
            'keywords' => ['burger', 'pizza', 'fries', 'sandwich']
        ],
        'drinks' => [
            'cuisines' => ['Cafe', 'Continental'],
            'keywords' => ['coffee', 'shake', 'juice', 'cold coffee', 'smoothie']
        ],
        'chinese' => [
            'cuisines' => ['Chinese'],
            'keywords' => ['noodles', 'fried rice', 'manchurian', 'chowmein']
        ],
        'bengali' => [
            'cuisines' => ['Bengali'],
            'keywords' => ['fish', 'rice', 'curry', 'biryani']
        ],
        'dessert' => [
            'cuisines' => ['Dessert', 'Cafe'],
            'keywords' => ['ice cream', 'cake', 'sweet', 'dessert']
        ],
        'italian' => [
            'cuisines' => ['Italian', 'Continental'],
            'keywords' => ['pasta', 'pizza', 'lasagna', 'spaghetti']
        ],
        'mexican' => [
            'cuisines' => ['Mexican', 'Continental'],
            'keywords' => ['tacos', 'burrito', 'nachos', 'quesadilla']
        ],
        'healthy' => [
            'cuisines' => ['Continental', 'Cafe'],
            'keywords' => ['salad', 'soup', 'grilled', 'healthy']
        ],
        'breakfast' => [
            'cuisines' => ['Continental', 'Cafe'],
            'keywords' => ['pancake', 'omelette', 'toast', 'breakfast']
        ],
        'snacks' => [
            'cuisines' => ['North Indian', 'South Indian', 'Chinese'],
            'keywords' => ['samosa', 'pakora', 'spring roll', 'finger food']
        ]
    ];
    
    // Price range mapping
    $priceRanges = [
        'budget' => ['min' => 0, 'max' => 200],
        'mid-range' => ['min' => 200, 'max' => 500],
        'premium' => ['min' => 500, 'max' => 9999]
    ];
    
    $mapping = $preferenceMapping[$preference] ?? null;
    
    if ($mapping) {
        // Get restaurants matching the criteria
        $cuisineList = "'" . implode("','", $mapping['cuisines']) . "'";
        
        // Build query with price and dietary filters
        $query = "SELECT DISTINCT r.restaurant_id, r.name as restaurant_name, r.cuisine_type, r.rating, r.locality,
                  (SELECT AVG(mi.price) FROM menu_items mi WHERE mi.restaurant_id = r.restaurant_id AND mi.is_available = TRUE) as avg_price
                  FROM restaurants r
                  WHERE r.locality = ? 
                  AND r.is_active = TRUE
                  AND r.cuisine_type IN ($cuisineList)";
        
        // Add price range filter
        if ($priceRange !== 'any' && isset($priceRanges[$priceRange])) {
            $minPrice = $priceRanges[$priceRange]['min'];
            $maxPrice = $priceRanges[$priceRange]['max'];
            $query .= " AND EXISTS (
                SELECT 1 FROM menu_items mi 
                WHERE mi.restaurant_id = r.restaurant_id 
                AND mi.price BETWEEN $minPrice AND $maxPrice
                AND mi.is_available = TRUE
            )";
        }
        
        $query .= " ORDER BY r.rating DESC LIMIT 5";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $location);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            // Get best dishes for this restaurant based on preference and dietary filter
            $keywords = $mapping['keywords'];
            $keywordConditions = [];
            foreach ($keywords as $keyword) {
                $keywordConditions[] = "item_name LIKE '%" . $conn->real_escape_string($keyword) . "%' OR description LIKE '%" . $conn->real_escape_string($keyword) . "%'";
            }
            $keywordSQL = implode(' OR ', $keywordConditions);
            
            $dishQuery = "SELECT item_name, price FROM menu_items 
                          WHERE restaurant_id = ? 
                          AND is_available = TRUE
                          AND ($keywordSQL)";
            
            // Add dietary filter
            if ($dietary === 'veg') {
                $dishQuery .= " AND is_veg = TRUE";
            } elseif ($dietary === 'non-veg') {
                $dishQuery .= " AND is_veg = FALSE";
            }
            
            // Add price filter for dishes
            if ($priceRange !== 'any' && isset($priceRanges[$priceRange])) {
                $minPrice = $priceRanges[$priceRange]['min'];
                $maxPrice = $priceRanges[$priceRange]['max'];
                $dishQuery .= " AND price BETWEEN $minPrice AND $maxPrice";
            }
            
            $dishQuery .= " ORDER BY price DESC LIMIT 3";
            
            $dishStmt = $conn->prepare($dishQuery);
            $dishStmt->bind_param("i", $row['restaurant_id']);
            $dishStmt->execute();
            $dishResult = $dishStmt->get_result();
            
            $bestDishes = [];
            while ($dish = $dishResult->fetch_assoc()) {
                $bestDishes[] = $dish['item_name'];
            }
            $dishStmt->close();
            
            // If no specific dishes found, get top-rated items
            if (empty($bestDishes)) {
                $topDishQuery = "SELECT item_name FROM menu_items 
                                 WHERE restaurant_id = ? 
                                 AND is_available = TRUE
                                 ORDER BY price DESC
                                 LIMIT 2";
                
                $topStmt = $conn->prepare($topDishQuery);
                $topStmt->bind_param("i", $row['restaurant_id']);
                $topStmt->execute();
                $topResult = $topStmt->get_result();
                
                while ($dish = $topResult->fetch_assoc()) {
                    $bestDishes[] = $dish['item_name'];
                }
                $topStmt->close();
            }
            
            $row['best_dishes'] = $bestDishes;
            $recommendations[] = $row;
        }
        
        $stmt->close();
    }
    
    echo json_encode([
        'success' => true,
        'recommendations' => $recommendations,
        'count' => count($recommendations)
    ]);
    
} elseif ($action === 'suggest') {
    // Legacy support for locality suggestions
    $locality = sanitizeInput($_POST['locality'] ?? '');
    
    $stmt = $conn->prepare("SELECT name, cuisine_type, rating FROM restaurants WHERE locality = ? AND is_active = TRUE ORDER BY rating DESC LIMIT 3");
    $stmt->bind_param("s", $locality);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $suggestions = [];
    while ($restaurant = $result->fetch_assoc()) {
        $suggestions[] = [
            'type' => 'restaurant',
            'name' => $restaurant['name'],
            'cuisine' => $restaurant['cuisine_type'],
            'rating' => $restaurant['rating']
        ];
    }
    
    $stmt->close();
    echo json_encode(['success' => true, 'suggestions' => $suggestions]);
    
} else {
    // General chat response
    $message = strtolower(sanitizeInput($_POST['message'] ?? ''));
    
    $response = '';
    
    if (strpos($message, 'hello') !== false || strpos($message, 'hi') !== false) {
        $response = "Hello! 👋 I'm here to help you find delicious food. Tell me your locality and I'll suggest the best options!";
    } elseif (strpos($message, 'help') !== false) {
        $response = "I can help you find:\n• Best restaurants in your area\n• Spicy food, fast food, drinks\n• Food recommendations\n\nJust tell me your locality!";
    } else {
        $response = "I can help you find great food! Try asking me about:\n• Specific cuisines (Chinese, Bengali, etc.)\n• Restaurants in your locality\n• Food recommendations";
    }
    
    echo json_encode(['success' => true, 'message' => $response]);
}

$conn->close();
?>
