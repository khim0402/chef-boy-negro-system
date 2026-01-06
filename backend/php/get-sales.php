<?php
header('Content-Type: application/json; charset=utf-8');
require_once(__DIR__ . '/db.php');

try {
    // Optional filters
    $start   = $_GET['start'] ?? null;
    $end     = $_GET['end'] ?? null;
    $cashier = $_GET['cashier'] ?? null; // cashier_id

    $whereTotals = [];
    $paramsTotals = [];

    if (!empty($cashier)) {
        $whereTotals[] = "cashier_id = :cashier";
        $paramsTotals[':cashier'] = $cashier;
    }
    $whereTotalsSql = count($whereTotals) ? ("WHERE " . implode(" AND ", $whereTotals)) : "";

    // Daily, weekly, monthly totals based on sales_items.created_at, filtered by cashier if provided
    $stmtDaily = $pdo->prepare("
        SELECT COALESCE(SUM(si.amount), 0) AS total
        FROM sales_items si
        JOIN sales s ON si.sales_id = s.id
        $whereTotalsSql
        AND DATE(si.created_at) = CURRENT_DATE
    ");
    $stmtWeekly = $pdo->prepare("
        SELECT COALESCE(SUM(si.amount), 0) AS total
        FROM sales_items si
        JOIN sales s ON si.sales_id = s.id
        $whereTotalsSql
        AND DATE_TRUNC('week', si.created_at) = DATE_TRUNC('week', CURRENT_DATE)
    ");
    $stmtMonthly = $pdo->prepare("
        SELECT COALESCE(SUM(si.amount), 0) AS total
        FROM sales_items si
        JOIN sales s ON si.sales_id = s.id
        $whereTotalsSql
        AND DATE_TRUNC('month', si.created_at) = DATE_TRUNC('month', CURRENT_DATE)
    ");

    // Bind params for totals
    foreach ([$stmtDaily, $stmtWeekly, $stmtMonthly] as $stmt) {
        foreach ($paramsTotals as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->execute();
    }

    $daily   = (float)($stmtDaily->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
    $weekly  = (float)($stmtWeekly->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
    $monthly = (float)($stmtMonthly->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

    // Sales list with optional date range and cashier filter
    $where = [];
    $params = [];

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

    $sql = "
        SELECT 
            si.created_at::timestamp(0) AS date,
            si.qty,
            si.amount,
            s.payment_method,
            i.name AS product,
            u.email AS cashier
        FROM sales_items si
        JOIN sales s ON si.sales_id = s.id
        JOIN inventory i ON si.product_id = i.product_id
        LEFT JOIN users u ON s.cashier_id = u.user_id
        $whereSql
        ORDER BY si.created_at DESC
    ";

    // Default limit if no date filter to avoid huge results
    if (!$start || !$end) {
        $sql .= " LIMIT 50";
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $sales = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "status"  => "success",
        "sales"   => $sales,
        "daily"   => $daily,
        "weekly"  => $weekly,
        "monthly" => $monthly
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>
