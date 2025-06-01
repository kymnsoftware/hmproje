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

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // POST verilerini al
    $user_id = $_POST['user_id'];
    $card_number = $_POST['card_number'];
    $name = $_POST['name'];
    $surname = $_POST['surname'];
    $department = $_POST['department'];
    $position = $_POST['position'];
    $phone = $_POST['phone'];
    $email = $_POST['email'];
    $hire_date = $_POST['hire_date'];
    $birth_date = $_POST['birth_date'];
    $address = $_POST['address'];
    $privilege = $_POST['privilege'];
    $enabled = isset($_POST['enabled']) ? 'true' : 'false';
    $fixed_salary = isset($_POST['fixed_salary']) ? $_POST['fixed_salary'] : 35000;
    
    // Şifre kontrolü - sadece dolu ise güncelle
    $passwordUpdate = '';
    if (!empty($_POST['password'])) {
        $password = $_POST['password'];
        $passwordUpdate = ", password = :password";
    }
    
    // Mevcut kullanıcıyı kontrol et
    $checkStmt = $conn->prepare("SELECT photo_path FROM cards WHERE user_id = :user_id");
    $checkStmt->bindParam(':user_id', $user_id);
    $checkStmt->execute();
    
    if ($checkStmt->rowCount() == 0) {
        echo json_encode(['success' => false, 'message' => 'Güncellenecek kullanıcı bulunamadı!']);
        exit;
    }
    
    // Mevcut fotoğraf yolunu al
    $currentData = $checkStmt->fetch(PDO::FETCH_ASSOC);
    $currentPhotoPath = $currentData['photo_path'];
    
    // Resim yüklemesi var mı kontrol et
    $photoPath = $currentPhotoPath; // Varsayılan olarak mevcut fotoğrafı koru
    
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
        // Uploads klasörü kontrolü
        $uploadDir = 'uploads/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        // Dosya uzantısını kontrol et
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['photo']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (!in_array($ext, $allowed)) {
            echo json_encode(['success' => false, 'message' => 'Sadece JPG, PNG ve GIF formatları kabul edilir!']);
            exit;
        }
        
        // Dosya boyutunu kontrol et (2MB maksimum)
        if ($_FILES['photo']['size'] > 2097152) {
            echo json_encode(['success' => false, 'message' => 'Dosya boyutu maksimum 2MB olmalıdır!']);
            exit;
        }
        
        // Benzersiz dosya adı oluştur
        $newFilename = 'user_' . $user_id . '_' . time() . '.' . $ext;
        $uploadPath = $uploadDir . $newFilename;
        
        // Dosyayı yükle
        if (move_uploaded_file($_FILES['photo']['tmp_name'], $uploadPath)) {
            // Yükleme başarılı, eski fotoğrafı sil (default dışındaysa)
            if (!empty($currentPhotoPath) && $currentPhotoPath != 'uploads/default-user.png' && file_exists($currentPhotoPath)) {
                unlink($currentPhotoPath);
            }
            $photoPath = $uploadPath;
        } else {
            echo json_encode(['success' => false, 'message' => 'Fotoğraf yüklenirken bir hata oluştu!']);
            exit;
        }
    }
    
    // SQL güncelleme sorgusu
 $sql = "UPDATE cards SET
        card_number = :card_number,
        name = :name,
        surname = :surname,
        department = :department,
        position = :position,
        phone = :phone,
        email = :email,
        hire_date = :hire_date,
        birth_date = :birth_date,
        address = :address,
        privilege = :privilege,
        enabled = :enabled,
        photo_path = :photo_path,
        fixed_salary = :fixed_salary,
        synced_to_device = 0
        $passwordUpdate
        WHERE user_id = :user_id";
    
    $stmt = $conn->prepare($sql);
    
    // Parametreleri bağla
    $stmt->bindParam(':user_id', $user_id);
    $stmt->bindParam(':card_number', $card_number);
    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':surname', $surname);
    $stmt->bindParam(':department', $department);
    $stmt->bindParam(':position', $position);
    $stmt->bindParam(':phone', $phone);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':hire_date', $hire_date);
    $stmt->bindParam(':birth_date', $birth_date);
    $stmt->bindParam(':address', $address);
    $stmt->bindParam(':privilege', $privilege);
    $stmt->bindParam(':enabled', $enabled);
    $stmt->bindParam(':photo_path', $photoPath);
    // Maaş bilgilerini bağla
$stmt->bindParam(':base_salary', $_POST['base_salary']);
$stmt->bindParam(':hourly_rate', $_POST['hourly_rate']);
$stmt->bindParam(':overtime_rate', $_POST['overtime_rate']);
$stmt->bindParam(':daily_work_hours', $_POST['daily_work_hours']);
$stmt->bindParam(':monthly_work_days', $_POST['monthly_work_days']);
    $stmt->bindParam(':fixed_salary', $fixed_salary);
    // Şifre dolu ise ekle
    if (!empty($_POST['password'])) {
        $stmt->bindParam(':password', $password);
    }
    
    // Güncelleme işlemini yap
    $stmt->execute();
    
    // Senkronizasyon komutu ekle
    $cmdStmt = $conn->prepare("INSERT INTO commands (command_type, user_id, status) VALUES ('sync_user', :user_id, 'pending')");
    $cmdStmt->bindParam(':user_id', $user_id);
    $cmdStmt->execute();
    
    echo json_encode(['success' => true, 'message' => 'Kullanıcı bilgileri başarıyla güncellendi.']);
    
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Veritabanı hatası: ' . $e->getMessage()]);
}
?>