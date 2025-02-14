<?php

$dbHost = 'localhost';
$dbName = 'products_db';
$dbUser = 'root';        
$dbPass = 'tiganj123';            

try {
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Greška u konekciji: " . $e->getMessage());
}
