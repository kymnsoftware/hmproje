<?php
// Veritabanı bağlantısı
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "db_kartlar";
// Dönüş değerlerini JSON olarak ayarla
header('Content-Type: application/json');
try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Toplam personel sayısı
    $stmt = $conn->query("SELECT COUNT(*) FROM cards");
    $totalPersonnel = $stmt->fetchColumn();
    // Bugün giriş yapanlar (sadece aktif kartlar)
    $today = date('Y-m-d');
    $stmt = $conn->prepare("
        SELECT COUNT(DISTINCT a.card_number) 
        FROM attendance_logs a
        JOIN cards c ON a.card_number = c.card_number
        WHERE DATE(a.event_time) = :today 
        AND a.event_type = 'ENTRY'
        AND c.enabled = 'true'
    ");
    $stmt->bindParam(':today', $today);
    $stmt->execute();
    $todayEntries = $stmt->fetchColumn();
    // Bugün çıkış yapanlar (sadece aktif kartlar)
    $stmt = $conn->prepare("
        SELECT COUNT(DISTINCT a.card_number) 
        FROM attendance_logs a
        JOIN cards c ON a.card_number = c.card_number
        WHERE DATE(a.event_time) = :today 
        AND a.event_type = 'EXIT'
        AND c.enabled = 'true'
    ");
    $stmt->bindParam(':today', $today);
    $stmt->execute();
    $todayExits = $stmt->fetchColumn();
    // İçeride bulunanlar (sadece aktif kartlar)
    $stmt = $conn->prepare("
        SELECT COUNT(DISTINCT t1.card_number)
        FROM attendance_logs t1
        JOIN cards c ON t1.card_number = c.card_number
        WHERE t1.event_type = 'ENTRY'
        AND c.enabled = 'true'
        AND t1.event_time = (
            SELECT MAX(t2.event_time)
            FROM attendance_logs t2
            WHERE t2.card_number = t1.card_number
        )
    ");
    $stmt->execute();
    $currentlyInside = $stmt->fetchColumn();
    // Son giriş-çıkış aktiviteleri (sadece aktif kartlar)
    $recentActivities = '';
    $stmt = $conn->prepare("
        SELECT a.*, c.name, c.surname, c.photo_path
        FROM attendance_logs a
        JOIN cards c ON a.card_number = c.card_number
        WHERE c.enabled = 'true'
        ORDER BY a.event_time DESC LIMIT 10
    ");
    $stmt->execute();
    $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach($activities as $activity) {
        $photoPath = !empty($activity['photo_path']) ? $activity['photo_path'] : 'uploads/default-user.png';
        $fullName = !empty($activity['name']) ? $activity['name'] . ' ' . $activity['surname'] : 'Bilinmeyen Kullanıcı';
        $recentActivities .= "<tr>";
        $recentActivities .= "<td><img src='".$photoPath."' class='user-photo-small mr-2' alt='Profil'>".$fullName."</td>";
        // İşlem tipini renkli göster
        $eventTypeText = ($activity['event_type'] == 'ENTRY') ? 'Giriş' : 'Çıkış';
        $eventTypeClass = ($activity['event_type'] == 'ENTRY') ? 'success' : 'danger';
        $recentActivities .= "<td><span class='badge badge-".$eventTypeClass."'>".$eventTypeText."</span></td>";
        $recentActivities .= "<td>".date('d.m.Y H:i', strtotime($activity['event_time']))."</td>";
        $recentActivities .= "<td>Cihaz #".$activity['device_id']."</td>";
        $recentActivities .= "</tr>";
    }
    // Son kart okutmaları
    $recentScans = '';
    $stmt = $conn->prepare("
        SELECT l.*, c.enabled
        FROM card_logs l
        LEFT JOIN cards c ON l.card_number = c.card_number
        WHERE c.enabled = 'true' OR c.enabled IS NULL
        ORDER BY l.scan_time DESC LIMIT 10
    ");
    $stmt->execute();
    $scans = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach($scans as $scan) {
        $recentScans .= "<tr>";
        $recentScans .= "<td><span class='badge badge-info'>".$scan['card_number']."</span></td>";
        $recentScans .= "<td>".date('H:i:s', strtotime($scan['scan_time']))."</td>";
        $recentScans .= "</tr>";
    }
    // JSON çıktısı
    echo json_encode([
        'total_personnel' => $totalPersonnel,
        'today_entries' => $todayEntries,
        'today_exits' => $todayExits,
        'currently_inside' => $currentlyInside,
        'recent_activities' => $recentActivities,
        'recent_scans' => $recentScans
    ]);
} catch(PDOException $e) {
    echo json_encode(['error' => 'Veritabanı hatası: ' . $e->getMessage()]);
}
?>