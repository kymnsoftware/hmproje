<?php
// Veritabanı bağlantısı
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "db_kartlar";

// Dönüş değerlerini JSON olarak ayarla
header('Content-Type: application/json');

if (!isset($_POST['user_id']) || !isset($_POST['card_number'])) {
    echo json_encode(['success' => false, 'message' => 'Kullanıcı ID ve Kart Numarası zorunludur!']);
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
    $password = $_POST['password'];
    $base_salary = isset($_POST['base_salary']) ? $_POST['base_salary'] : 0;
$hourly_rate = isset($_POST['hourly_rate']) ? $_POST['hourly_rate'] : 0;
$overtime_rate = isset($_POST['overtime_rate']) ? $_POST['overtime_rate'] : 1.5;
$daily_work_hours = isset($_POST['daily_work_hours']) ? $_POST['daily_work_hours'] : 8.0;
$monthly_work_days = isset($_POST['monthly_work_days']) ? $_POST['monthly_work_days'] : 22;
    $enabled = isset($_POST['enabled']) ? 'true' : 'false';
    $fixed_salary = isset($_POST['fixed_salary']) ? $_POST['fixed_salary'] : 35000;
    
    // Resim yüklemesi var mı kontrol et
    $photoPath = 'uploads/default-user.png'; // Varsayılan fotoğraf yolu
    
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
            $photoPath = $uploadPath;
        } else {
            echo json_encode(['success' => false, 'message' => 'Fotoğraf yüklenirken bir hata oluştu!']);
            exit;
        }
    }
    
    // Kullanıcı ID ve kart numarası kontrolü
    $checkStmt = $conn->prepare("SELECT id FROM cards WHERE user_id = :user_id OR card_number = :card_number");
    $checkStmt->bindParam(':user_id', $user_id);
    $checkStmt->bindParam(':card_number', $card_number);
    $checkStmt->execute();
    
    if ($checkStmt->rowCount() > 0) {
        echo json_encode(['success' => false, 'message' => 'Bu Kullanıcı ID veya Kart Numarası zaten kullanılıyor!']);
        exit;
    }
    
    // SQL sorgusu
    $sql = "INSERT INTO cards (
    user_id, card_number, name, surname, department, position,
    phone, email, hire_date, birth_date, address,
    privilege, password, enabled, photo_path, fixed_salary
) VALUES (
    :user_id, :card_number, :name, :surname, :department, :position,
    :phone, :email, :hire_date, :birth_date, :address,
    :privilege, :password, :enabled, :photo_path, :fixed_salary
)";
    
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
    $stmt->bindParam(':password', $password);
    $stmt->bindParam(':enabled', $enabled);
    $stmt->bindParam(':photo_path', $photoPath);
    $stmt->bindParam(':base_salary', $base_salary);
$stmt->bindParam(':hourly_rate', $hourly_rate);
$stmt->bindParam(':overtime_rate', $overtime_rate);
$stmt->bindParam(':daily_work_hours', $daily_work_hours);
$stmt->bindParam(':monthly_work_days', $monthly_work_days);
$stmt->bindParam(':fixed_salary', $fixed_salary);
    
    
    // Kaydı ekle
    $stmt->execute();
    
    // Son eklenen ID'yi al
    $lastId = $conn->lastInsertId();
    
    // Senkronizasyon komutu ekle
    $cmdStmt = $conn->prepare("INSERT INTO commands (command_type, user_id, status) VALUES ('sync_user', :user_id, 'pending')");
    $cmdStmt->bindParam(':user_id', $user_id);
    $cmdStmt->execute();
    
    echo json_encode(['success' => true, 'message' => 'Yeni personel başarıyla kaydedildi.', 'id' => $lastId]);
    
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Veritabanı hatası: ' . $e->getMessage()]);
}
?>