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
    
    // SQL sorgusu - En yüksek kullanıcı ID'sini al
    $stmt = $conn->query("SELECT MAX(CAST(user_id AS UNSIGNED)) AS max_id FROM cards");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $maxId = $result['max_id'];
    $nextId = ($maxId > 0) ? $maxId + 1 : 1;
    
    echo json_encode(['success' => true, 'next_id' => $nextId]);
    
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Veritabanı hatası: ' . $e->getMessage()]);
}
?>