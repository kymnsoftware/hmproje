<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['privilege'] < 1) {
    die('Yetkisiz erişim!');
}

// Veritabanı bağlantısı
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "db_kartlar";

header('Content-Type: application/json');

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // İzin bakiyelerini al
    $stmt = $conn->prepare("
        SELECT lb.*, lt.name as leave_type_name, c.name, c.surname
        FROM leave_balances lb
        JOIN leave_types lt ON lb.leave_type_id = lt.id
        JOIN cards c ON lb.user_id = c.user_id
        WHERE lb.year = :year
        ORDER BY c.name, c.surname, lt.name
        LIMIT 10
    ");
    $current_year = date('Y');
    $stmt->bindParam(':year', $current_year);
    $stmt->execute();
    $balances = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $html = '';
    
    if (count($balances) > 0) {
        $html .= '<div class="table-responsive">';
        $html .= '<table class="table table-sm table-striped">';
        $html .= '<thead>';
        $html .= '<tr>';
        $html .= '<th>Personel</th>';
        $html .= '<th>İzin Türü</th>';
        $html .= '<th>Toplam</th>';
        $html .= '<th>Kullanılan</th>';
        $html .= '<th>Kalan</th>';
        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '<tbody>';
        
        foreach ($balances as $balance) {
            $html .= '<tr>';
            $html .= '<td>'.$balance['name'].' '.$balance['surname'].'</td>';
            $html .= '<td>'.$balance['leave_type_name'].'</td>';
            $html .= '<td>'.$balance['total_days'].'</td>';
            $html .= '<td>'.$balance['used_days'].'</td>';
            $html .= '<td><strong>'.$balance['remaining_days'].'</strong></td>';
            $html .= '</tr>';
        }
        
        $html .= '</tbody>';
        $html .= '</table>';
        $html .= '</div>';
        
        if (count($balances) >= 10) {
            $html .= '<div class="text-center mt-3">';
            $html .= '<a href="leave_management.php#balances" class="btn btn-outline-success btn-sm">Tümünü Görüntüle</a>';
            $html .= '</div>';
        }
    } else {
        $html .= '<div class="alert alert-info">';
        $html .= '<i class="fas fa-info-circle mr-1"></i> Tanımlanmış izin bakiyesi bulunmamaktadır.';
        $html .= '</div>';
    }
    
    echo json_encode(['success' => true, 'html' => $html]);
    
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Veritabanı hatası: ' . $e->getMessage()]);
}
?>