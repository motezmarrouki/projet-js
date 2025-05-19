<?php
// Connection config for XAMPP with no password
$db_host = 'localhost';
$db_name = 'home_db';
$db_user = 'root';
$db_pass = ''; // IMPORTANT: No password

try {
    $conn = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

if (!function_exists('create_unique_id')) {
    function create_unique_id($length = 20) {
        return substr(str_shuffle(str_repeat(
            '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ',
            ceil($length / 62)
        )), 0, $length);
    }
}
