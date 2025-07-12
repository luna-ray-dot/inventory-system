<?php
 include 'auth.php'; 
include 'sidebar.php'; 

// weekly_sales.php - Show total sales for the past 7 days
include 'db/db.php';

$week_start = date('Y-m-d', strtotime('-6 days'));
$today = date('Y-m-d');

$sql = "SELECT DATE(sale_date) as sale_date, SUM(quantity_sold * sold_price) as total_sales
        FROM sales
        WHERE DATE(sale_date) BETWEEN ? AND ?
        GROUP BY DATE(sale_date)
        ORDER BY sale_date DESC";


$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $week_start, $today);
$stmt->execute();
$result = $stmt->get_result();

$total = 0;
$sales = [];
while ($row = $result->fetch_assoc()) {
    $sales[] = $row;
    $total += $row['total_sales'];
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Weekly Sales</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        body { font-family: Arial; background: #f4faff; color: #333; padding: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; border-bottom: 1px solid #ccc; text-align: left; }
        th { background-color: #007bff; color: white; }
        tr:hover { background-color: #e6f0ff; }
        h2 { text-align: center; }
        .total { font-size: 20px; font-weight: bold; text-align: right; margin-top: 20px; }
    </style>
</head>
<body>
    <h2>Weekly Sales Report</h2>
    <p><strong>From:</strong> <?php echo $week_start; ?> <strong>To:</strong> <?php echo $today; ?></p>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Total Sales (XAF)</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($sales as $s): ?>
            <tr>
                <td><?php echo $s['sale_date']; ?></td>
                <td>XAF<?php echo number_format($s['total_sales'], 2); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <div class="total">Total for Week: XAF<?php echo number_format($total, 2); ?></div>
</body>
</html>

