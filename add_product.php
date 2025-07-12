<?php
include 'auth.php';
include 'sidebar.php';
include 'db/db.php';

$message = '';

// Fetch categories for dropdown
$categories = $conn->query("SELECT id, name FROM categories");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $quantity = (int) $_POST['quantity'];
    $buy_price = (float) $_POST['buy_price'];
    $low_stock_threshold = (int) $_POST['low_stock_threshold'];
    $category_id = (int) $_POST['category_id'];

    // Generate a unique barcode
    $barcode = 'PRD_' . uniqid(); // Safer unique value

    // Prepare and insert product
    $stmt = $conn->prepare("INSERT INTO products (name, quantity, buy_price, low_stock_threshold, category_id, barcode) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("siddis", $name, $quantity, $buy_price, $low_stock_threshold, $category_id, $barcode);

    if ($stmt->execute()) {
        $message = "✅ Product added successfully!<br><strong>Barcode:</strong> $barcode";
    } else {
        $message = "❌ Error adding product: " . $conn->error;
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Product</title>
    <style>
        body { font-family: Arial; background: #f8faff; padding: 20px; }
        form { max-width: 600px; margin: auto; background: white; padding: 20px; border-radius: 10px; box-shadow: 0 0 10px #cce0ff; }
        input, select, button { width: 100%; padding: 10px; margin: 10px 0; border-radius: 5px; border: 1px solid #ccc; }
        button { background-color: #007bff; color: white; border: none; cursor: pointer; }
        button:hover { background-color: #0056b3; }
        .message { text-align: center; font-weight: bold; color: green; margin-top: 10px; }
        .barcode-box { text-align: center; font-family: monospace; font-size: 18px; background: #eee; padding: 10px; margin-top: 10px; border-radius: 5px; }
    </style>
</head>
<body>

<h2 style="text-align:center;">Add Product</h2>

<form method="POST">
    <label>Product Name</label>
    <input type="text" name="name" required>

    <label>Quantity</label>
    <input type="number" name="quantity" required>

    <label>Buy Price</label>
    <input type="number" step="0.01" name="buy_price" required>

    <label>Low Stock Threshold</label>
    <input type="number" name="low_stock_threshold" required>

    <label>Category</label>
    <select name="category_id" required>
        <option value="">-- Select Category --</option>
        <?php while ($cat = $categories->fetch_assoc()): ?>
            <option value="<?= $cat['id']; ?>"><?= $cat['name']; ?></option>
        <?php endwhile; ?>
    </select>

    <button type="submit">Add Product</button>

    <?php if ($message): ?>
        <div class="message"><?= $message; ?></div>
        <?php if (strpos($message, '✅') !== false): ?>
            <div class="barcode-box"><?= $barcode; ?></div>
        <?php endif; ?>
    <?php endif; ?>
</form>

</body>
</html>
