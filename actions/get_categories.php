<?php
require_once '../includes/config.php';
requireLogin();
$uid = $_SESSION['user_id'];
$type = $_GET['type'] ?? 'expense';
$type = in_array($type, ['income','expense']) ? $type : 'expense';

$result = $conn->query("SELECT id, name, icon, color FROM categories WHERE user_id=$uid AND type='$type' ORDER BY name");
$cats = [];
while ($row = $result->fetch_assoc()) {
    $cats[] = $row;
}

header('Content-Type: application/json');
echo json_encode($cats);
?>
