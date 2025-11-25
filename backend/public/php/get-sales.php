<?php
header('Content-Type: application/json; charset=utf-8');
require_once(__DIR__ . '/../../php/db.php');

try {
    $daily = $weekly = $monthly = 0;

    $res = $pdo->query("SELECT SUM(amount) AS total FROM sales_items WHERE DATE(created_at) = CURRENT_DATE");
    $daily = (float)($res->fetch()['total'] ?? 0);

    $res = $pdo->query("SELECT SUM(amount) AS total FROM sales_items WHERE DATE_TRUNC('week', created_at) = DATE_TRUNC('week', CURRENT_DATE)");
    $weekly = (float)($res->fetch()['total'] ?? 0);

    $res = $pdo->query("SELECT SUM(amount) AS total FROM sales_items WHERE DATE_TRUNC('month', created_at) = DATE_TRUNC('month', CURRENT_DATE)");
    $monthly = (float)($res->fetch()['total'] ?? 0);

    $start = $_GET['start'] ?? null;
    $end   = $_GET['end'] ?? null;

    $sales = [];
    if ($start && $end) {
        $stmt = $pdo->prepare("SELECT s.created_at AS date, si.qty, si.amount, s.payment_method, i.name AS product
                               FROM sales_items si
                               JOIN sales s ON si.sales_id = s.id
                               JOIN inventory i ON si.product_id = i.product_id
                               WHERE DATE(s.created_at) BETWEEN :start AND :end
                               ORDER BY s.created_at DESC");
        $stmt->execute([':start' => $start, ':end' => $end]);
        $sales = $stmt->fetchAll();
    } else {
        $sales = $pdo->query("SELECT s.created_at AS date, si.qty, si.amount, s.payment_method, i.name AS product
                              FROM sales_items si
                              JOIN sales s ON si.sales_id = s.id
                              JOIN inventory i ON si.product_id = i.product_id
                              ORDER BY s.created_at DESC LIMIT 50")->fetchAll();
    }

    echo json_encode([
        "status" => "success",
        "sales" => $sales,
        "daily" => $daily,
        "weekly" => $weekly,
        "monthly" => $monthly
    ]);
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>
