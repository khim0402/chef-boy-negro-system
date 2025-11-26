<?php
header('Content-Type: application/json');
require_once(__DIR__ . '/db.php');

$email    = trim($_POST['email'] ?? '');
$password = trim($_POST['password'] ?? '');
$roleRaw  = trim($_POST['role'] ?? '');

// Normalize role to lowercase for DB consistency
$role = strtolower($roleRaw);

if (!$email || !$password || !$role) {
    echo json_encode(["status" => "error", "message" => "Missing fields"]);
    exit;
}

// Ensure role is one of the allowed values
$validRoles = ['admin', 'manager', 'cashier'];
if (!in_array($role, $validRoles, true)) {
    echo json_encode(["status" => "error", "message" => "Invalid role"]);
    exit;
}

// Block duplicate emails
$stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = :email");
$stmtCheck->execute([':email' => $email]);
if ((int)$stmtCheck->fetchColumn() > 0) {
    echo json_encode(["status" => "error", "message" => "Email already exists"]);
    exit;
}

// Keep SHA-256 as requested
$hashed = hash("sha256", $password);

try {
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
    // Return the actual SQL error for debugging in dev; keep generic if you prefer
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
