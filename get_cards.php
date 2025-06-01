<?php
// Veritabanı bağlantısı
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "db_kartlar";

// Filtreleme parametreleri
$search = isset($_GET['search']) ? $_GET['search'] : '';
$department = isset($_GET['department']) ? $_GET['department'] : '';

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // SQL sorgusu
    $sql = "SELECT * FROM cards WHERE 1=1";
    $params = [];
    
    if (!empty($search)) {
        $sql .= " AND (name LIKE :search OR surname LIKE :search OR user_id LIKE :search OR card_number LIKE :search OR department LIKE :search)";
        $params[':search'] = "%$search%";
    }
    
    if (!empty($department)) {
        $sql .= " AND department = :department";
        $params[':department'] = $department;
    }
    
    $sql .= " ORDER BY id DESC";
    
    $stmt = $conn->prepare($sql);
    
    // Parametreleri bağla
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    $stmt->execute();
    $cards = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($cards) > 0) {
        foreach($cards as $card) {
            $photoPath = !empty($card['photo_path']) ? $card['photo_path'] : 'uploads/default-user.png';
            $fullName = $card['name'] . ' ' . $card['surname'];
            
            echo "<tr>";
            echo "<td>".$card['user_id']."</td>";
            echo "<td><img src='".$photoPath."' class='user-photo-small' alt='Profil'></td>";
            echo "<td>".$fullName."</td>";
            echo "<td>".(!empty($card['department']) ? $card['department'] : '-')."</td>";
            echo "<td>".$card['card_number']."</td>";
            
            // Yetki seviyesi
            $privilegeText = '';
            switch($card['privilege']) {
                case '0': $privilegeText = 'Normal Kullanıcı'; break;
                case '1': $privilegeText = 'Kayıt Yetkilisi'; break;
                case '2': $privilegeText = 'Yönetici'; break;
                case '3': $privilegeText = 'Süper Admin'; break;
                default: $privilegeText = 'Bilinmiyor';
            }
            echo "<td>".$privilegeText."</td>";
            
            // Durum
            echo "<td>".($card['enabled'] == 'true' ? '<span class="badge badge-success badge-status">Aktif</span>' : '<span class="badge badge-danger badge-status">Pasif</span>')."</td>";
            
            // İşlem butonları
            echo "<td>
                <button class='btn btn-sm btn-info action-btn view-details' data-user-id='".$card['user_id']."' title='Detay'><i class='fas fa-eye'></i></button>
                <button class='btn btn-sm btn-primary action-btn edit-user' data-user-id='".$card['user_id']."' title='Düzenle'><i class='fas fa-edit'></i></button>
                <button class='btn btn-sm btn-danger action-btn delete-user' data-user-id='".$card['user_id']."' title='Sil'><i class='fas fa-trash'></i></button>
              </td>";
            echo "</tr>";
        }
    } else {
        echo "<tr><td colspan='8' class='text-center'>Kayıt bulunamadı</td></tr>";
    }
} catch(PDOException $e) {
    echo "<tr><td colspan='8' class='text-danger'>Veritabanı hatası: " . $e->getMessage() . "</td></tr>";
}
?>