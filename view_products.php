<?php
include 'auth.php';
include 'sidebar.php';
include 'db/db.php';

$message = "";

// Handle deletion
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];

    // Delete related restocks first
    $delRestocks = $conn->prepare("DELETE FROM restocks WHERE product_id = ?");
    $delRestocks->bind_param("i", $id);
    $delRestocks->execute();
    $delRestocks->close();

    // Now delete the product
    $delProduct = $conn->prepare("DELETE FROM products WHERE id = ?");
    $delProduct->bind_param("i", $id);
    $delProduct->execute();
    $delProduct->close();

    $message = "✅ Product and related restocks deleted successfully.";
}

// Fetch categories for the dropdown
$categories = $conn->query("SELECT id, name FROM categories");

// Get selected category ID from query string
$selected_category = isset($_GET['category']) ? (int) $_GET['category'] : 0;

// Build query
if ($selected_category > 0) {
    $stmt = $conn->prepare("SELECT products.*, categories.name AS category_name FROM products JOIN categories ON products.category_id = categories.id WHERE category_id = ?");
    $stmt->bind_param("i", $selected_category);
} else {
    $stmt = $conn->prepare("SELECT products.*, categories.name AS category_name FROM products LEFT JOIN categories ON products.category_id = categories.id");
}
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>View Products</title>
    <style>
        body { font-family: Arial; background: #f4faff; padding: 20px; }
        .filter { margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; background: white; box-shadow: 0 0 10px #ccc; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        th { background-color: #007bff; color: white; }
        tr:nth-child(even) { background-color: #f2f2f2; }
        .filter select { padding: 8px; font-size: 16px; }
        .delete-btn {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 5px;
            cursor: pointer;
        }
        .delete-btn:hover {
            background-color: #c82333;
        }
        .message {
            padding: 10px;
            background-color: #d4edda;
            border-left: 5px solid #28a745;
            color: #155724;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>

<h2>📦 View Products</h2>

<?php if ($message): ?>
    <div class="message"><?php echo $message; ?></div>
<?php endif; ?>

<div class="filter">
    <form method="GET">
        <label>Filter by Category:</label>
        <select name="category" onchange="this.form.submit()">
            <option value="0">-- All Categories --</option>
            <?php while ($cat = $categories->fetch_assoc()): ?>
                <option value="<?php echo $cat['id']; ?>" <?php echo $selected_category == $cat['id'] ? 'selected' : ''; ?>>
                    <?php echo $cat['name']; ?>
                </option>
            <?php endwhile; ?>
        </select>
    </form>
</div>

<table>
    <thead>
        <tr>
            <th>Name</th>
            <th>Quantity</th>
            <th>Buy Price</th>
            <th>Low Stock Threshold</th>
            <th>Category</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?php echo htmlspecialchars($row['name']); ?></td>
                <td><?php echo $row['quantity']; ?></td>
                <td>XAF<?php echo number_format($row['buy_price'], 2); ?></td>
                <td><?php echo $row['low_stock_threshold']; ?></td>
                <td><?php echo $row['category_name'] ?? 'Uncategorized'; ?></td>
                <td>
                    <a href="view_products.php?delete=<?php echo $row['id']; ?>" onclick="return confirm('Are you sure you want to delete this product?');">
                        <button class="delete-btn">Delete</button>
                    </a>
                </td>
            </tr>
        <?php endwhile; ?>
    </tbody>
</table>

</body>
</html>
