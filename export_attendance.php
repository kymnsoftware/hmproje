<?php
// Veritabanı bağlantısı
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "db_kartlar";

// Filtreleme parametreleri
$search = isset($_GET['search']) ? $_GET['search'] : '';
$date = isset($_GET['date']) ? $_GET['date'] : '';
$type = isset($_GET['type']) ? $_GET['type'] : '';
$format = isset($_GET['format']) ? $_GET['format'] : 'excel'; // 'excel', 'pdf', 'csv'

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // SQL sorgusu
    $sql = "
        SELECT a.id, a.card_number, c.name, c.surname, a.event_type, a.event_time, a.device_id
        FROM attendance_logs a
        LEFT JOIN cards c ON a.card_number = c.card_number
        WHERE 1=1
    ";
    $params = [];
    
    if (!empty($search)) {
        $sql .= " AND (c.name LIKE :search OR c.surname LIKE :search OR a.card_number LIKE :search)";
        $params[':search'] = "%$search%";
    }
    
    if (!empty($date)) {
        $sql .= " AND DATE(a.event_time) = :date";
        $params[':date'] = $date;
    }
    
    if (!empty($type)) {
        $sql .= " AND a.event_type = :type";
        $params[':type'] = $type;
    }
    
    $sql .= " ORDER BY a.event_time DESC";
    
    $stmt = $conn->prepare($sql);
    
    // Parametreleri bağla
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Başlık için tarih metni oluştur
    $dateText = !empty($date) ? date('d.m.Y', strtotime($date)) : 'Tüm Tarihler';
    $typeText = '';
    if (!empty($type)) {
        $typeText = $type == 'ENTRY' ? 'Giriş Kayıtları' : 'Çıkış Kayıtları';
    } else {
        $typeText = 'Giriş-Çıkış Kayıtları';
    }
    $reportTitle = "PDKS $typeText - $dateText";
    
    // Veri formatını seç ve uygun içeriği oluştur
    switch ($format) {
        case 'pdf':
            generatePDF($data, $reportTitle);
            break;
            
        case 'csv':
            generateCSV($data);
            break;
            
        case 'excel':
        default:
            generateExcel($data, $reportTitle);
            break;
    }
    
} catch(PDOException $e) {
    die("Veritabanı hatası: " . $e->getMessage());
}

// Excel formatında veri çıktısı oluştur
function generateExcel($data, $title) {
    // PHPExcel yerine basit bir Excel dosyası (HTML tabanlı) oluştur
    $filename = 'giris_cikis_raporu_' . date('Y-m-d_H-i-s') . '.xls';
    
    // HTTP başlıkları
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    
    // Excel içeriği
    echo '<!DOCTYPE html>';
    echo '<html>';
    echo '<head>';
    echo '<meta charset="UTF-8">';
    echo '<title>' . $title . '</title>';
    echo '<style>';
    echo 'table { border-collapse: collapse; width: 100%; }';
    echo 'th, td { border: 1px solid #000; padding: 5px; }';
    echo 'th { background-color: #4CAF50; color: white; }';
    echo '.entry { color: green; }';
    echo '.exit { color: red; }';
    echo '</style>';
    echo '</head>';
    echo '<body>';
    echo '<h1>' . $title . '</h1>';
    echo '<table>';
    echo '<thead>';
    echo '<tr>';
    echo '<th>ID</th>';
    echo '<th>Kart Numarası</th>';
    echo '<th>Ad</th>';
    echo '<th>Soyad</th>';
    echo '<th>İşlem Tipi</th>';
    echo '<th>Tarih/Saat</th>';
    echo '<th>Cihaz</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';
    
    foreach ($data as $row) {
        $eventTypeText = ($row['event_type'] == 'ENTRY') ? 'Giriş' : 'Çıkış';
        $colorClass = ($row['event_type'] == 'ENTRY') ? 'entry' : 'exit';
        
        echo '<tr>';
        echo '<td>' . $row['id'] . '</td>';
        echo '<td>' . $row['card_number'] . '</td>';
        echo '<td>' . $row['name'] . '</td>';
        echo '<td>' . $row['surname'] . '</td>';
        echo '<td class="' . $colorClass . '">' . $eventTypeText . '</td>';
        echo '<td>' . date('d.m.Y H:i:s', strtotime($row['event_time'])) . '</td>';
        echo '<td>Cihaz #' . $row['device_id'] . '</td>';
        echo '</tr>';
    }
    
    echo '</tbody>';
    echo '</table>';
    echo '<div style="text-align: center; margin-top: 20px; font-size: 12px;">';
    echo 'PDKS Raporu - Oluşturulma Tarihi: ' . date('d.m.Y H:i:s');
    echo '</div>';
    echo '</body>';
    echo '</html>';
    exit;
}

