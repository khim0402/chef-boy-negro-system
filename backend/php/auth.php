<?php
session_start();
header('Content-Type: application/json');
require_once(__DIR__ . '/db.php');

$data = json_decode(file_get_contents("php://input"), true);
$email = trim(strtolower($data['email'] ?? ''));
$password = hash('sha256', trim($data['password'] ?? ''));

$stmt = $conn->prepare("SELECT user_id, role FROM users WHERE email = ? AND password = ?");
$stmt->bind_param("ss", $email, $password);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows === 1) {
  $row = $result->fetch_assoc();
  $role = strtolower($row['role']);

  $_SESSION['user_id'] = $row['user_id'];
  $_SESSION['email'] = $email;
  $_SESSION['role'] = $role;

  if ($role === 'admin') {
    echo json_encode(['status' => 'success', 'redirect' => '../html/dashboard.html']);
  } elseif ($role === 'cashier') {
    echo json_encode(['status' => 'success', 'redirect' => '../html/pos.html']);
  } elseif ($role === 'manager') {
    echo json_encode(['status' => 'success', 'redirect' => '../html/dashboard.html']);
  } else {
    echo json_encode(['status' => 'error', 'message' => 'Unknown role']);
  }
} else {
  echo json_encode(['status' => 'error', 'message' => 'Invalid email or password']);
}
?>
