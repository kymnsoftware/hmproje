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

$absence_id = $_POST['absence_id'] ?? '';

if (empty($absence_id)) {
    echo json_encode(['success' => false, 'message' => 'Devamsızlık ID eksik!']);
    exit;
}

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Devamsızlık kaydını sil
    $stmt = $conn->prepare("DELETE FROM absences WHERE id = :id");
    $stmt->bindParam(':id', $absence_id);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Devamsızlık kaydı başarıyla silindi.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Silinecek kayıt bulunamadı!']);
    }
    
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Veritabanı hatası: ' . $e->getMessage()]);
}
?>