<?php
include 'auth.php';
include 'sidebar.php';
include 'db/db.php';

// sell_product.php - Sell a product and update stock
$message = '';

// Fetch products for dropdown
$products = $conn->query("SELECT id, name, quantity FROM products");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = (int) $_POST['product_id'];
    $quantity_sold = (int) $_POST['quantity_sold'];
    $sold_price = (float) $_POST['sold_price'];
    $customer_name = $_POST['customer_name'];

    // Check available quantity
    $check = $conn->query("SELECT quantity FROM products WHERE id = $product_id");

    if ($check && $data = $check->fetch_assoc()) {
        if ($data['quantity'] >= $quantity_sold) {
            // Insert sale
            $stmt = $conn->prepare("INSERT INTO sales (product_id, quantity_sold, sold_price, customer_name) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iids", $product_id, $quantity_sold, $sold_price, $customer_name);
            $stmt->execute();
            $stmt->close();

            // Update product quantity
            $conn->query("UPDATE products SET quantity = quantity - $quantity_sold WHERE id = $product_id");

            $message = "✅ Sale recorded and stock updated.";
        } else {
            $message = "❌ Not enough stock available.";
        }
    } else {
        $message = "❌ Product not found.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Sell Product</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        body { font-family: Arial; background: #f4faff; padding: 20px; }
        form { max-width: 600px; margin: auto; background: white; padding: 20px; border-radius: 10px; box-shadow: 0 0 10px #cce0ff; }
        input, select, button { width: 100%; padding: 10px; margin: 10px 0; border-radius: 5px; border: 1px solid #ccc; }
        button { background-color: #007bff; color: white; border: none; cursor: pointer; }
        button:hover { background-color: #0056b3; }
        .message { text-align: center; font-weight: bold; margin-top: 10px; }
    </style>
</head>
<body>
    <h2 style="text-align:center;">Sell Product</h2>
    <form method="POST">
        <label>Select Product</label>
        <select name="product_id" required>
            <option value="">-- Choose --</option>
            <?php while ($p = $products->fetch_assoc()): ?>
                <option value="<?php echo $p['id']; ?>">
                    <?php echo $p['name'] . " (Stock: " . $p['quantity'] . ")"; ?>
                </option>
            <?php endwhile; ?>
        </select>

        <label>Quantity Sold</label>
        <input type="number" name="quantity_sold" min="1" required>

        <label>Selling Price (per unit)</label>
        <input type="number" step="0.01" name="sold_price" required>

        <label>Customer Name</label>
        <input type="text" name="customer_name">

        <button type="submit">Record Sale</button>
        <div class="message"><?php echo $message; ?></div>
    </form>
</body>
</html>

</div> <!-- Close main-content -->