// CSV formatında veri çıktısı oluştur
function generateCSV($data) {
    $filename = 'giris_cikis_raporu_' . date('Y-m-d_H-i-s') . '.csv';
    
    // HTTP başlıkları
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    // Çıktı tamponu
    $output = fopen('php://output', 'w');
    
    // UTF-8 BOM
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Başlık satırı
    fputcsv($output, ['ID', 'Kart Numarası', 'Ad', 'Soyad', 'İşlem Tipi', 'Tarih/Saat', 'Cihaz']);
    
    // Veriler
    foreach ($data as $row) {
        $eventType = ($row['event_type'] == 'ENTRY') ? 'Giriş' : 'Çıkış';
        fputcsv($output, [
            $row['id'],
            $row['card_number'],
            $row['name'],
            $row['surname'],
            $eventType,
            date('d.m.Y H:i:s', strtotime($row['event_time'])),
            'Cihaz #' . $row['device_id']
        ]);
    }
    
    fclose($output);
    exit;
}

// PDF formatında veri çıktısı oluştur
function generatePDF($data, $title) {
    $filename = 'giris_cikis_raporu_' . date('Y-m-d_H-i-s') . '.html';
    
    // HTTP başlıkları
    header('Content-Type: text/html; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    // HTML çıktısı (tarayıcıdan yazdırılabilir)
    echo '<!DOCTYPE html>';
    echo '<html>';
    echo '<head>';
    echo '<meta charset="UTF-8">';
    echo '<title>' . $title . '</title>';
    echo '<style>';
    echo 'body { font-family: Arial, sans-serif; }';
    echo 'table { border-collapse: collapse; width: 100%; margin-top: 20px; }';
    echo 'th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }';
    echo 'th { background-color: #4CAF50; color: white; }';
    echo 'tr:nth-child(even) { background-color: #f2f2f2; }';
    echo '.entry { color: green; font-weight: bold; }';
    echo '.exit { color: red; font-weight: bold; }';
    echo 'h1 { text-align: center; color: #333; }';
    echo '@media print { .no-print { display: none; } }';
    echo '</style>';
    echo '</head>';
    echo '<body>';
    echo '<div class="no-print" style="text-align: center; margin: 20px;">';
    echo '<button onclick="window.print()">Yazdır</button> ';
    echo '<button onclick="window.close()">Kapat</button>';
    echo '</div>';
    echo '<h1>' . $title . '</h1>';
    echo '<table>';
    echo '<thead>';
    echo '<tr>';
    echo '<th>ID</th>';
    echo '<th>Kart Numarası</th>';
    echo '<th>Ad</th>';
    echo '<th>Soyad</th>';
    echo '<th>İşlem Tipi</th>';
    echo '<th>Tarih/Saat</th>';
    echo '<th>Cihaz</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';
    
    foreach ($data as $row) {
        $eventTypeText = ($row['event_type'] == 'ENTRY') ? 'Giriş' : 'Çıkış';
        $colorClass = ($row['event_type'] == 'ENTRY') ? 'entry' : 'exit';
        
        echo '<tr>';
        echo '<td>' . $row['id'] . '</td>';
        echo '<td>' . $row['card_number'] . '</td>';
        echo '<td>' . $row['name'] . '</td>';
        echo '<td>' . $row['surname'] . '</td>';
        echo '<td class="' . $colorClass . '">' . $eventTypeText . '</td>';
        echo '<td>' . date('d.m.Y H:i:s', strtotime($row['event_time'])) . '</td>';
        echo '<td>Cihaz #' . $row['device_id'] . '</td>';
        echo '</tr>';
    }
    
    echo '</tbody>';
    echo '</table>';
    echo '<div style="text-align: center; margin-top: 20px; font-size: 12px;">';
    echo 'PDKS Raporu - Oluşturulma Tarihi: ' . date('d.m.Y H:i:s');
    echo '</div>';
    echo '</body>';
    echo '</html>';
    exit;
}
?>