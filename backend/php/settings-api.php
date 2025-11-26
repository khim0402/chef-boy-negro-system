<?php
header('Content-Type: application/json');
require_once(__DIR__ . '/db.php');

$email    = trim($_POST['email'] ?? '');
$password = trim($_POST['password'] ?? '');
$role     = trim($_POST['role'] ?? '');

if (!$email || !$password || !$role) {
    echo json_encode(["status" => "error", "message" => "Missing fields"]);
    exit;
}

$hashed = hash("sha256", $password);

try {
    // 🔍 Check for duplicate email
    $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = :email");
    $stmtCheck->execute([':email' => $email]);
    if ((int)$stmtCheck->fetchColumn() > 0) {
        throw new Exception("Email already exists.");
    }

    // ✅ Insert new user
    $stmt = $pdo->prepare("
        INSERT INTO users (email, password, role, created_at)
        VALUES (:email, :password, :role, NOW())
    ");
    $stmt->execute([
        ':email'    => $email,
        ':password' => $hashed,
        ':role'     => $role
    ]);

    echo json_encode(["status" => "success"]);
} catch (Exception $e) {
    // Return the actual SQL error message for debugging
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>
