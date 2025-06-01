<?php
session_start();
// Oturum kontrolü
if (!isset($_SESSION['user_id']) || $_SESSION['privilege'] < 1) {
    die('Yetkisiz erişim!');
}

// Veritabanı bağlantısı
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "db_kartlar";

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek metodu!']);
    exit;
}

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Gelen ayarları işle
    $allowedSettings = [
        'salary_minimum_work_days',
        'salary_minimum_work_rate', 
        'salary_minimum_type',
        'salary_exclude_weekends',
        'salary_exclude_holidays'
    ];
    
    foreach ($_POST as $key => $value) {
        if (in_array($key, $allowedSettings)) {
            // Checkbox değerlerini düzenle
            if ($key == 'salary_exclude_weekends' || $key == 'salary_exclude_holidays') {
                $value = isset($_POST[$key]) ? 'true' : 'false';
            }
            
            // Ayarı güncelle veya ekle
            $stmt = $conn->prepare("
                INSERT INTO settings (setting_key, setting_value)
                VALUES (:key, :value)
                ON DUPLICATE KEY UPDATE setting_value = :value
            ");
            $stmt->bindParam(':key', $key);
            $stmt->bindParam(':value', $value);
            $stmt->execute();
        }
    }
    
    echo json_encode(['success' => true, 'message' => 'Sistem ayarları başarıyla kaydedildi.']);
    
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Veritabanı hatası: ' . $e->getMessage()]);
}
?>