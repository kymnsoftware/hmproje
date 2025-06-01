<?php
// Veritabanı bağlantısı
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "db_kartlar";

// Filtreleme parametreleri
$search = isset($_GET['search']) ? $_GET['search'] : '';
$date = isset($_GET['date']) ? $_GET['date'] : '';
$type = isset($_GET['type']) ? $_GET['type'] : '';

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $params = [];
    $query = "SELECT * FROM attendance_logs WHERE 1=1 ";
    
    if (!empty($search)) {
        $query .= " AND (name LIKE :search OR card_number LIKE :search) ";
        $params[':search'] = "%$search%";
    }
    
    if (!empty($date)) {
        $query .= " AND DATE(event_time) = :date ";
        $params[':date'] = $date;
    }
    
    if (!empty($type)) {
        $query .= " AND event_type = :type ";
        $params[':type'] = $type;
    }
    
    $query .= " ORDER BY event_time DESC LIMIT 500";
    
    $stmt = $conn->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    $stmt->execute();
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $html = "";
    foreach($logs as $log) {
        $html .= "<tr>";
        $html .= "<td>".$log['id']."</td>";
        $html .= "<td>".(!empty($log['name']) ? $log['name'] : 'Bilinmeyen')."</td>";
        $html .= "<td>".$log['card_number']."</td>";
        
        // İşlem tipini renkli göster
        $eventTypeText = ($log['event_type'] == 'ENTRY') ? 'Giriş' : 'Çıkış';
        $eventTypeClass = ($log['event_type'] == 'ENTRY') ? 'success' : 'danger';
        $html .= "<td><span class='badge badge-".$eventTypeClass."'>".$eventTypeText."</span></td>";
        
        $html .= "<td>".$log['event_time']."</td>";
        $html .= "<td>".$log['device_id']."</td>";
        $html .= "</tr>";
    }
    
    echo $html;
} catch(PDOException $e) {
    echo "<tr><td colspan='6' class='text-danger'>Veritabanı sorgu hatası: " . $e->getMessage() . "</td></tr>";
}
?>