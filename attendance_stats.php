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
    
    // Bugün giriş yapanlar
    $today = date('Y-m-d');
    $stmt = $conn->prepare("SELECT COUNT(DISTINCT card_number) FROM attendance_logs WHERE DATE(event_time) = :today AND event_type = 'ENTRY'");
    $stmt->bindParam(':today', $today);
    $stmt->execute();
    $entries = $stmt->fetchColumn();
    
    // Bugün çıkış yapanlar
    $stmt = $conn->prepare("SELECT COUNT(DISTINCT card_number) FROM attendance_logs WHERE DATE(event_time) = :today AND event_type = 'EXIT'");
    $stmt->bindParam(':today', $today);
    $stmt->execute();
    $exits = $stmt->fetchColumn();
    
    // İçeride bulunanlar
    $stmt = $conn->prepare("
        SELECT COUNT(DISTINCT t1.card_number)
        FROM attendance_logs t1
        WHERE t1.event_type = 'ENTRY'
        AND t1.event_time = (
            SELECT MAX(t2.event_time)
            FROM attendance_logs t2
            WHERE t2.card_number = t1.card_number
        )
    ");
    $stmt->execute();
    $inside = $stmt->fetchColumn();
    
    echo json_encode([
        'entries' => $entries,
        'exits' => $exits,
        'inside' => $inside
    ]);
    
} catch(PDOException $e) {
    echo json_encode(['error' => 'Veritabanı hatası: ' . $e->getMessage()]);
}
?>