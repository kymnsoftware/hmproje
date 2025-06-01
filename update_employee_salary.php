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

$user_id = $_POST['user_id'] ?? '';
$fixed_salary = $_POST['fixed_salary'] ?? '';

if (empty($user_id) || empty($fixed_salary)) {
    echo json_encode(['success' => false, 'message' => 'Kullanıcı ID ve maaş bilgisi gereklidir!']);
    exit;
}

// Maaş doğrulaması
if (!is_numeric($fixed_salary) || $fixed_salary < 0) {
    echo json_encode(['success' => false, 'message' => 'Geçerli bir maaş tutarı giriniz!']);
    exit;
}

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Kullanıcı varlığını kontrol et
    $stmt = $conn->prepare("SELECT id FROM cards WHERE user_id = :user_id");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        echo json_encode(['success' => false, 'message' => 'Kullanıcı bulunamadı!']);
        exit;
    }
    
    // Maaşı güncelle
    $stmt = $conn->prepare("UPDATE cards SET fixed_salary = :fixed_salary WHERE user_id = :user_id");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->bindParam(':fixed_salary', $fixed_salary);
    $stmt->execute();
    
    echo json_encode(['success' => true, 'message' => 'Maaş başarıyla güncellendi.']);
    
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Veritabanı hatası: ' . $e->getMessage()]);
}
?>