<?php
session_start();
// Oturum kontrolü
if (!isset($_SESSION['user_id'])) {
    die('Yetkisiz erişim!');
}

// Veritabanı bağlantısı
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "db_kartlar";

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Devamsızlık ID eksik!']);
    exit;
}

$absenceId = $_GET['id'];

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Devamsızlık detayını getir
    $stmt = $conn->prepare("
        SELECT a.*, at.name as absence_type_name, at.color, 
               c.name, c.surname, c.department, c.position, c.photo_path,
               creator.name as created_by_name
        FROM absences a
        JOIN absence_types at ON a.absence_type_id = at.id
        JOIN cards c ON a.user_id = c.user_id
        LEFT JOIN cards creator ON a.created_by = creator.user_id
        WHERE a.id = :id
    ");
    $stmt->bindParam(':id', $absenceId);
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        echo json_encode(['success' => false, 'message' => 'Devamsızlık kaydı bulunamadı!']);
        exit;
    }
    
    $absence = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Durum bilgisi
    $statusInfo = '';
    if ($absence['is_justified']) {
        $statusInfo = '<span class="badge badge-success">Mazeretli</span>';
    } else {
        $statusInfo = '<span class="badge badge-danger">Mazeretsiz</span>';
    }
    
    // HTML içeriği
    $html = '
        <div class="text-center mb-3">
            <img src="' . (!empty($absence['photo_path']) ? $absence['photo_path'] : 'uploads/default-user.png') . '" 
                 class="img-thumbnail rounded-circle" style="width: 80px; height: 80px;" alt="Profil">
            <h5 class="mt-2">' . $absence['name'] . ' ' . $absence['surname'] . '</h5>
            <p class="text-muted">' . $absence['department'] . ' - ' . $absence['position'] . '</p>
        </div>
        <table class="table table-bordered">
            <tr>
                <th width="40%">Devamsızlık Türü</th>
                <td>
                    <span class="badge" style="background-color: ' . $absence['color'] . '; color: white;">
                        ' . $absence['absence_type_name'] . '
                    </span>
                </td>
            </tr>
            <tr>
                <th>Başlangıç Tarihi</th>
                <td>' . date('d.m.Y', strtotime($absence['start_date'])) . '</td>
            </tr>
            <tr>
                <th>Bitiş Tarihi</th>
                <td>' . date('d.m.Y', strtotime($absence['end_date'])) . '</td>
            </tr>
            <tr>
                <th>Toplam Gün</th>
                <td>' . $absence['total_days'] . ' gün</td>
            </tr>
            <tr>
                <th>Durum</th>
                <td>' . $statusInfo . '</td>
            </tr>
            <tr>
                <th>Kayıt Tarihi</th>
                <td>' . date('d.m.Y H:i', strtotime($absence['created_at'])) . '</td>
            </tr>
            <tr>
                <th>Kaydeden</th>
                <td>' . ($absence['created_by_name'] ?: 'Sistem') . '</td>
            </tr>';
    
    if ($absence['auto_generated']) {
        $html .= '<tr>
                    <th>Oluşturma Türü</th>
                    <td><i class="fas fa-robot mr-1"></i> Otomatik Oluşturuldu</td>
                  </tr>';
    }
    
    $html .= '</table>';
    
    if (!empty($absence['reason'])) {
        $html .= '
            <div class="card">
                <div class="card-header">Açıklama / Sebep</div>
                <div class="card-body">
                    ' . nl2br(htmlspecialchars($absence['reason'])) . '
                </div>
            </div>';
    }
    
    if (!empty($absence['admin_note'])) {
        $html .= '
            <div class="card mt-3">
                <div class="card-header">Yönetici Notu</div>
                <div class="card-body">
                    ' . nl2br(htmlspecialchars($absence['admin_note'])) . '
                </div>
            </div>';
    }
    
    echo json_encode(['success' => true, 'html' => $html, 'data' => $absence]);
    
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Veritabanı hatası: ' . $e->getMessage()]);
}
?>