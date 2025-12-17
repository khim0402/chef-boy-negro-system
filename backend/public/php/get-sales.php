<?php
header('Content-Type: application/json; charset=utf-8');
require_once(__DIR__ . '/../../php/db.php');

try {
    $start   = $_GET['start'] ?? null;
    $end     = $_GET['end'] ?? null;
    $cashier = $_GET['cashier'] ?? null;

    $params = [];
    $where = [];

    if ($start && $end) {
        $where[] = "DATE(si.created_at) BETWEEN :start AND :end";
        $params[':start'] = $start;
        $params[':end']   = $end;
    }
    if (!empty($cashier)) {
        $where[] = "s.cashier_id = :cashier";
        $params[':cashier'] = $cashier;
    }

    $whereSql = count($where) ? ("WHERE " . implode(" AND ", $where)) : "";

    // Sales list
    $sql = "
        SELECT 
            si.created_at::timestamp(0) AS date,
            si.qty,
            si.amount,
            s.payment_method,
            i.name AS product,
            u.username AS cashier
        FROM sales_items si
        JOIN sales s ON si.sales_id = s.id
        JOIN inventory i ON si.product_id = i.product_id
        LEFT JOIN users u ON s.cashier_id = u.user_id
        $whereSql
        ORDER BY si.created_at DESC
    ";
    if (!$start || !$end) {
        $sql .= " LIMIT 50";
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $sales = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Grouped totals
    $stmtDaily = $pdo->query("
        SELECT s.cashier_id, u.username, COALESCE(SUM(si.amount), 0) AS total
        FROM sales_items si
        JOIN sales s ON si.sales_id = s.id
        LEFT JOIN users u ON s.cashier_id = u.user_id
        WHERE DATE(si.created_at) = CURRENT_DATE
        GROUP BY s.cashier_id, u.username
    ");
    $dailyTotals = $stmtDaily->fetchAll(PDO::FETCH_ASSOC);

    $stmtWeekly = $pdo->query("
        SELECT s.cashier_id, u.username, COALESCE(SUM(si.amount), 0) AS total
        FROM sales_items si
        JOIN sales s ON si.sales_id = s.id
        LEFT JOIN users u ON s.cashier_id = u.user_id
        WHERE DATE_TRUNC('week', si.created_at) = DATE_TRUNC('week', CURRENT_DATE)
        GROUP BY s.cashier_id, u.username
    ");
    $weeklyTotals = $stmtWeekly->fetchAll(PDO::FETCH_ASSOC);

    $stmtMonthly = $pdo->query("
        SELECT s.cashier_id, u.username, COALESCE(SUM(si.amount), 0) AS total
        FROM sales_items si
        JOIN sales s ON si.sales_id = s.id
        LEFT JOIN users u ON s.cashier_id = u.user_id
        WHERE DATE_TRUNC('month', si.created_at) = DATE_TRUNC('month', CURRENT_DATE)
        GROUP BY s.cashier_id, u.username
    ");
    $monthlyTotals = $stmtMonthly->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "status"  => "success",
        "sales"   => $sales,
        "daily"   => $dailyTotals,
        "weekly"  => $weeklyTotals,
        "monthly" => $monthlyTotals
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>
