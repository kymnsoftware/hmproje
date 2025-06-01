<?php
// Veritabanı bağlantısı
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "db_kartlar";
if (!isset($_GET['user_id'])) {
    echo "Kullanıcı ID eksik!";
    exit;
}
$user_id = $_GET['user_id'];
try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Kullanıcı bilgilerini al
    $stmt = $conn->prepare("SELECT * FROM cards WHERE user_id = :user_id");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    if ($stmt->rowCount() == 0) {
        echo "<div class='alert alert-warning'>Kullanıcı bulunamadı!</div>";
        exit;
    }
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    // Fotoğraf kontrolü
    $photoPath = !empty($user['photo_path']) ? $user['photo_path'] : 'uploads/default-user.png';
    // Son 10 giriş-çıkış kaydını al
    $attendanceStmt = $conn->prepare("
        SELECT a.*, c.name, c.surname
        FROM attendance_logs a
        LEFT JOIN cards c ON a.card_number = c.card_number
        WHERE a.card_number = :card_number
        ORDER BY a.event_time DESC
        LIMIT 10
    ");
    $attendanceStmt->bindParam(':card_number', $user['card_number']);
    $attendanceStmt->execute();
    $attendance = $attendanceStmt->fetchAll(PDO::FETCH_ASSOC);
    // Yetki seviyesi metni
    $privilegeText = '';
    switch($user['privilege']) {
        case '0': $privilegeText = 'Normal Kullanıcı'; break;
        case '1': $privilegeText = 'Kayıt Yetkilisi'; break;
        case '2': $privilegeText = 'Yönetici'; break;
        case '3': $privilegeText = 'Süper Admin'; break;
        default: $privilegeText = 'Bilinmiyor';
    }
    // HTML çıktısı
    echo '<div class="row" style="max-height: 70vh; overflow-y: auto;">';
    // Sol kolon - Fotoğraf ve temel bilgiler
    echo '<div class="col-md-4 text-center">';
    echo '<img src="'.$photoPath.'" class="user-photo mb-3" alt="Profil Fotoğrafı">';
    echo '<h4>'.$user['name'].' '.$user['surname'].'</h4>';
    echo '<p class="text-muted">'.$user['department'].' - '.$user['position'].'</p>';
    // Durum bilgisi
    if ($user['enabled'] == 'true') {
        echo '<span class="badge badge-success p-2 mb-3">Aktif</span>';
    } else {
        echo '<span class="badge badge-danger p-2 mb-3">Pasif</span>';
    }
    echo '</div>';
    // Sağ kolon - Detaylı bilgiler
    echo '<div class="col-md-8">';
    // Sekme başlıkları
    echo '<ul class="nav nav-tabs" id="userDetailTabs" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" id="personal-tab" data-toggle="tab" href="#personal" role="tab">Kişisel Bilgiler</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="card-tab" data-toggle="tab" href="#card" role="tab">Kart Bilgileri</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="attendance-tab" data-toggle="tab" href="#attendance" role="tab">Giriş-Çıkış Kayıtları</a>
            </li>
        </ul>';
    // Sekme içerikleri
    echo '<div class="tab-content" id="userDetailTabsContent">';
    // Kişisel Bilgiler sekmesi
    echo '<div class="tab-pane fade show active" id="personal" role="tabpanel">';
    echo '<div class="mt-3">';
    echo '<table class="table table-hover">';
    echo '<tr><th width="30%">Telefon:</th><td>'.(!empty($user['phone']) ? $user['phone'] : '-').'</td></tr>';
    echo '<tr><th>E-posta:</th><td>'.(!empty($user['email']) ? $user['email'] : '-').'</td></tr>';
    echo '<tr><th>İşe Giriş Tarihi:</th><td>'.(!empty($user['hire_date']) && $user['hire_date'] != '0000-00-00' ? date('d.m.Y', strtotime($user['hire_date'])) : '-').'</td></tr>';
    echo '<tr><th>Doğum Tarihi:</th><td>'.(!empty($user['birth_date']) && $user['birth_date'] != '0000-00-00' ? date('d.m.Y', strtotime($user['birth_date'])) : '-').'</td></tr>';
    echo '<tr><th>Adres:</th><td>'.(!empty($user['address']) ? $user['address'] : '-').'</td></tr>';
    echo '</table>';
    echo '</div>';
    echo '</div>';
    // Kart Bilgileri sekmesi
    echo '<div class="tab-pane fade" id="card" role="tabpanel">';
    echo '<div class="mt-3">';
    echo '<table class="table table-hover">';
    echo '<tr><th width="30%">Kullanıcı ID:</th><td>'.$user['user_id'].'</td></tr>';
    echo '<tr><th>Kart Numarası:</th><td><span class="badge badge-info p-2">'.$user['card_number'].'</span></td></tr>';
    echo '<tr><th>Yetki Seviyesi:</th><td>'.$privilegeText.'</td></tr>';
    echo '<tr><th>Kayıt Tarihi:</th><td>'.date('d.m.Y H:i:s', strtotime($user['created_at'])).'</td></tr>';
    echo '<tr><th>Cihaz Senkronizasyonu:</th><td>'.($user['synced_to_device'] == 1 ? '<span class="badge badge-success">Senkronize</span>' : '<span class="badge badge-warning">Senkronize Değil</span>').'</td></tr>';
    echo '</table>';
    echo '</div>';
    echo '</div>';
    // Giriş-Çıkış Kayıtları sekmesi
    echo '<div class="tab-pane fade" id="attendance" role="tabpanel">';
    echo '<div class="mt-3">';
    if (count($attendance) > 0) {
        echo '<table class="table table-striped table-sm">';
        echo '<thead><tr><th>Tarih/Saat</th><th>İşlem</th><th>Cihaz</th></tr></thead>';
        echo '<tbody>';
        foreach ($attendance as $record) {
            $eventTypeText = ($record['event_type'] == 'ENTRY') ? 'Giriş' : 'Çıkış';
            $eventTypeClass = ($record['event_type'] == 'ENTRY') ? 'success' : 'danger';
            echo '<tr>';
            echo '<td>'.date('d.m.Y H:i:s', strtotime($record['event_time'])).'</td>';
            echo '<td><span class="badge badge-'.$eventTypeClass.'">'.$eventTypeText.'</span></td>';
            echo '<td>Cihaz #'.$record['device_id'].'</td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
    } else {
        echo '<div class="alert alert-info">Bu kullanıcıya ait giriş-çıkış kaydı bulunamadı.</div>';
    }
    echo '</div>';
    echo '</div>';
    echo '</div>'; // tab-content sonu
    echo '</div>'; // col-md-8 sonu
    echo '</div>'; // row sonu
} catch(PDOException $e) {
    echo "<div class='alert alert-danger'>Veritabanı hatası: " . $e->getMessage() . "</div>";
}
?>