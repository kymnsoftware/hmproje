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
$delete_type = isset($_POST['delete_type']) ? $_POST['delete_type'] : 'db_only';
try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Kullanıcı ve fotoğraf bilgilerini al
    $stmt = $conn->prepare("SELECT photo_path, card_number FROM cards WHERE user_id = :user_id");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    if ($stmt->rowCount() == 0) {
        echo json_encode(['success' => false, 'message' => 'Silinecek kullanıcı bulunamadı!']);
        exit;
    }
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    $photoPath = $user['photo_path'];
    $cardNumber = $user['card_number'];
    
    // Kullanıcıyı veritabanından sil
    $stmt = $conn->prepare("DELETE FROM cards WHERE user_id = :user_id");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    
    // Cihazdan da silme komutu gönderilecekse
    if ($delete_type == 'both') {
        $cmdStmt = $conn->prepare("INSERT INTO commands (command_type, user_id, status) VALUES ('delete_user', :user_id, 'pending')");
        $cmdStmt->bindParam(':user_id', $user_id);
        $cmdStmt->execute();
        
        // Silme işlemi flag dosyasına yazılıyor (cihaz uygulaması bunu okuyacak)
        $deleteInfo = [
            'action' => 'delete_single',
            'user_id' => $user_id,
            'card_number' => $cardNumber,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        file_put_contents('delete_flag.txt', json_encode($deleteInfo));
    }
    
    // Fotoğrafı sil (default dışındaysa)
    if (!empty($photoPath) && $photoPath != 'uploads/default-user.png' && file_exists($photoPath)) {
        unlink($photoPath);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Kullanıcı başarıyla silindi.' . ($delete_type == 'both' ? ' Cihazdan silme işlemi için komut gönderildi.' : '')
    ]);
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Veritabanı hatası: ' . $e->getMessage()]);
}
?>