<?php
$host = 'localhost';  // or '127.0.0.1'
$user = 'root';       // username
$pass = '29636';           // password (empty if none)
$port = 3306;         // default MySQL port

try {
    $pdo = new PDO("mysql:host=$host;port=$port", $user, $pass);
    echo "✅ Successfully connected to MySQL!";
} catch (PDOException $e) {
    echo "❌ Connection failed: " . $e->getMessage();
}