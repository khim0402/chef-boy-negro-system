<?php
header('Content-Type: application/json; charset=utf-8');
require_once(__DIR__ . '/../../php/db.php');

try {
    $daily = $weekly = $monthly = 0;

    // Daily total
    $res = $conn->query("SELECT SUM(amount) AS total FROM sales_items WHERE DATE(created_at) = CURDATE()");
    if ($res && $res->num_rows > 0) {
        $row = $res->fetch_assoc();
        $daily = (float)$row['total'];
    }

    // Weekly total
    $res = $conn->query("SELECT SUM(amount) AS total FROM sales_items WHERE YEARWEEK(created_at, 1) = YEARWEEK(CURDATE(), 1)");
    if ($res && $res->num_rows > 0) {
        $row = $res->fetch_assoc();
        $weekly = (float)$row['total'];
    }

    // Monthly total
    $res = $conn->query("SELECT SUM(amount) AS total FROM sales_items WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())");
    if ($res && $res->num_rows > 0) {
        $row = $res->fetch_assoc();
        $monthly = (float)$row['total'];
    }

    // 📅 Read filter params
    $start = $_GET['start'] ?? null;
    $end   = $_GET['end'] ?? null;

    $sales = [];
    $query = "SELECT s.created_at AS date, si.qty, si.amount, s.payment_method, i.name AS product
              FROM sales_items si
              JOIN sales s ON si.sales_id = s.id
              JOIN inventory i ON si.product_id = i.product_id";

    if ($start && $end) {
        $query .= " WHERE DATE(s.created_at) BETWEEN ? AND ? ORDER BY s.created_at DESC";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ss", $start, $end);
        $stmt->execute();
        $res = $stmt->get_result();
    } else {
        $query .= " ORDER BY s.created_at DESC LIMIT 50";
        $res = $conn->query($query);
    }

    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $sales[] = [
                "date" => $row['date'],
                "product" => $row['product'],
                "method" => $row['payment_method'],
                "qty" => (int)$row['qty'],
                "amount" => (float)$row['amount']
            ];
        }
    }

    echo json_encode([
        "status" => "success",
        "sales" => $sales,
        "daily" => $daily,
        "weekly" => $weekly,
        "monthly" => $monthly
    ]);
} catch (Exception $e) {
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}
?>
