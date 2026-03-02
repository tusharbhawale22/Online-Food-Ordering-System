<?php
require_once __DIR__ . '/../../backend/config/config.php';

$conn = getDBConnection();

$updates = [
    29 => 'images/menu_items/item_29.jpg', // Vegetable Samosa
    30 => 'images/menu_items/item_30.jpg'  // Gulab Jamun
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
