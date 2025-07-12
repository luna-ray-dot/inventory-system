<?php
// db/db.php - connects your PHP app to MySQL database

$host = 'localhost';
$user = 'root';
$password = '';
$dbname = 'inventory_db';

// Create connection
$conn = new mysqli($host, $user, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
