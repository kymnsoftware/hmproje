<?php
// Veritabanı bağlantısı
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "db_kartlar";

// Dönüş değerlerini JSON olarak ayarla
header('Content-Type: application/json');

if (!isset($_POST['confirm_text']) || $_POST['confirm_text'] != 'TÜM KULLANICILARI SİL') {
    echo json_encode(['success' => false, 'message' => 'Onay metni doğru değil!']);
    exit;
}

$delete_all_type = isset($_POST['delete_all_type']) ? $_POST['delete_all_type'] : 'db_only';

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Tüm fotoğraf yollarını al
    $photoPaths = [];
    if ($stmt = $conn->query("SELECT photo_path FROM cards WHERE photo_path IS NOT NULL AND photo_path != 'uploads/default-user.png'")) {
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if (!empty($row['photo_path'])) {
                $photoPaths[] = $row['photo_path'];
            }
        }
    }
    
    // Tüm kullanıcıları sil
    $stmt = $conn->exec("DELETE FROM cards");
    
    // Cihazdan da silme komutu gönderilecekse
    if ($delete_all_type == 'both') {
        $cmdStmt = $conn->prepare("INSERT INTO commands (command_type, status) VALUES ('delete_all', 'pending')");
        $cmdStmt->execute();
    }
    
    // Fotoğrafları sil
    foreach ($photoPaths as $path) {
        if (file_exists($path)) {
            unlink($path);
        }
    }
    
    echo json_encode([
        'success' => true, 
        'message' => 'Tüm kullanıcılar başarıyla silindi.' . ($delete_all_type == 'both' ? ' Cihazdan silme işlemi için komut gönderildi.' : '')
    ]);
    
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Veritabanı hatası: ' . $e->getMessage()]);
}
?>