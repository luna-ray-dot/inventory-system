<?php
// Handle Export Sales
if (isset($_POST['export_sales'])) {
    include 'db/db.php';
    $start = $_POST['start_date'];
    $end = $_POST['end_date'];
    $product_filter = $_POST['product_name'] ?? '';
    $customer_filter = $_POST['customer_name'] ?? '';

    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=sales_{$start}_to_{$end}.xls");
    header("Pragma: no-cache");
    header("Expires: 0");

    echo "ID\tProduct Name\tQuantity Sold\tSold Price\tTotal Price\tCustomer Name\tSale Date\n";

    $query = "SELECT sales.id, products.name, quantity_sold, sold_price, (quantity_sold * sold_price) AS total_price, customer_name, sale_date 
              FROM sales 
              JOIN products ON sales.product_id = products.id 
              WHERE sale_date BETWEEN ? AND ?";

    $params = [$start, $end];
    $types = "ss";

    if (!empty($product_filter)) {
        $query .= " AND products.name LIKE ?";
        $params[] = "%$product_filter%";
        $types .= "s";
    }
    if (!empty($customer_filter)) {
        $query .= " AND customer_name LIKE ?";
        $params[] = "%$customer_filter%";
        $types .= "s";
    }

    $query .= " ORDER BY sale_date DESC";

    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        echo $row['id'] . "\t" .
             $row['name'] . "\t" .
             $row['quantity_sold'] . "\t" .
             $row['sold_price'] . "\t" .
             $row['total_price'] . "\t" .
             $row['customer_name'] . "\t" .
             $row['sale_date'] . "\n";
    }
    exit;
}

// Handle Export Products
if (isset($_POST['export_products'])) {
    include 'db/db.php';
    $low_stock = isset($_POST['low_stock']);

    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=product_stock.xls");
    header("Pragma: no-cache");
    header("Expires: 0");

    echo "ID\tProduct Name\tQuantity\tCategory\n";

    $query = "SELECT products.id, products.name, products.quantity, categories.name AS category 
              FROM products 
              LEFT JOIN categories ON products.category_id = categories.id";

    if ($low_stock) {
        $query .= " WHERE quantity < 10";
    }

    $query .= " ORDER BY products.name ASC";
    $result = $conn->query($query);

    while ($row = $result->fetch_assoc()) {
        echo $row['id'] . "\t" .
             $row['name'] . "\t" .
             $row['quantity'] . "\t" .
             ($row['category'] ?? 'Uncategorized') . "\n";
    }
    exit;
}

// Non-export logic: UI
include 'auth.php';
include 'sidebar.php';
include 'db/db.php';
?>

<!DOCTYPE html>
<html>
<head>
    <title>📁 Export Reports</title>
    <style>
        body { font-family: Arial; background: #f9fbfe; padding: 20px; }
        h2 { text-align: center; }
        form { background: white; padding: 20px; margin: 20px auto; border-radius: 10px; max-width: 600px; box-shadow: 0 0 10px #ccc; }
        input, button { padding: 10px; width: 100%; margin: 10px 0; }
        button { background: #007bff; color: white; border: none; }
        button:hover { background: #0056b3; cursor: pointer; }
        label { font-weight: bold; margin-top: 10px; display: block; }
    </style>
</head>
<body>

<h2>📤 Export Reports</h2>

<!-- Export Sales Form -->
<form method="POST">
    <h3>Export Sales by Date</h3>
    <label>Start Date</label>
    <input type="date" name="start_date" required>

    <label>End Date</label>
    <input type="date" name="end_date" required>

    <label>Filter by Product Name</label>
    <input type="text" name="product_name" placeholder="e.g. Sugar">

    <label>Filter by Customer Name</label>
    <input type="text" name="customer_name" placeholder="e.g. John">

    <button type="submit" name="export_sales">📁 Export Sales</button>
</form>

<!-- Export Product Stock Form -->
<form method="POST">
    <h3>Export Product Stock</h3>
    <label><input type="checkbox" name="low_stock"> Only Low Stock (less than 10)</label>
    <button type="submit" name="export_products" style="background: #28a745;">📦 Export Product Stock</button>
</form>

</body>
</html>
