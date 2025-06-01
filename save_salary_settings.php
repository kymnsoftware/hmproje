<?php
// Veritabanı bağlantısı
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "db_kartlar";

// Dönüş değerlerini JSON olarak ayarla
header('Content-Type: application/json');

if (!isset($_POST['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Kullanıcı ID eksik!']);
    exit;
}

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // POST verilerini al
    $userId = $_POST['user_id'];
    $baseSalary = isset($_POST['base_salary']) ? $_POST['base_salary'] : 0;
    $hourlyRate = isset($_POST['hourly_rate']) ? $_POST['hourly_rate'] : 0;
    $overtimeRate = isset($_POST['overtime_rate']) ? $_POST['overtime_rate'] : 1.5;
    $dailyWorkHours = isset($_POST['daily_work_hours']) ? $_POST['daily_work_hours'] : 8;
    $monthlyWorkDays = isset($_POST['monthly_work_days']) ? $_POST['monthly_work_days'] : 22;
    
    // Kullanıcı varlığını kontrol et
    $checkStmt = $conn->prepare("SELECT id FROM cards WHERE user_id = :user_id");
    $checkStmt->bindParam(':user_id', $userId);
    $checkStmt->execute();
    
    if ($checkStmt->rowCount() == 0) {
        echo json_encode(['success' => false, 'message' => 'Güncellenecek kullanıcı bulunamadı!']);
        exit;
    }
    
    // SQL güncelleme sorgusu
    $sql = "UPDATE cards SET 
            base_salary = :base_salary,
            hourly_rate = :hourly_rate,
            overtime_rate = :overtime_rate,
            daily_work_hours = :daily_work_hours,
            monthly_work_days = :monthly_work_days
            WHERE user_id = :user_id";
    
    $stmt = $conn->prepare($sql);
    
    // Parametreleri bağla
    $stmt->bindParam(':user_id', $userId);
    $stmt->bindParam(':base_salary', $baseSalary);
    $stmt->bindParam(':hourly_rate', $hourlyRate);
    $stmt->bindParam(':overtime_rate', $overtimeRate);
    $stmt->bindParam(':daily_work_hours', $dailyWorkHours);
    $stmt->bindParam(':monthly_work_days', $monthlyWorkDays);
    
    // Güncelleme işlemini yap
    $stmt->execute();
    
    echo json_encode(['success' => true, 'message' => 'Maaş ayarları başarıyla güncellendi.']);
    
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Veritabanı hatası: ' . $e->getMessage()]);
}
?>