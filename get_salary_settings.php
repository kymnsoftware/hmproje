<?php
// Veritabanı bağlantısı
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "db_kartlar";

// Dönüş değerlerini JSON olarak ayarla
header('Content-Type: application/json');

if (!isset($_GET['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Kullanıcı ID eksik!']);
    exit;
}

$userId = $_GET['user_id'];

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Kullanıcının maaş ayarlarını al
    $stmt = $conn->prepare("
        SELECT user_id, name, surname, base_salary, hourly_rate, overtime_rate, daily_work_hours, monthly_work_days 
        FROM cards 
        WHERE user_id = :user_id
    ");
    $stmt->bindParam(':user_id', $userId);
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        echo json_encode(['success' => false, 'message' => 'Kullanıcı bulunamadı!']);
        exit;
    }
    
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'user_id' => $user['user_id'],
        'name' => $user['name'] . ' ' . $user['surname'],
        'base_salary' => $user['base_salary'],
        'hourly_rate' => $user['hourly_rate'],
        'overtime_rate' => $user['overtime_rate'],
        'daily_work_hours' => $user['daily_work_hours'],
        'monthly_work_days' => $user['monthly_work_days']
    ]);
    
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Veritabanı hatası: ' . $e->getMessage()]);
}
?>