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
$absence_type_id = $_POST['absence_type_id'] ?? '';
$start_date = $_POST['start_date'] ?? '';
$end_date = $_POST['end_date'] ?? '';
$reason = $_POST['reason'] ?? '';
$admin_note = $_POST['admin_note'] ?? '';
$is_justified = isset($_POST['is_justified']) ? 1 : 0;

if (empty($absence_id) || empty($absence_type_id) || empty($start_date) || empty($end_date)) {
    echo json_encode(['success' => false, 'message' => 'Gerekli alanlar eksik!']);
    exit;
}

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Toplam gün sayısını hesapla
    $start = new DateTime($start_date);
    $end = new DateTime($end_date);
    $end->modify('+1 day');
    $interval = $start->diff($end);
    $total_days = $interval->days;
    
    // Devamsızlık kaydını güncelle
    $stmt = $conn->prepare("
        UPDATE absences 
        SET absence_type_id = :absence_type_id,
            start_date = :start_date,
            end_date = :end_date,
            total_days = :total_days,
            reason = :reason,
            admin_note = :admin_note,
            is_justified = :is_justified,
            updated_at = NOW()
        WHERE id = :id
    ");
    
    $stmt->bindParam(':absence_type_id', $absence_type_id);
    $stmt->bindParam(':start_date', $start_date);
    $stmt->bindParam(':end_date', $end_date);
    $stmt->bindParam(':total_days', $total_days);
    $stmt->bindParam(':reason', $reason);
    $stmt->bindParam(':admin_note', $admin_note);
    $stmt->bindParam(':is_justified', $is_justified);
    $stmt->bindParam(':id', $absence_id);
    
    $stmt->execute();
    
    echo json_encode(['success' => true, 'message' => 'Devamsızlık kaydı başarıyla güncellendi.']);
    
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Veritabanı hatası: ' . $e->getMessage()]);
}
?>