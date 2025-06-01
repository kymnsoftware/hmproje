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
    // SQL sorgusu
    $sql = "
        SELECT a.*, c.name, c.surname, c.photo_path
        FROM attendance_logs a
        LEFT JOIN cards c ON a.card_number = c.card_number
        WHERE 1=1
        AND (c.enabled = 'true' OR c.enabled IS NULL)
    ";
    $params = [];
    if (!empty($search)) {
        $sql .= " AND (c.name LIKE :search OR c.surname LIKE :search OR a.card_number LIKE :search)";
        $params[':search'] = "%$search%";
    }
    if (!empty($date)) {
        $sql .= " AND DATE(a.event_time) = :date";
        $params[':date'] = $date;
    }
    if (!empty($type)) {
        $sql .= " AND a.event_type = :type";
        $params[':type'] = $type;
    }
    $sql .= " ORDER BY a.event_time DESC LIMIT 500";
    $stmt = $conn->prepare($sql);
    // Parametreleri bağla
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (count($logs) > 0) {
        foreach($logs as $log) {
            $photoPath = !empty($log['photo_path']) ? $log['photo_path'] : 'uploads/default-user.png';
            $fullName = !empty($log['name']) ? $log['name'] . ' ' . $log['surname'] : 'Bilinmeyen Kullanıcı';
            echo "<tr>";
            echo "<td>".$log['id']."</td>";
            echo "<td><img src='".$photoPath."' class='user-photo-small' alt='Profil'></td>";
            echo "<td>".$fullName."</td>";
            echo "<td>".$log['card_number']."</td>";
            // İşlem tipini renkli göster
            $eventTypeText = ($log['event_type'] == 'ENTRY') ? 'Giriş' : 'Çıkış';
            $eventTypeClass = ($log['event_type'] == 'ENTRY') ? 'success' : 'danger';
            echo "<td><span class='badge badge-".$eventTypeClass."'>".$eventTypeText."</span></td>";
            echo "<td>".date('d.m.Y H:i:s', strtotime($log['event_time']))."</td>";
            echo "<td>Cihaz #".$log['device_id']."</td>";
            echo "</tr>";
        }
    } else {
        echo "<tr><td colspan='7' class='text-center'>Kayıt bulunamadı</td></tr>";
    }
} catch(PDOException $e) {
    echo "<tr><td colspan='7' class='text-danger'>Veritabanı sorgu hatası: " . $e->getMessage() . "</td></tr>";
}
?>