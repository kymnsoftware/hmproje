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
    
    $currentMonth = date('Y-m');
    $monthStart = $currentMonth . '-01';
    $monthEnd = date('Y-m-t', strtotime($monthStart));
    
    // Bu ayki personel sayısı
    $stmt = $conn->prepare("SELECT COUNT(*) as total_employees FROM cards WHERE enabled = 'true'");
    $stmt->execute();
    $totalEmployees = $stmt->fetchColumn();
    
    // Sabit maaş ortalaması
    $stmt = $conn->prepare("SELECT AVG(fixed_salary) as avg_salary FROM cards WHERE enabled = 'true' AND fixed_salary > 0");
    $stmt->execute();
    $avgSalary = $stmt->fetchColumn() ?: 0;
    
    // En yüksek maaş
    $stmt = $conn->prepare("SELECT MAX(fixed_salary) as max_salary FROM cards WHERE enabled = 'true'");
    $stmt->execute();
    $maxSalary = $stmt->fetchColumn() ?: 0;
    
    // En düşük maaş
    $stmt = $conn->prepare("SELECT MIN(fixed_salary) as min_salary FROM cards WHERE enabled = 'true' AND fixed_salary > 0");
    $stmt->execute();
    $minSalary = $stmt->fetchColumn() ?: 0;
    
    $html = '<div class="row">';
    $html .= '<div class="col-md-6 mb-3">';
    $html .= '<div class="text-center p-3" style="background-color: #f8f9fa; border-radius: 5px;">';
    $html .= '<h4 class="text-primary">' . $totalEmployees . '</h4>';
    $html .= '<small>Toplam Personel</small>';
    $html .= '</div>';
    $html .= '</div>';
    
    $html .= '<div class="col-md-6 mb-3">';
    $html .= '<div class="text-center p-3" style="background-color: #f8f9fa; border-radius: 5px;">';
    $html .= '<h4 class="text-success">' . number_format($avgSalary, 0, ',', '.') . ' ₺</h4>';
    $html .= '<small>Ortalama Maaş</small>';
    $html .= '</div>';
    $html .= '</div>';
    
    $html .= '<div class="col-md-6 mb-3">';
    $html .= '<div class="text-center p-3" style="background-color: #f8f9fa; border-radius: 5px;">';
    $html .= '<h4 class="text-info">' . number_format($maxSalary, 0, ',', '.') . ' ₺</h4>';
    $html .= '<small>En Yüksek Maaş</small>';
    $html .= '</div>';
    $html .= '</div>';
    
    $html .= '<div class="col-md-6 mb-3">';
    $html .= '<div class="text-center p-3" style="background-color: #f8f9fa; border-radius: 5px;">';
    $html .= '<h4 class="text-warning">' . number_format($minSalary, 0, ',', '.') . ' ₺</h4>';
    $html .= '<small>En Düşük Maaş</small>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';
    
    echo json_encode(['success' => true, 'html' => $html]);
    
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Veritabanı hatası: ' . $e->getMessage()]);
}
?>