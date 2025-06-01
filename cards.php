<?php
// Veritabanı bağlantısı
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "db_kartlar";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Kartları sorgula
    $stmt = $conn->prepare("SELECT * FROM cards ORDER BY id DESC");
    $stmt->execute();
    $cards = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $html = "";
    foreach($cards as $card) {
        $html .= "<tr>";
        $html .= "<td>".$card['id']."</td>";
        $html .= "<td>".$card['user_id']."</td>";
        $html .= "<td>".$card['name']."</td>";
        $html .= "<td>".$card['card_number']."</td>";
        $html .= "<td>".$card['privilege']."</td>";
        $html .= "<td>".($card['enabled'] == 'true' ? '<span class="badge badge-success">Aktif</span>' : '<span class="badge badge-danger">Pasif</span>')."</td>";
        $html .= "</tr>";
    }
    
    echo $html;
} catch(PDOException $e) {
    echo "<tr><td colspan='6' class='text-danger'>Veritabanı bağlantı hatası: " . $e->getMessage() . "</td></tr>";
}
?>