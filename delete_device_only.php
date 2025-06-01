<?php
// Veritabanı bağlantısı
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "db_kartlar";
// Dönüş değerlerini JSON olarak ayarla
header('Content-Type: application/json');
if (!isset($_POST['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Kullanıcı ID eksik!']);
    exit;
}
$user_id = $_POST['user_id'];
try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Kullanıcı bilgisini al
    $stmt = $conn->prepare("SELECT card_number FROM cards WHERE user_id = :user_id");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    if ($stmt->rowCount() == 0) {
        echo json_encode(['success' => false, 'message' => 'Silinecek kullanıcı bulunamadı!']);
        exit;
    }
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    $cardNumber = $user['card_number'];
    
    // Cihazdan silme komutu gönder
    $cmdStmt = $conn->prepare("INSERT INTO commands (command_type, user_id, status) VALUES ('delete_from_device', :user_id, 'pending')");
    $cmdStmt->bindParam(':user_id', $user_id);
    $cmdStmt->execute();
    
    // Silme işlemi flag dosyasına yazılıyor (cihaz uygulaması bunu okuyacak)
    $deleteInfo = [
        'action' => 'delete_from_device',
        'user_id' => $user_id,
        'card_number' => $cardNumber,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    file_put_contents('delete_flag.txt', json_encode($deleteInfo));
    
    echo json_encode([
        'success' => true,
        'message' => 'Kullanıcı başarıyla cihazdan silindi. Veritabanında kayıtları durmaya devam edecek.'
    ]);
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Veritabanı hatası: ' . $e->getMessage()]);
}
?>