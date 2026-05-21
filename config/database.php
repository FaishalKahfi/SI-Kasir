<?php
date_default_timezone_set('Asia/Jakarta');


$host = 'db';
$dbname = 'db_majujaya';
$username = 'user_php';
$password = 'password_php';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    $pdo->exec("SET time_zone = '+07:00';");
} catch (PDOException $e) {
    die("Koneksi database gagal: " . $e->getMessage());
}
?>