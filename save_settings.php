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
    
    // Ayarları kontrol et
    $tableExists = $conn->query("SHOW TABLES LIKE 'settings'")->rowCount() > 0;
    
    if (!$tableExists) {
        // Tablo yoksa oluştur
        $conn->exec("CREATE TABLE settings (
            id INT(11) NOT NULL AUTO_INCREMENT,
            setting_key VARCHAR(50) NOT NULL,
            setting_value TEXT,
            PRIMARY KEY (id),
            UNIQUE KEY (setting_key)
        )");
    }
    
    // Gelen tüm ayarları işle
    foreach ($_POST as $key => $value) {
        // SQL injection koruması için anahtarı kontrol et
        if (preg_match('/^[a-zA-Z0-9_]+$/', $key)) {
            // Ayarı güncelle veya ekle
            $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) 
                                   VALUES (:key, :value)
                                   ON DUPLICATE KEY UPDATE setting_value = :value");
            $stmt->bindParam(':key', $key);
            $stmt->bindParam(':value', $value);
            $stmt->execute();
        }
    }
    
    echo json_encode(['success' => true, 'message' => 'Ayarlar başarıyla kaydedildi.']);
    
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Veritabanı hatası: ' . $e->getMessage()]);
}
?>