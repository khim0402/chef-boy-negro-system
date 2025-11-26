<?php
header('Content-Type: application/json');
require_once(__DIR__ . '/db.php');

try {
    $stmt = $pdo->query("SELECT user_id, email, role, created_at FROM users ORDER BY user_id DESC");
    $users = $stmt->fetchAll();

    foreach ($users as &$u) {
        // Capitalize role for UI while keeping DB normalized
        $u['role'] = ucfirst(strtolower($u['role']));
    }

    echo json_encode($users);
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
