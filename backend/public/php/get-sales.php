<?php
header('Content-Type: application/json; charset=utf-8');
require_once(__DIR__ . '/../../php/db.php');

try {
    // Daily, weekly, monthly totals based on sales_items.created_at
    $res = $pdo->query("SELECT SUM(amount) AS total FROM sales_items WHERE DATE(created_at) = CURRENT_DATE");
    $daily = (float)($res->fetch()['total'] ?? 0);

    $res = $pdo->query("SELECT SUM(amount) AS total FROM sales_items WHERE DATE_TRUNC('week', created_at) = DATE_TRUNC('week', CURRENT_DATE)");
    $weekly = (float)($res->fetch()['total'] ?? 0);

    $res = $pdo->query("SELECT SUM(amount) AS total FROM sales_items WHERE DATE_TRUNC('month', created_at) = DATE_TRUNC('month', CURRENT_DATE)");
    $monthly = (float)($res->fetch()['total'] ?? 0);

    // Date filters
    $start = $_GET['start'] ?? null;
    $end   = $_GET['end'] ?? null;

    if ($start && $end) {
        $stmt = $pdo->prepare("
            SELECT s.created_at AS date, si.qty, si.amount, s.payment_method, i.name AS product
            FROM sales_items si
            JOIN sales s ON si.sales_id = s.id
            JOIN inventory i ON si.product_id = i.product_id
            WHERE DATE(si.created_at) BETWEEN :start AND :end
            ORDER BY si.created_at DESC
        ");
        $stmt->execute([':start' => $start, ':end' => $end]);
        $sales = $stmt->fetchAll();
    } else {
        $sales = $pdo->query("
            SELECT s.created_at AS date, si.qty, si.amount, s.payment_method, i.name AS product
            FROM sales_items si
            JOIN sales s ON si.sales_id = s.id
            JOIN inventory i ON si.product_id = i.product_id
            ORDER BY si.created_at DESC
            LIMIT 50
        ")->fetchAll();
    }

    echo json_encode([
        "status" => "success",
        "sales" => $sales,
        "daily" => $daily,
        "weekly" => $weekly,
        "monthly" => $monthly
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>
