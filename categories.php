<?php
include 'auth.php';
include 'sidebar.php';
include 'db/db.php';

// Handle Add Category
if (isset($_POST['add'])) {
    $name = trim($_POST['name']);
    if ($name !== '') {
        $stmt = $conn->prepare("INSERT INTO categories (name) VALUES (?)");
        $stmt->bind_param("s", $name);
        $stmt->execute();
        $stmt->close();
    }
}

// Handle Update Category
if (isset($_POST['update'])) {
    $id = $_POST['id'];
    $name = trim($_POST['name']);
    if ($name !== '') {
        $stmt = $conn->prepare("UPDATE categories SET name = ? WHERE id = ?");
        $stmt->bind_param("si", $name, $id);
        $stmt->execute();
        $stmt->close();
    }
}

// Handle Delete Category
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $conn->query("DELETE FROM categories WHERE id = $id");
}

// Fetch categories
$result = $conn->query("SELECT * FROM categories ORDER BY name ASC");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Categories</title>
    <style>
        body { font-family: Arial; background: #f4faff; padding: 20px; }
        table { width: 100%; border-collapse: collapse; background: white; box-shadow: 0 0 10px #ccc; }
        th, td { padding: 10px; border: 1px solid #ccc; }
        form { margin-top: 20px; }
        input[type="text"], button { padding: 8px; width: 100%; margin-top: 10px; }
        .edit-form { display: flex; gap: 10px; }
        .edit-form input[type="text"] { flex: 1; }
        .edit-form button { width: auto; }
    </style>
</head>
<body>

<h2>📁 Manage Product Categories</h2>

<table>
    <thead>
        <tr>
            <th>#</th>
            <th>Category Name</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php $i = 1; while ($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?= $i++ ?></td>
            <td>
                <form method="POST" class="edit-form">
                    <input type="hidden" name="id" value="<?= $row['id'] ?>">
                    <input type="text" name="name" value="<?= htmlspecialchars($row['name']) ?>" required>
                    <button type="submit" name="update">Update</button>
                </form>
            </td>
            <td><a href="?delete=<?= $row['id'] ?>" onclick="return confirm('Delete this category?')">🗑️ Delete</a></td>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table>

<form method="POST">
    <h3>Add New Category</h3>
    <input type="text" name="name" placeholder="Category name" required>
    <button type="submit" name="add">➕ Add Category</button>
</form>

</body>
</html>
