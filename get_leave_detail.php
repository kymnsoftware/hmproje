<?php
session_start();
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
    echo json_encode(['success' => false, 'message' => 'İzin ID eksik!']);
    exit;
}

$leaveId = $_GET['id'];

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // İzin detayını getir
    $stmt = $conn->prepare("
        SELECT lr.*, lt.name as leave_type_name, lt.color, c.name, c.surname, c.department, c.position, c.photo_path
        FROM leave_requests lr
        JOIN leave_types lt ON lr.leave_type_id = lt.id
        JOIN cards c ON lr.user_id = c.user_id
        WHERE lr.id = :id
    ");
    $stmt->bindParam(':id', $leaveId);
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        echo json_encode(['success' => false, 'message' => 'İzin bulunamadı!']);
        exit;
    }
    
    $leave = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Durum bilgisi
    $statusInfo = '';
    switch ($leave['status']) {
        case 'pending':
            $statusInfo = '<span class="badge badge-warning">Beklemede</span>';
            break;
        case 'approved':
            $statusInfo = '<span class="badge badge-success">Onaylandı</span>';
            break;
        case 'rejected':
            $statusInfo = '<span class="badge badge-danger">Reddedildi</span>';
            break;
    }
    
    // HTML içeriği
    $html = '
        <div class="text-center mb-3">
            <img src="' . (!empty($leave['photo_path']) ? $leave['photo_path'] : 'uploads/default-user.png') . '" class="img-thumbnail rounded-circle" style="width: 100px; height: 100px;" alt="Profil">
            <h5 class="mt-2">' . $leave['name'] . ' ' . $leave['surname'] . '</h5>
            <p class="text-muted">' . $leave['department'] . ' - ' . $leave['position'] . '</p>
        </div>
        <table class="table table-bordered">
            <tr>
                <th width="40%">İzin Türü</th>
                <td>
                    <span class="badge" style="background-color: ' . $leave['color'] . '; color: white;">
                        ' . $leave['leave_type_name'] . '
                    </span>
                </td>
            </tr>
            <tr>
                <th>Başlangıç Tarihi</th>
                <td>' . date('d.m.Y', strtotime($leave['start_date'])) . '</td>
            </tr>
            <tr>
                <th>Bitiş Tarihi</th>
                <td>' . date('d.m.Y', strtotime($leave['end_date'])) . '</td>
            </tr>
            <tr>
                <th>Toplam Gün</th>
                <td>' . $leave['total_days'] . ' gün</td>
            </tr>
            <tr>
                <th>Durum</th>
                <td>' . $statusInfo . '</td>
            </tr>
            <tr>
                <th>Talep Tarihi</th>
                <td>' . date('d.m.Y H:i', strtotime($leave['created_at'])) . '</td>
            </tr>
        </table>
        <div class="card">
            <div class="card-header">Açıklama / Sebep</div>
            <div class="card-body">
                ' . (!empty($leave['reason']) ? nl2br($leave['reason']) : 'Belirtilmemiş') . '
            </div>
        </div>
    ';
    
    // Eğer onaylanmış veya reddedilmişse yönetici notu göster
    if ($leave['status'] != 'pending' && !empty($leave['comment'])) {
        $html .= '
            <div class="card mt-3">
                <div class="card-header">Yönetici Notu</div>
                <div class="card-body">
                    ' . nl2br($leave['comment']) . '
                </div>
            </div>
        ';
    }
    
    echo json_encode(['success' => true, 'html' => $html]);
    
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Veritabanı hatası: ' . $e->getMessage()]);
}
?>