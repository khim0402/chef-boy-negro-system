<?php
session_start();
header('Content-Type: application/json');
require_once(__DIR__ . '/db.php');

$data = json_decode(file_get_contents("php://input"), true);
$email = trim(strtolower($data['email'] ?? ''));
$password = trim($data['password'] ?? '');

try {
    $stmt = $pdo->prepare("SELECT user_id, role, password FROM users WHERE email = :email");
    $stmt->execute([':email' => $email]);
    $row = $stmt->fetch();

    if ($row && password_verify($password, $row['password'])) {
        $_SESSION['user_id'] = $row['user_id'];
        $_SESSION['email'] = $email;
        $_SESSION['role'] = strtolower($row['role']);

        $redirect = ($_SESSION['role'] === 'cashier') ? '../html/pos.html' : '../html/dashboard.html';
        echo json_encode(['status' => 'success', 'redirect' => $redirect]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid email or password']);
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
