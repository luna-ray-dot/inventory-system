<?php
// sidebar.php
?>
<style>
    body {
        margin: 0;
        font-family: Arial, sans-serif;
        display: flex;
        transition: background 0.3s, color 0.3s;
    }

    .sidebar {
        width: 220px;
        background-color: var(--sidebar-color, #007bff);
        height: 100vh;
        padding-top: 20px;
        color: white;
        position: fixed;
        transition: background 0.3s;
    }

    .sidebar h2 {
        text-align: center;
        margin-bottom: 20px;
        font-size: 22px;
    }

    .sidebar a {
        display: block;
        padding: 12px 20px;
        color: white;
        text-decoration: none;
    }

    .sidebar a:hover {
        background-color: rgba(255, 255, 255, 0.1);
    }

    .main-content {
        margin-left: 220px;
        padding: 20px;
        width: 100%;
        background-color: var(--main-bg, #f4faff);
        color: var(--text-color, #000);
        min-height: 100vh;
        transition: background 0.3s, color 0.3s;
    }

    .theme-controls {
        padding: 10px 20px;
    }

    .theme-controls button,
    .theme-controls input[type="color"] {
        margin: 5px 0;
        padding: 6px 10px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
    }

    .theme-controls button {
        background: #fff;
        color: #000;
    }

    body.dark-mode {
        --main-bg: #121212;
        --text-color: #ffffff;
    }
</style>

<div class="sidebar" id="sidebar">
    <h2>My Inventory</h2>
    <a href="dashboard.php">📊 Dashboard</a>
    <li><a href="categories.php">📂 Manage Categories</a></li>
    <a href="add_product.php">➕ Add Product</a>
    <a href="view_products.php">😍 View Products</a>
    <a href="sell_product.php">💰 Record Sale</a>
    <a href="weekly_sales.php">📅 Weekly Sales</a>
    <a href="manage_stock.php">📦 Manage Stock</a>
    <a href="export_sales.php">📤 Export Reports</a>
    <a href="restock_history.php">📦 Restock History</a>
    <a href="pos.php">🖨️ POS</a>
    <a href="logout.php">🚪 Logout</a>

    <div class="theme-controls">
        <button onclick="toggleTheme()">🌙 Toggle Theme</button><br>
        <label for="sidebarColor">🎨 Sidebar Color:</label><br>
        <input type="color" id="sidebarColor" onchange="changeSidebarColor(this.value)">
    </div>
</div>

<div class="main-content">
<!-- Page content starts here -->

<script>
    // Toggle theme and save preference
    function toggleTheme() {
        document.body.classList.toggle('dark-mode');
        localStorage.setItem('theme', document.body.classList.contains('dark-mode') ? 'dark' : 'light');
    }

    // Change sidebar color and save
    function changeSidebarColor(color) {
        document.documentElement.style.setProperty('--sidebar-color', color);
        localStorage.setItem('sidebarColor', color);
    }

    // Apply saved preferences
    window.onload = function () {
        const theme = localStorage.getItem('theme');
        if (theme === 'dark') document.body.classList.add('dark-mode');

        const color = localStorage.getItem('sidebarColor');
        if (color) document.documentElement.style.setProperty('--sidebar-color', color);
    }
</script>
