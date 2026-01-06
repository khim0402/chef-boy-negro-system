<?php
header('Content-Type: application/json; charset=utf-8');
require_once(__DIR__ . '/db.php');

try {
    $start   = $_GET['start'] ?? null;
    $end     = $_GET['end'] ?? null;
    $cashier = $_GET['cashier'] ?? null; // can be user_id, username, or email

    // ----------------------------
    // Sales History
    // ----------------------------
    $where = [];
    $params = [];

    if ($start && $end) {
        $where[] = "DATE(si.created_at) BETWEEN :start AND :end";
        $params[':start'] = $start;
        $params[':end']   = $end;
    }

    if (!empty($cashier)) {
        $where[] = "(s.cashier_id = :cashier OR u.username = :cashier OR u.email = :cashier)";
        $params[':cashier'] = $cashier;
    }

    $whereSql = count($where) ? "WHERE " . implode(" AND ", $where) : "";

    $sql = "
        SELECT 
            si.created_at::timestamp(0) AS date,
            i.name AS product,
            s.payment_method,
            si.qty,
            si.amount,
            u.username AS cashier
        FROM sales_items si
        JOIN sales s ON si.sales_id = s.id
        JOIN inventory i ON si.product_id = i.product_id
        LEFT JOIN users u ON s.cashier_id = u.user_id
        $whereSql
        ORDER BY si.created_at DESC
    ";

    if (!$start || !$end) {
        $sql .= " LIMIT 50"; // avoid huge results
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $sales = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ----------------------------
    // Totals (Daily, Weekly, Monthly)
    // ----------------------------
    function getTotals($pdo, $interval, $cashier = null) {
        $params = [];
        $where = [];

        if (!empty($cashier)) {
            $where[] = "(s.cashier_id = :cashier OR u.username = :cashier OR u.email = :cashier)";
            $params[':cashier'] = $cashier;
        }

        $whereSql = count($where) ? "AND " . implode(" AND ", $where) : "";

        $sql = "
            SELECT s.cashier_id, u.username, COALESCE(SUM(si.amount),0) AS total
            FROM sales_items si
            JOIN sales s ON si.sales_id = s.id
            LEFT JOIN users u ON s.cashier_id = u.user_id
            WHERE DATE_TRUNC('$interval', si.created_at) = DATE_TRUNC('$interval', CURRENT_DATE)
            $whereSql
            GROUP BY s.cashier_id, u.username
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    $daily   = getTotals($pdo, 'day', $cashier);
    $weekly  = getTotals($pdo, 'week', $cashier);
    $monthly = getTotals($pdo, 'month', $cashier);

    // ----------------------------
    // Response
    // ----------------------------
    echo json_encode([
        "status"  => "success",
        "sales"   => $sales,
        "daily"   => $daily,
        "weekly"  => $weekly,
        "monthly" => $monthly
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}
?>
