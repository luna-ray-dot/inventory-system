<?php
include 'auth.php';
include 'db/db.php';

// Handle export request BEFORE any HTML is sent
if (isset($_GET['export'])) {
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=stock_report.xls");
    header("Pragma: no-cache");
    header("Expires: 0");

    echo "Product\tQuantity\n";

    $result = $conn->query("SELECT name, quantity FROM products ORDER BY name ASC");
    while ($row = $result->fetch_assoc()) {
        echo "{$row['name']}\t{$row['quantity']}\n";
    }
    exit;
}

include 'sidebar.php';

$message = '';

// Handle stock update for a specific product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_stock'])) {
    $product_id = (int) $_POST['product_id'];
    $new_quantity = (int) $_POST['new_quantity'];

    $stmt = $conn->prepare("SELECT quantity FROM products WHERE id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $stmt->bind_result($current_quantity);
    $stmt->fetch();
    $stmt->close();

    if ($new_quantity >= 0) {
        $added_quantity = $new_quantity - $current_quantity;

        $stmt = $conn->prepare("UPDATE products SET quantity = ? WHERE id = ?");
        $stmt->bind_param("ii", $new_quantity, $product_id);
        $stmt->execute();
        $stmt->close();

        if ($added_quantity > 0) {
            $stmt = $conn->prepare("INSERT INTO restocks (product_id, quantity, restock_date) VALUES (?, ?, NOW())");
            $stmt->bind_param("ii", $product_id, $added_quantity);
            $stmt->execute();
            $stmt->close();
        }

        $message = "✅ Stock updated for product ID $product_id.";
    } else {
        $message = "❌ Quantity cannot be negative.";
    }
}

// Fetch all products
$products = $conn->query("SELECT id, name, quantity FROM products ORDER BY name ASC");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Product Stock</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4faff; padding: 20px; }
        table { width: 100%; border-collapse: collapse; background: white; box-shadow: 0 0 10px #ccc; }
        th, td { padding: 12px; border: 1px solid #ddd; text-align: left; }
        th { background: #007bff; color: white; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        input[type="number"] { width: 80px; padding: 6px; }
        button, .export-btn { padding: 6px 12px; background: #28a745; color: white; border: none; cursor: pointer; border-radius: 4px; text-decoration: none; }
        button:hover, .export-btn:hover { background: #218838; }
        .message { margin: 10px 0; color: green; font-weight: bold; }
        .top-actions { margin-bottom: 20px; }
    </style>
</head>
<body>

<h2>📝 Edit Product Stock</h2>

<div class="top-actions">
    <a href="manage_stock.php?export=1" class="export-btn">📤 Export Stock Report</a>
</div>

<?php if ($message): ?>
    <div class="message"><?php echo $message; ?></div>
<?php endif; ?>

<table>
    <tr>
        <th>Product</th>
        <th>Current Stock</th>
        <th>Update To</th>
        <th>Action</th>
    </tr>
    <?php while ($row = $products->fetch_assoc()): ?>
    <tr>
        <td><?php echo htmlspecialchars($row['name']); ?></td>
        <td><?php echo $row['quantity']; ?></td>
        <td>
            <form method="POST" style="display:flex; align-items:center;">
                <input type="hidden" name="product_id" value="<?php echo $row['id']; ?>">
                <input type="number" name="new_quantity" value="<?php echo $row['quantity']; ?>" min="0" required>
        </td>
        <td>
                <button type="submit" name="update_stock">Update</button>
            </form>
        </td>
    </tr>
    <?php endwhile; ?>
</table>

</body>
</html>
