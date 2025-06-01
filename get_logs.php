<?php
// Veritabanı bağlantısı
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "db_kartlar";

// Filtreleme parametreleri
$search = isset($_GET['search']) ? $_GET['search'] : '';
$date = isset($_GET['date']) ? $_GET['date'] : '';

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // SQL sorgusu
    $sql = "
        SELECT l.*, c.name, c.surname, c.photo_path, c.user_id
        FROM card_logs l
        LEFT JOIN cards c ON l.card_number = c.card_number
        WHERE 1=1
    ";
    $params = [];
    
    if (!empty($search)) {
        $sql .= " AND (l.card_number LIKE :search OR c.name LIKE :search OR c.user_id LIKE :search)";
        $params[':search'] = "%$search%";
    }
    
    if (!empty($date)) {
        $sql .= " AND DATE(l.scan_time) = :date";
        $params[':date'] = $date;
    }
    
    $sql .= " ORDER BY l.scan_time DESC LIMIT 500";
    
    $stmt = $conn->prepare($sql);
    
    // Parametreleri bağla
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    $stmt->execute();
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($logs) > 0) {
        foreach($logs as $log) {
            $fullName = !empty($log['name']) ? $log['name'] . ' ' . $log['surname'] : 'Bilinmeyen';
            echo "<tr>";
            echo "<td>".$log['id']."</td>";
            echo "<td><span class='badge badge-info'>".$log['card_number']."</span></td>";
            echo "<td>".date('d.m.Y H:i:s', strtotime($log['scan_time']))."</td>";
            echo "<td>".date('d.m.Y H:i:s', strtotime($log['created_at']))."</td>";
            if (!empty($log['name'])) {
                echo "<td>".$fullName." (ID: ".$log['user_id'].")</td>";
            } else {
                echo "<td><span class='text-muted'>Kullanıcı bulunamadı</span></td>";
            }
            echo "</tr>";
        }
    } else {
        echo "<tr><td colspan='5' class='text-center'>Kayıt bulunamadı</td></tr>";
    }
} catch(PDOException $e) {
    echo "<tr><td colspan='5' class='text-danger'>Veritabanı sorgu hatası: " . $e->getMessage() . "</td></tr>";
}
?>