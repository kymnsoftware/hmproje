<?php
session_start();
if (!isset($_SESSION['user_id'])) {
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
    
    // Parametreleri al
    $start = isset($_GET['start']) ? $_GET['start'] : '';
    $end = isset($_GET['end']) ? $_GET['end'] : '';
    $department = isset($_GET['department']) ? $_GET['department'] : '';
    $leaveTypes = isset($_GET['leave_types']) ? explode(',', $_GET['leave_types']) : [];
    $status = isset($_GET['status']) ? $_GET['status'] : 'approved'; // Varsayılan olarak onaylı izinler
    
    // SQL sorgusu
    $sql = "
        SELECT lr.id, lr.user_id, lr.start_date, lr.end_date, lr.status, 
               c.name, c.surname, c.department, 
               lt.name as leave_type_name, lt.color
        FROM leave_requests lr
        JOIN cards c ON lr.user_id = c.user_id
        JOIN leave_types lt ON lr.leave_type_id = lt.id
        WHERE 1=1
    ";
    
    $params = [];
    
    // Tarih aralığı filtresi
    if (!empty($start) && !empty($end)) {
        $sql .= " AND (
            (lr.start_date BETWEEN :start AND :end)
            OR (lr.end_date BETWEEN :start AND :end)
            OR (lr.start_date <= :start AND lr.end_date >= :end)
        )";
        $params[':start'] = $start;
        $params[':end'] = $end;
    }
    
    // Departman filtresi
    if (!empty($department)) {
        $sql .= " AND c.department = :department";
        $params[':department'] = $department;
    }
    
    // İzin türü filtresi
    if (!empty($leaveTypes)) {
        $placeholders = implode(',', array_map(function($i) { return ':type_'.$i; }, array_keys($leaveTypes)));
        $sql .= " AND lr.leave_type_id IN ($placeholders)";
        
        foreach ($leaveTypes as $index => $type) {
            $params[':type_'.$index] = $type;
        }
    }
    
    // Durum filtresi
    if (!empty($status)) {
        $sql .= " AND lr.status = :status";
        $params[':status'] = $status;
    }
    
    $stmt = $conn->prepare($sql);
    
    // Parametreleri bağla
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    $stmt->execute();
    $leaves = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // FullCalendar için events dizisi
    $events = [];
    
    foreach ($leaves as $leave) {
        // Başlangıç ve bitiş tarihleri
        $startDate = new DateTime($leave['start_date']);
        $endDate = new DateTime($leave['end_date']);
        $endDate->modify('+1 day'); // FullCalendar için bitiş tarihine 1 gün ekle (exclusive)
        
        // Durum sınıfı
        $statusClass = '';
        switch ($leave['status']) {
            case 'pending':
                $statusClass = 'pending-leave';
                break;
            case 'approved':
                $statusClass = 'approved-leave';
                break;
            case 'rejected':
                $statusClass = 'rejected-leave';
                break;
        }
        
        // Event nesnesini oluştur
        $events[] = [
            'id' => $leave['id'],
            'title' => $leave['name'] . ' ' . $leave['surname'] . ' - ' . $leave['leave_type_name'],
            'start' => $startDate->format('Y-m-d'),
            'end' => $endDate->format('Y-m-d'),
            'backgroundColor' => $leave['color'],
            'borderColor' => $leave['color'],
            'classNames' => $statusClass,
            'extendedProps' => [
                'userId' => $leave['user_id'],
                'department' => $leave['department'],
                'leaveType' => $leave['leave_type_name'],
                'status' => $leave['status']
            ]
        ];
    }
    
    echo json_encode(['success' => true, 'events' => $events]);
    
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Veritabanı hatası: ' . $e->getMessage()]);
}
?>