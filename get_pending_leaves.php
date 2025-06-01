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
    
    // Bekleyen izin taleplerini al
    $stmt = $conn->prepare("
        SELECT lr.*, lt.name as leave_type_name, lt.color, c.name, c.surname, c.department, c.position
        FROM leave_requests lr
        JOIN leave_types lt ON lr.leave_type_id = lt.id
        JOIN cards c ON lr.user_id = c.user_id
        WHERE lr.status = 'pending'
        ORDER BY lr.created_at DESC
        LIMIT 5
    ");
    $stmt->execute();
    $pending_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $html = '';
    
    if (count($pending_requests) > 0) {
        foreach ($pending_requests as $request) {
            $html .= '<div class="leave-item mb-3 p-3" style="border-left: 4px solid '.$request['color'].'; background-color: #f8f9fa; border-radius: 4px;">';
            $html .= '<div class="d-flex justify-content-between align-items-center mb-2">';
            $html .= '<h6 class="mb-0">'.$request['name'].' '.$request['surname'].'</h6>';
            $html .= '<span class="badge" style="background-color: '.$request['color'].'; color: white;">'.$request['leave_type_name'].'</span>';
            $html .= '</div>';
            $html .= '<div class="text-muted small">'.$request['department'].' - '.$request['position'].'</div>';
            $html .= '<div class="mt-2">';
            $html .= '<strong>Tarih:</strong> '.date('d.m.Y', strtotime($request['start_date'])).' - '.date('d.m.Y', strtotime($request['end_date'])).' ('.$request['total_days'].' gün)';
            $html .= '</div>';
            $html .= '<div class="mt-2 d-flex justify-content-end">';
            $html .= '<a href="leave_management.php#pending" class="btn btn-sm btn-primary">İşlem Yap</a>';
            $html .= '</div>';
            $html .= '</div>';
        }
        
        if (count($pending_requests) >= 5) {
            $html .= '<div class="text-center mt-3">';
            $html .= '<a href="leave_management.php#pending" class="btn btn-outline-primary btn-sm">Tümünü Görüntüle</a>';
            $html .= '</div>';
        }
    } else {
        $html .= '<div class="alert alert-info">';
        $html .= '<i class="fas fa-info-circle mr-1"></i> Bekleyen izin talebi bulunmamaktadır.';
        $html .= '</div>';
    }
    
    echo json_encode(['success' => true, 'html' => $html, 'count' => count($pending_requests)]);
    
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Veritabanı hatası: ' . $e->getMessage()]);
}
?>