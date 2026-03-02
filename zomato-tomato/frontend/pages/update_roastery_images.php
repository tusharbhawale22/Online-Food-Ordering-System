<?php
require_once __DIR__ . '/../../backend/config/config.php';

$conn = getDBConnection();

$updates = [
    23 => 'images/menu_items/item_23.jpg', // Cheesecake
    24 => 'images/menu_items/item_24.jpg', // Avocado Toast
    25 => 'images/menu_items/item_25.jpg', // Cold Brew
    26 => 'images/menu_items/item_26.jpg'  // Chicken Lasagna
];

$stmt = $conn->prepare("UPDATE menu_items SET image_url = ? WHERE item_id = ?");

foreach ($updates as $id => $path) {
    echo "Updating item $id to $path...\n";
    $stmt->bind_param("si", $path, $id);
    $stmt->execute();
}
echo "Done.\n";
$conn->close();
?>
