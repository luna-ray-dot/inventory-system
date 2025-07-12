<?php
// --- CLEAN EXCEL EXPORT HANDLER FIRST ---
if (isset($_GET['export']) && $_GET['export'] === 'xls') {
    require 'db/db.php';

    $from = $_GET['from'] ?? date('Y-m-01');
    $to = $_GET['to'] ?? date('Y-m-d');

    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=sales_report_{$from}_to_{$to}.xls");
    header("Pragma: no-cache");
    header("Expires: 0");

    echo "ID\tProduct Name\tQty Sold\tPrice\tDate\n";

    $export = $conn->prepare("SELECT s.id, p.name, s.quantity_sold, s.sold_price, s.sale_date 
                              FROM sales s 
                              JOIN products p ON s.product_id = p.id 
                              WHERE s.sale_date BETWEEN ? AND ?");
    $export->bind_param("ss", $from, $to);
    $export->execute();
    $res = $export->get_result();

    while ($row = $res->fetch_assoc()) {
        echo "{$row['id']}\t{$row['name']}\t{$row['quantity_sold']}\t{$row['sold_price']}\t{$row['sale_date']}\n";
    }
    exit;
}

// --- REST OF THE DASHBOARD ---
include 'auth.php';
include 'sidebar.php';
include 'db/db.php';

$from = $_GET['from'] ?? date('Y-m-01');
$to = $_GET['to'] ?? date('Y-m-d');

// Total products
$total_products = $conn->query("SELECT COUNT(*) AS total FROM products")->fetch_assoc()['total'];

// Total sales
$total_sales = $conn->query("SELECT SUM(quantity_sold * sold_price) AS total FROM sales")->fetch_assoc()['total'] ?? 0;

// Low stock count
$low_stock = $conn->query("SELECT COUNT(*) AS total FROM products WHERE quantity <= low_stock_threshold")->fetch_assoc()['total'];

// Weekly sales trend
$week_start = date('Y-m-d', strtotime('-6 days'));
$today = date('Y-m-d');
$weekly = $conn->prepare("SELECT DATE(sale_date) as day, SUM(quantity_sold * sold_price) as total FROM sales WHERE sale_date BETWEEN ? AND ? GROUP BY day ORDER BY day ASC");
$weekly->bind_param("ss", $week_start, $today);
$weekly->execute();
$weekly_result = $weekly->get_result();
$week_labels = [];
$week_totals = [];
while ($row = $weekly_result->fetch_assoc()) {
    $week_labels[] = $row['day'];
    $week_totals[] = $row['total'];
}

// Product-wise sales
$product_sales = $conn->query("SELECT p.name, SUM(s.quantity_sold) as sold_qty FROM sales s JOIN products p ON s.product_id = p.id GROUP BY s.product_id ORDER BY sold_qty DESC");
$product_labels = [];
$product_quantities = [];
while ($row = $product_sales->fetch_assoc()) {
    $product_labels[] = $row['name'];
    $product_quantities[] = $row['sold_qty'];
}

// Profit breakdown
$profit_result = $conn->query("SELECT SUM(quantity_sold * sold_price) AS revenue, SUM(quantity_sold * buy_price) AS cost FROM sales JOIN products ON sales.product_id = products.id");
$profit_data = $profit_result->fetch_assoc();
$revenue = $profit_data['revenue'] ?? 0;
$cost = $profit_data['cost'] ?? 0;
$profit = $revenue - $cost;

// Monthly Sales Summary
$monthly = $conn->query("SELECT DATE_FORMAT(sale_date, '%Y-%m') as month, SUM(quantity_sold * sold_price) as total FROM sales GROUP BY month ORDER BY month DESC LIMIT 12");

// Yearly Sales Summary
$yearly = $conn->query("SELECT YEAR(sale_date) as year, SUM(quantity_sold * sold_price) as total FROM sales GROUP BY year ORDER BY year DESC");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Dashboard</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { background: #121212; color: #fff; font-family: Arial, sans-serif; padding: 20px; }
        h1, h2 { color: #00d1ff; }
        .card { background: #1e1e2f; padding: 20px; border-radius: 12px; margin-bottom: 20px; box-shadow: 0 0 10px rgba(0,0,0,0.4); }
        .grid { display: flex; flex-wrap: wrap; gap: 20px; }
        .grid > .card { flex: 1; min-width: 250px; }
        canvas { background: #1e1e2f; padding: 10px; border-radius: 10px; }
        strong { font-size: 24px; }
        table { width: 100%; border-collapse: collapse; color: #fff; }
        table, th, td { border: 1px solid #444; padding: 8px; }
        th { background: #00d1ff; color: #000; }
        input[type="date"], button {
            padding: 6px 10px;
            background: #222;
            border: 1px solid #555;
            color: #fff;
            border-radius: 4px;
        }
        a { color: #00d1ff; text-decoration: none; }
    </style>
</head>
<body>
<h1>📊 Dashboard</h1>

<!-- Date Filters and Export Button -->
<form method="GET">
    From: <input type="date" name="from" value="<?= $from ?>">
    To: <input type="date" name="to" value="<?= $to ?>">
    <button type="submit">Filter</button>
    <a href="?from=<?= $from ?>&to=<?= $to ?>&export=xls" style="margin-left:10px;">📤 Export Excel</a>
</form>

<!-- Summary Cards -->
<div class="grid">
    <div class="card"><h3>Total Products</h3><p><strong><?= $total_products ?></strong></p></div>
    <div class="card"><h3>Total Sales</h3><p><strong>XAF<?= number_format($total_sales, 2) ?></strong></p></div>
    <div class="card"><h3>Low Stock Alerts</h3><p><strong><?= $low_stock ?></strong></p></div>
    <div class="card"><h3>Profit</h3><p><strong>XAF<?= number_format($profit, 2) ?></strong> <br>(Revenue: XAF<?= number_format($revenue, 2) ?> | Cost: XAF<?= number_format($cost, 2) ?>)</p></div>
</div>

<!-- Weekly Sales Trend -->
<div class="card">
    <h2>📅 Weekly Sales Trend</h2>
    <canvas id="weeklyChart"></canvas>
</div>

<!-- Product-wise Sales -->
<div class="card">
    <h2>📦 Product-wise Sales</h2>
    <canvas id="productChart"></canvas>
</div>

<!-- Monthly Sales -->
<div class="card">
    <h2>📆 Monthly Sales</h2>
    <table>
        <tr><th>Month</th><th>Total Sales (XAF)</th></tr>
        <?php while($row = $monthly->fetch_assoc()): ?>
        <tr><td><?= $row['month'] ?></td><td><?= number_format($row['total'], 2) ?></td></tr>
        <?php endwhile; ?>
    </table>
</div>

<!-- Yearly Sales -->
<div class="card">
    <h2>📅 Yearly Sales</h2>
    <table>
        <tr><th>Year</th><th>Total Sales (XAF)</th></tr>
        <?php while($row = $yearly->fetch_assoc()): ?>
        <tr><td><?= $row['year'] ?></td><td><?= number_format($row['total'], 2) ?></td></tr>
        <?php endwhile; ?>
    </table>
</div>

<!-- Charts Scripts -->
<script>
const weeklyCtx = document.getElementById('weeklyChart').getContext('2d');
new Chart(weeklyCtx, {
    type: 'bar',
    data: {
        labels: <?= json_encode($week_labels) ?>,
        datasets: [{ label: 'Sales (XAF)', data: <?= json_encode($week_totals) ?>, backgroundColor: '#00d1ff' }]
    },
    options: {
        scales: {
            x: { ticks: { color: '#fff' } },
            y: { ticks: { color: '#fff' }, beginAtZero: true }
        }
    }
});

const productCtx = document.getElementById('productChart').getContext('2d');
new Chart(productCtx, {
    type: 'bar',
    data: {
        labels: <?= json_encode($product_labels) ?>,
        datasets: [{ label: 'Units Sold', data: <?= json_encode($product_quantities) ?>, backgroundColor: '#ffaa00' }]
    },
    options: {
        scales: {
            x: { ticks: { color: '#fff' } },
            y: { ticks: { color: '#fff' }, beginAtZero: true }
        }
    }
});
</script>
</body>
</html>
