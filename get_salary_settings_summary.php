<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['privilege'] < 1) {
    die('Yetkisiz erişim!');
}

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "db_kartlar";

header('Content-Type: application/json');

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Sistem ayarlarını al
    $stmt = $conn->prepare("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'salary_%'");
    $stmt->execute();
    $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    $minimumType = $settings['salary_minimum_type'] ?? 'percentage';
    $minimumValue = $minimumType === 'percentage' ? 
                   ($settings['salary_minimum_work_rate'] ?? 90) . '%' : 
                   ($settings['salary_minimum_work_days'] ?? 20) . ' gün';
    
    $excludeWeekends = ($settings['salary_exclude_weekends'] ?? 'true') === 'true' ? 'Evet' : 'Hayır';
    $excludeHolidays = ($settings['salary_exclude_holidays'] ?? 'true') === 'true' ? 'Evet' : 'Hayır';
    
    $html = '<ul class="list-group list-group-flush">';
    $html .= '<li class="list-group-item d-flex justify-content-between">';
    $html .= '<span>Minimum Çalışma:</span><span class="badge badge-primary">' . $minimumValue . '</span>';
    $html .= '</li>';
    $html .= '<li class="list-group-item d-flex justify-content-between">';
    $html .= '<span>Hafta Sonu Hariç:</span><span class="badge badge-secondary">' . $excludeWeekends . '</span>';
    $html .= '</li>';
    $html .= '<li class="list-group-item d-flex justify-content-between">';
    $html .= '<span>Tatil Hariç:</span><span class="badge badge-secondary">' . $excludeHolidays . '</span>';
    $html .= '</li>';
    $html .= '</ul>';
    $html .= '<div class="mt-3">';
    $html .= '<a href="salary_management.php" class="btn btn-sm btn-outline-primary btn-block">Ayarları Düzenle</a>';
    $html .= '</div>';
    
    echo json_encode(['success' => true, 'html' => $html]);
    
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Veritabanı hatası: ' . $e->getMessage()]);
}
?>