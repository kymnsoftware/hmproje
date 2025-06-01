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
    
    // Son okutulan kartı al (son 5 saniye içinde)
    $stmt = $conn->prepare("
        SELECT card_number 
        FROM card_logs 
        WHERE scan_time >= DATE_SUB(NOW(), INTERVAL 5 SECOND)
        ORDER BY scan_time DESC 
        LIMIT 1
    ");
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $card = $stmt->fetch(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'card_number' => $card['card_number']]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Kart bulunamadı']);
    }
    
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Veritabanı hatası: ' . $e->getMessage()]);
}
?>