<?php
include 'auth.php';
include 'db/db.php';

$filter = '';
$filterLabel = 'All Time';

if (isset($_GET['filter'])) {
    $type = $_GET['filter'];
    if ($type == 'today') {
        $filter = "WHERE DATE(restock_date) = CURDATE()";
        $filterLabel = "Today";
    } elseif ($type == 'week') {
        $filter = "WHERE YEARWEEK(restock_date, 1) = YEARWEEK(CURDATE(), 1)";
        $filterLabel = "This Week";
    } elseif ($type == 'month') {
        $filter = "WHERE MONTH(restock_date) = MONTH(CURDATE()) AND YEAR(restock_date) = YEAR(CURDATE())";
        $filterLabel = "This Month";
    }
}

$query = "SELECT r.*, p.name AS product_name 
          FROM restocks r 
          JOIN products p ON r.product_id = p.id 
          $filter 
          ORDER BY r.restock_date DESC";

$result = $conn->query($query);

if (isset($_GET['export'])) {
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=restock_history_{$filterLabel}.xls");
    header("Pragma: no-cache");
    header("Expires: 0");

    echo "Product\tQuantity\tRestock Date\n";
    while ($row = $result->fetch_assoc()) {
        $product = htmlspecialchars($row['product_name']);
        $quantity = $row['quantity'];
        $restockDate = date('Y-m-d', strtotime($row['restock_date']));
        echo "{$product}\t{$quantity}\t{$restockDate}\n";
    }
    exit;
}

include 'sidebar.php';
?>

<!DOCTYPE html>
<html>
<head>
    <title>📦 Restock History</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background-color: #f0f4fa;
            margin: 0;
            padding: 20px;
        }

        .container {
            max-width: 1000px;
            margin: auto;
            background: #fff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        h2 {
            margin-top: 0;
            font-size: 28px;
            color: #333;
        }

        .filters {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 20px 0;
        }

        .filter-links a {
            text-decoration: none;
            color: #007bff;
            margin-right: 15px;
            font-weight: 500;
        }

        .filter-links a:hover {
            text-decoration: underline;
        }

        .btn-export {
            background: #28a745;
            color: white;
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
            transition: background 0.3s;
        }

        .btn-export:hover {
            background: #218838;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        th, td {
            padding: 12px;
            text-align: left;
        }

        thead {
            background-color: #007bff;
            color: white;
        }

        tbody tr:nth-child(even) {
            background-color: #f7f9fc;
        }

        tbody tr:hover {
            background-color: #e6f0ff;
        }

        th {
            border-bottom: 2px solid #dee2e6;
        }

        td {
            border-bottom: 1px solid #ddd;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>📦 Restock History <small style="font-size:16px; color: #666;">(<?= $filterLabel ?>)</small></h2>

    <div class="filters">
        <div class="filter-links">
            <a href="?filter=today">Today</a>
            <a href="?filter=week">This Week</a>
            <a href="?filter=month">This Month</a>
            <a href="restock_history.php">All Time</a>
        </div>
        <a class="btn-export" href="?<?= http_build_query(array_merge($_GET, ['export' => 1])) ?>">⬇ Export to Excel</a>
    </div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Product</th>
                <th>Quantity</th>
                <th>Restock Date</th>
            </tr>
        </thead>
        <tbody>
            <?php $i = 1; $result->data_seek(0); while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= $i++ ?></td>
                <td><?= htmlspecialchars($row['product_name']) ?></td>
                <td><?= $row['quantity'] ?></td>
                <td><?= date('Y-m-d H:i', strtotime($row['restock_date'])) ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

</body>
</html>
