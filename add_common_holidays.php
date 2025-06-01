<?php
session_start();
// Oturum kontrolü
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
    
    $currentYear = date('Y');
    $nextYear = $currentYear + 1;
    
    // Yaygın resmi tatiller
    $commonHolidays = [
        // Bu yıl
        [$currentYear . '-01-01', 'Yılbaşı'],
        [$currentYear . '-04-23', '23 Nisan Ulusal Egemenlik ve Çocuk Bayramı'],
        [$currentYear . '-05-01', 'İşçi Bayramı'],
        [$currentYear . '-05-19', '19 Mayıs Atatürk\'ü Anma, Gençlik ve Spor Bayramı'],
        [$currentYear . '-08-30', '30 Ağustos Zafer Bayramı'],
        [$currentYear . '-10-29', '29 Ekim Cumhuriyet Bayramı'],
        
        // Gelecek yıl
        [$nextYear . '-01-01', 'Yılbaşı'],
        [$nextYear . '-04-23', '23 Nisan Ulusal Egemenlik ve Çocuk Bayramı'],
        [$nextYear . '-05-01', 'İşçi Bayramı'],
        [$nextYear . '-05-19', '19 Mayıs Atatürk\'ü Anma, Gençlik ve Spor Bayramı'],
        [$nextYear . '-08-30', '30 Ağustos Zafer Bayramı'],
        [$nextYear . '-10-29', '29 Ekim Cumhuriyet Bayramı']
    ];
    
    $addedCount = 0;
    $skippedCount = 0;
    
    foreach ($commonHolidays as $holiday) {
        list($date, $name) = $holiday;
        
        // Zaten var mı kontrol et
        $stmt = $conn->prepare("SELECT id FROM holidays WHERE holiday_date = :date");
        $stmt->bindParam(':date', $date);
        $stmt->execute();
        
        if ($stmt->rowCount() == 0) {
            // Yoksa ekle
            $stmt = $conn->prepare("INSERT INTO holidays (holiday_date, name) VALUES (:date, :name)");
            $stmt->bindParam(':date', $date);
            $stmt->bindParam(':name', $name);
            $stmt->execute();
            $addedCount++;
        } else {
            $skippedCount++;
        }
    }
    
    $message = "$addedCount yeni tatil eklendi.";
    if ($skippedCount > 0) {
        $message .= " $skippedCount tatil zaten mevcut olduğu için atlandı.";
    }
    
    echo json_encode(['success' => true, 'message' => $message]);
    
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Veritabanı hatası: ' . $e->getMessage()]);
}
?>