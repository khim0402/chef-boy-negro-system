<?php
header('Content-Type: application/json');
require_once(__DIR__ . '/db.php');

$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';
$role = $_POST['role'] ?? '';

if (!$email || !$password || !$role) {
    echo json_encode(["status" => "error", "message" => "Missing fields"]);
    exit;
}

$hashed = hash("sha256", $password);

try {
    $stmt = $conn->prepare("INSERT INTO users (email, password, role, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->bind_param("sss", $email, $hashed, $role);
    $stmt->execute();
    echo json_encode(["status" => "success"]);
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>
