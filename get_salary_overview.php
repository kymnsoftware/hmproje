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

$month = $_GET['month'] ?? date('Y-m');
$department = $_GET['department'] ?? '';

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Kullanıcıları al
    $sql = "SELECT user_id, name, surname, department, fixed_salary FROM cards WHERE enabled = 'true'";
    $params = [];
    
    if (!empty($department)) {
        $sql .= " AND department = :department";
        $params[':department'] = $department;
    }
    
    $sql .= " ORDER BY name, surname LIMIT 10";
    
    $stmt = $conn->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($users) > 0) {
        $html = '<div class="table-responsive">';
        $html .= '<table class="table table-sm table-striped">';
        $html .= '<thead>';
        $html .= '<tr>';
        $html .= '<th>Personel</th>';
        $html .= '<th>Departman</th>';
        $html .= '<th>Sabit Maaş</th>';
        $html .= '<th>İşlemler</th>';
        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '<tbody>';
        
        foreach ($users as $user) {
            $html .= '<tr>';
            $html .= '<td>' . $user['name'] . ' ' . $user['surname'] . '</td>';
            $html .= '<td>' . ($user['department'] ?: '-') . '</td>';
            $html .= '<td><span class="badge badge-success">' . number_format($user['fixed_salary'] ?: 35000, 0, ',', '.') . ' ₺</span></td>';
            $html .= '<td>';
            $html .= '<a href="salary_management.php?user_id=' . $user['user_id'] . '" class="btn btn-sm btn-outline-primary" target="_blank">';
            $html .= '<i class="fas fa-calculator"></i> Hesapla';
            $html .= '</a>';
            $html .= '</td>';
            $html .= '</tr>';
        }
        
        $html .= '</tbody>';
        $html .= '</table>';
        $html .= '</div>';
        
        if (count($users) == 10) {
            $html .= '<div class="text-center mt-2">';
            $html .= '<small class="text-muted">İlk 10 personel gösteriliyor. Tüm liste için ';
            $html .= '<a href="salary_management.php" target="_blank">maaş yönetim sayfasını</a> ziyaret edin.</small>';
            $html .= '</div>';
        }
    } else {
        $html = '<div class="alert alert-info">Bu kriterlere uygun personel bulunamadı.</div>';
    }
    
    echo json_encode(['success' => true, 'html' => $html]);
    
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Veritabanı hatası: ' . $e->getMessage()]);
}
?>