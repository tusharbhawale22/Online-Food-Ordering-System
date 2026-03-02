<?php
require_once __DIR__ . '/../../backend/config/config.php';

$conn = getDBConnection();

$updates = [
    27 => 'images/menu_items/item_27.jpg', // Prawn Cocktail
    28 => 'images/menu_items/item_28.jpg'  // Caramel Custard
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
