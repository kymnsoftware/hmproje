<?php
// Veritabanı bağlantısı
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "db_kartlar";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Kart loglarını sorgula
    $stmt = $conn->prepare("
        SELECT l.*, c.name, c.user_id 
        FROM card_logs l
        LEFT JOIN cards c ON l.card_number = c.card_number
        ORDER BY l.scan_time DESC
        LIMIT 500
    ");
    $stmt->execute();
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $html = "";
    foreach($logs as $log) {
        $html .= "<tr>";
        $html .= "<td>".$log['id']."</td>";
        $html .= "<td>".$log['card_number']."</td>";
        $html .= "<td>".$log['scan_time']."</td>";
        $html .= "<td>".$log['created_at']."</td>";
        $html .= "<td>".(!empty($log['name']) ? $log['name']." (ID: ".$log['user_id'].")" : '<span class="text-muted">Kullanıcı bulunamadı</span>')."</td>";
        $html .= "</tr>";
    }
    
    echo $html;
} catch(PDOException $e) {
    echo "<tr><td colspan='5' class='text-danger'>Veritabanı sorgu hatası: " . $e->getMessage() . "</td></tr>";
}
?>