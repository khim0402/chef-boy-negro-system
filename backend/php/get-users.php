<?php
header('Content-Type: application/json');
require_once(__DIR__ . '/db.php');

$result = $conn->query("SELECT user_id, email, role, created_at FROM users ORDER BY user_id DESC");
$users = [];

while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}

echo json_encode($users);
?>
