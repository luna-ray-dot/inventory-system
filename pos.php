<?php
include 'auth.php';
include 'sidebar.php';
include 'db/db.php';

// Handle sale submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_sale'])) {
    $customer = $_POST['customer_name'];
    $items = json_decode($_POST['cart_items'], true);
    
    foreach ($items as $item) {
        $product_id = $item['product_id'];
        $qty = (int)$item['quantity'];
        $price = (float)$item['sell_price'];

        // Insert sale record
        $stmt = $conn->prepare("INSERT INTO sales (product_id, quantity_sold, sold_price, customer_name, sale_date) VALUES (?, ?, ?, ?, NOW())");
        $stmt->bind_param("iids", $product_id, $qty, $price, $customer);
        $stmt->execute();
        $stmt->close();

        // Deduct from stock
        $conn->query("UPDATE products SET quantity = quantity - $qty WHERE id = $product_id");
    }

    echo "<script>alert('✅ Sale recorded successfully!'); window.print(); window.location='pos.php';</script>";
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>POS System</title>
    <style>
        body { font-family: Arial; background: #f0f2f5; padding: 20px; }
        .pos-box { background: white; padding: 20px; border-radius: 10px; max-width: 800px; margin: auto; box-shadow: 0 0 10px #ccc; }
        input, button { padding: 10px; width: 100%; margin-top: 10px; border-radius: 5px; border: 1px solid #ccc; }
        table { width: 100%; margin-top: 20px; border-collapse: collapse; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: center; }
        th { background: #007bff; color: white; }
        .total { font-weight: bold; font-size: 18px; text-align: right; margin-top: 10px; }

        @media print {
            body * { visibility: hidden; }
            #receipt, #receipt * { visibility: visible; }
            #receipt { position: absolute; left: 0; top: 0; width: 100%; }
        }
    </style>
</head>
<body>
<div class="pos-box">
    <h2>Point of Sale (POS)</h2>
    <input type="text" id="barcode_input" placeholder="Scan or Enter Barcode" autofocus>
    <input type="text" id="customer_name" placeholder="Customer Name (Optional)">

    <form method="POST" id="sale_form">
        <input type="hidden" name="customer_name" id="form_customer">
        <input type="hidden" name="cart_items" id="form_cart">

        <table id="cart_table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Price</th>
                    <th>Qty</th>
                    <th>Subtotal</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>

        <div class="total" id="cart_total">Total: XAF0.00</div>

        <button type="submit" name="submit_sale">Complete Sale & Print</button>
    </form>
</div>

<!-- Receipt section (for printing) -->
<div id="receipt" style="display:none; padding: 20px; font-family: monospace;">
    <h2>🧾 Sales Receipt</h2>
    <p><strong>Date:</strong> <span id="receipt-date"></span></p>
    <p><strong>Customer:</strong> <span id="receipt-customer"></span></p>
    <table style="width:100%; border-collapse: collapse; margin-top:10px;" border="1">
        <thead>
            <tr><th>Item</th><th>Qty</th><th>Price</th><th>Total</th></tr>
        </thead>
        <tbody id="receipt-items"></tbody>
    </table>
    <h3>Total: XAF<span id="receipt-total"></span></h3>
</div>

<script>
const cart = [];

// Format money
function formatMoney(n) {
    return "XAF" + n.toFixed(2);
}

// Update cart display
function updateCartDisplay() {
    const tbody = document.querySelector("#cart_table tbody");
    tbody.innerHTML = "";
    let total = 0;
    cart.forEach((item, index) => {
        const subtotal = item.quantity * item.sell_price;
        total += subtotal;
        tbody.innerHTML += `
            <tr>
                <td>${item.name}</td>
                <td>${formatMoney(item.sell_price)}</td>
                <td><input type='number' value='${item.quantity}' min='1' onchange='updateQty(${index}, this.value)'></td>
                <td>${formatMoney(subtotal)}</td>
                <td><button type='button' onclick='removeItem(${index})'>❌</button></td>
            </tr>`;
    });
    document.getElementById("cart_total").innerText = `Total: ${formatMoney(total)}`;
}

function updateQty(index, value) {
    cart[index].quantity = parseInt(value);
    updateCartDisplay();
}

function removeItem(index) {
    cart.splice(index, 1);
    updateCartDisplay();
}

// Barcode scanning
const barcodeInput = document.getElementById("barcode_input");
barcodeInput.addEventListener("keypress", function(e) {
    if (e.key === "Enter") {
        e.preventDefault();
        fetch(`get_product.php?barcode=${barcodeInput.value.trim()}`)
            .then(res => res.json())
            .then(data => {
                if (data && data.id) {
                    const existing = cart.find(i => i.product_id === data.id);
                    if (existing) {
                        existing.quantity++;
                    } else {
                        cart.push({
                            product_id: data.id,
                            name: data.name,
                            sell_price: parseFloat(data.sell_price),
                            quantity: 1
                        });
                    }
                    updateCartDisplay();
                    barcodeInput.value = "";
                } else {
                    alert("Product not found!");
                }
            });
    }
});

// Prepare receipt before submitting
const saleForm = document.getElementById("sale_form");
saleForm.addEventListener("submit", function(e) {
    if (cart.length === 0) {
        alert("Cart is empty!");
        e.preventDefault();
        return;
    }

    // Fill hidden fields
    document.getElementById("form_customer").value = document.getElementById("customer_name").value;
    document.getElementById("form_cart").value = JSON.stringify(cart);

    // Build receipt
    const receiptCustomer = document.getElementById("customer_name").value;
    document.getElementById("receipt-customer").innerText = receiptCustomer;
    document.getElementById("receipt-date").innerText = new Date().toLocaleString();

    let receiptHTML = '';
    let total = 0;
    cart.forEach(item => {
        const subtotal = item.quantity * item.sell_price;
        total += subtotal;
        receiptHTML += `<tr>
            <td>${item.name}</td>
            <td>${item.quantity}</td>
            <td>${formatMoney(item.sell_price)}</td>
            <td>${formatMoney(subtotal)}</td>
        </tr>`;
    });
    document.getElementById("receipt-items").innerHTML = receiptHTML;
    document.getElementById("receipt-total").innerText = total.toFixed(2);
    document.getElementById("receipt").style.display = "block";
});
</script>

</body>
</html>
