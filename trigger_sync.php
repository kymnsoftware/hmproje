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
    
    // Senkronize edilmemiş tüm kartları işaretle
    $stmt = $conn->exec("UPDATE cards SET synced_to_device = 0 WHERE 1");
    
    // Senkronizasyon komutu oluştur
    $cmdStmt = $conn->prepare("INSERT INTO commands (command_type, status) VALUES ('sync_all', 'pending')");
    $cmdStmt->execute();
    
    echo json_encode(['success' => true, 'message' => 'Senkronizasyon başarıyla başlatıldı.']);
    
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Veritabanı hatası: ' . $e->getMessage()]);
}
?>