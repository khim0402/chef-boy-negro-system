<?php
session_start();
header('Content-Type: application/json');
require_once(__DIR__ . '/db.php');

$data = json_decode(file_get_contents("php://input"), true);
$email = trim(strtolower($data['email'] ?? ''));
$password = hash('sha256', trim($data['password'] ?? ''));

try {
    $stmt = $pdo->prepare("SELECT user_id, role FROM users WHERE email = :email AND password = :password");
    $stmt->execute([':email' => $email, ':password' => $password]);
    $row = $stmt->fetch();

    if ($row) {
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
