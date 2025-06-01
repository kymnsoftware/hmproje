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
    
    // Filtre parametrelerini al
    $name = isset($_GET['name']) ? $_GET['name'] : '';
    $department = isset($_GET['department']) ? $_GET['department'] : '';
    $leaveType = isset($_GET['leave_type']) ? $_GET['leave_type'] : '';
    $status = isset($_GET['status']) ? $_GET['status'] : '';
    $startDate = isset($_GET['start_date']) ? $_GET['start_date'] : '';
    $endDate = isset($_GET['end_date']) ? $_GET['end_date'] : '';
    
    // Sorgu hazırlama
    $sql = "
        SELECT lr.*, lt.name as leave_type_name, lt.color, c.name, c.surname, c.department, c.position
        FROM leave_requests lr
        JOIN leave_types lt ON lr.leave_type_id = lt.id
        JOIN cards c ON lr.user_id = c.user_id
        WHERE lr.status != 'pending'
    ";
    
    $params = [];
    
    // Filtreler
    if (!empty($name)) {
        $sql .= " AND (c.name LIKE :name OR c.surname LIKE :name)";
        $params[':name'] = "%$name%";
    }
    
    if (!empty($department)) {
        $sql .= " AND c.department = :department";
        $params[':department'] = $department;
    }
    
    if (!empty($leaveType)) {
        $sql .= " AND lr.leave_type_id = :leave_type";
        $params[':leave_type'] = $leaveType;
    }
    
    if (!empty($status)) {
        $sql .= " AND lr.status = :status";
        $params[':status'] = $status;
    }
    
    if (!empty($startDate)) {
        $sql .= " AND lr.start_date >= :start_date";
        $params[':start_date'] = $startDate;
    }
    
    if (!empty($endDate)) {
        $sql .= " AND lr.end_date <= :end_date";
        $params[':end_date'] = $endDate;
    }
    
    $sql .= " ORDER BY lr.updated_at DESC LIMIT 100";
    
    $stmt = $conn->prepare($sql);
    
    // Parametreleri bağla
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    $stmt->execute();
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $html = '';
    
    if (count($requests) > 0) {
        $html .= '<div class="table-responsive">';
        $html .= '<table class="table table-striped">';
        $html .= '<thead>';
        $html .= '<tr>';
        $html .= '<th>Personel</th>';
        $html .= '<th>İzin Türü</th>';
        $html .= '<th>Başlangıç</th>';
        $html .= '<th>Bitiş</th>';
        $html .= '<th>Süre</th>';
        $html .= '<th>Durum</th>';
        $html .= '<th>İşlem Tarihi</th>';
        $html .= '<th>Notlar</th>';
        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '<tbody>';
        
        foreach ($requests as $request) {
            $html .= '<tr>';
            $html .= '<td>'.$request['name'].' '.$request['surname'].'</td>';
            $html .= '<td><span class="badge" style="background-color: '.$request['color'].'; color: white;">'.$request['leave_type_name'].'</span></td>';
            $html .= '<td>'.date('d.m.Y', strtotime($request['start_date'])).'</td>';
            $html .= '<td>'.date('d.m.Y', strtotime($request['end_date'])).'</td>';
            $html .= '<td>'.$request['total_days'].' gün</td>';
            
            if ($request['status'] == 'approved') {
                $html .= '<td><span class="leave-status status-approved">Onaylandı</span></td>';
            } else {
                $html .= '<td><span class="leave-status status-rejected">Reddedildi</span></td>';
            }
            
            $html .= '<td>'.date('d.m.Y H:i', strtotime($request['updated_at'])).'</td>';
            $html .= '<td>'.(!empty($request['comment']) ? $request['comment'] : '-').'</td>';
            $html .= '</tr>';
        }
        
        $html .= '</tbody>';
        $html .= '</table>';
        $html .= '</div>';
    } else {
        $html .= '<div class="alert alert-info">';
        $html .= '<i class="fas fa-info-circle mr-1"></i> Filtrelere uygun izin talebi bulunamadı.';
        $html .= '</div>';
    }
    
    echo json_encode(['success' => true, 'html' => $html, 'count' => count($requests)]);
    
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Veritabanı hatası: ' . $e->getMessage()]);
}
?>