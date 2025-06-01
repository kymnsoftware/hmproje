<?php
// Veritabanı bağlantısı
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "db_kartlar";

// Dönüş değerlerini JSON olarak ayarla
header('Content-Type: application/json');

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // SQL sorgusu
    $stmt = $conn->prepare("SELECT user_id, name, surname FROM cards ORDER BY user_id");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($users);
    
} catch(PDOException $e) {
    echo json_encode(['error' => 'Veritabanı hatası: ' . $e->getMessage()]);
}
?>