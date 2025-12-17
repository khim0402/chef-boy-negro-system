<?php
header('Content-Type: application/json; charset=utf-8');
require_once(__DIR__ . '/db.php');

try {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input || empty($input['email']) || empty($input['password'])) {
        throw new Exception("Missing email or password.");
    }

    $email = trim($input['email']);
    $password = trim($input['password']);

    // Fetch user record including username
    $stmt = $pdo->prepare("
        SELECT user_id, email, username, role, password
        FROM users
        WHERE email = :email
        LIMIT 1
    ");
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        throw new Exception("Invalid credentials.");
    }

    // Verify password (assuming stored as SHA256 hex string)
    $hashedInput = hash('sha256', $password);
    if (!hash_equals($user['password'], $hashedInput)) {
        throw new Exception("Invalid credentials.");
    }

    // Success: return user info
    echo json_encode([
        "status" => "success",
        "redirect" => "../html/pos.html",
        "user" => [
            "user_id" => $user['user_id'],
            "email"   => $user['email'],
            "username"=> $user['username'], // 👈 critical for POS header
            "role"    => $user['role']
        ]
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}
