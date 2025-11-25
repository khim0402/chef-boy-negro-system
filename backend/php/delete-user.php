<?php
header('Content-Type: application/json');
require_once(__DIR__ . '/db.php');

$id = $_POST['id'] ?? '';

if (!$id) {
    echo json_encode(["status" => "error", "message" => "Missing ID"]);
    exit;
}

try {
    $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    echo json_encode(["status" => "success"]);
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>
