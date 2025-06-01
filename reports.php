<?php
// Veritabanı bağlantısı
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "db_kartlar";
// Rapor tipi
$report_type = isset($_GET['type']) ? $_GET['type'] : 'daily';
$format = isset($_GET['format']) ? $_GET['format'] : 'html';
$date_start = isset($_GET['date_start']) ? $_GET['date_start'] : date('Y-m-d');
$date_end = isset($_GET['date_end']) ? $_GET['date_end'] : date('Y-m-d');
$department = isset($_GET['department']) ? $_GET['department'] : '';
$user_id = isset($_GET['user_id']) ? $_GET['user_id'] : '';

// Veritabanına bağlan
try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Rapor başlığı ve sorgu belirleme
    $title = '';
    $sql = '';
    $params = [];
    
    switch($report_type) {
        case 'daily':
            $title = 'Günlük Rapor - ' . date('d.m.Y', strtotime($date_start));
            $sql = "
                SELECT c.user_id, c.name, c.surname, c.department, c.position,
                       MIN(CASE WHEN a.event_type = 'ENTRY' THEN a.event_time ELSE NULL END) as first_entry,
                       MAX(CASE WHEN a.event_type = 'EXIT' THEN a.event_time ELSE NULL END) as last_exit,
                       SEC_TO_TIME(TIMESTAMPDIFF(SECOND,
                           MIN(CASE WHEN a.event_type = 'ENTRY' THEN a.event_time ELSE NULL END),
                           MAX(CASE WHEN a.event_type = 'EXIT' THEN a.event_time ELSE NULL END)
                       )) as total_time
                FROM cards c
                LEFT JOIN attendance_logs a ON c.card_number = a.card_number
                  AND DATE(a.event_time) = :date
                WHERE c.enabled = 'true'
            ";
            $params[':date'] = $date_start;
            if (!empty($department)) {
                $sql .= " AND c.department = :department";
                $params[':department'] = $department;
            }
            $sql .= " GROUP BY c.user_id, c.name, c.surname, c.department, c.position
                      HAVING first_entry IS NOT NULL OR last_exit IS NOT NULL";
            break;
            
        case 'monthly':
            $month = date('m', strtotime($date_start));
            $year = date('Y', strtotime($date_start));
            $title = 'Aylık Rapor - ' . date('F Y', strtotime("$year-$month-01"));
            $sql = "
                SELECT c.user_id, c.name, c.surname, c.department, c.position,
                       COUNT(DISTINCT DATE(a.event_time)) as work_days,
                       SEC_TO_TIME(SUM(
                           TIMESTAMPDIFF(SECOND,
                               MIN(CASE WHEN a.event_type = 'ENTRY' THEN a.event_time ELSE NULL END),
                               MAX(CASE WHEN a.event_type = 'EXIT' THEN a.event_time ELSE NULL END)
                           )
                       )) as total_time
                FROM cards c
                LEFT JOIN attendance_logs a ON c.card_number = a.card_number
                  AND MONTH(a.event_time) = :month
                  AND YEAR(a.event_time) = :year
                WHERE c.enabled = 'true'
            ";
            $params[':month'] = $month;
            $params[':year'] = $year;
            if (!empty($department)) {
                $sql .= " AND c.department = :department";
                $params[':department'] = $department;
            }
            $sql .= " GROUP BY c.user_id, c.name, c.surname, c.department, c.position
                      HAVING work_days > 0";
            break;
            
        case 'department':
            $title = 'Departman Raporu';
            if (!empty($department)) {
                $title .= ' - ' . $department;
            }
            $title .= ' (' . date('d.m.Y', strtotime($date_start)) . ' - ' . date('d.m.Y', strtotime($date_end)) . ')';
            
            $sql = "
                SELECT c.department,
                       COUNT(DISTINCT c.user_id) as employee_count,
                       COUNT(DISTINCT DATE(a.event_time)) as work_days,
                       SEC_TO_TIME(SUM(
                           TIMESTAMPDIFF(SECOND,
                               MIN(CASE WHEN a.event_type = 'ENTRY' THEN a.event_time ELSE NULL END),
                               MAX(CASE WHEN a.event_type = 'EXIT' THEN a.event_time ELSE NULL END)
                           )
                       )) as total_time
                FROM cards c
                LEFT JOIN attendance_logs a ON c.card_number = a.card_number
                  AND DATE(a.event_time) BETWEEN :date_start AND :date_end
                WHERE c.department IS NOT NULL AND c.department != ''
                  AND c.enabled = 'true'
            ";
            $params[':date_start'] = $date_start;
            $params[':date_end'] = $date_end;
            if (!empty($department)) {
                $sql .= " AND c.department = :department";
                $params[':department'] = $department;
            }
            $sql .= " GROUP BY c.department";
            break;
            
        case 'user':
            if (empty($user_id)) {
                die("Kullanıcı ID belirtilmelidir.");
            }
            
            $title = 'Personel Raporu';
            
            // Önce kullanıcı bilgisini al
            $userStmt = $conn->prepare("SELECT name, surname FROM cards WHERE user_id = :user_id");
            $userStmt->bindParam(':user_id', $user_id);
            $userStmt->execute();
            
            if ($userStmt->rowCount() > 0) {
                $userInfo = $userStmt->fetch(PDO::FETCH_ASSOC);
                $title .= ' - ' . $userInfo['name'] . ' ' . $userInfo['surname'];
            } else {
                $title .= ' - ID: ' . $user_id;
            }
            
            $title .= ' (' . date('d.m.Y', strtotime($date_start)) . ' - ' . date('d.m.Y', strtotime($date_end)) . ')';
            
            $sql = "
                SELECT DATE(a.event_time) as work_date,
                       MIN(CASE WHEN a.event_type = 'ENTRY' THEN a.event_time ELSE NULL END) as first_entry,
                       MAX(CASE WHEN a.event_type = 'EXIT' THEN a.event_time ELSE NULL END) as last_exit,
                       SEC_TO_TIME(TIMESTAMPDIFF(SECOND,
                           MIN(CASE WHEN a.event_type = 'ENTRY' THEN a.event_time ELSE NULL END),
                           MAX(CASE WHEN a.event_type = 'EXIT' THEN a.event_time ELSE NULL END)
                       )) as daily_time
                FROM cards c
                JOIN attendance_logs a ON c.card_number = a.card_number
                WHERE c.user_id = :user_id
                  AND DATE(a.event_time) BETWEEN :date_start AND :date_end
                GROUP BY DATE(a.event_time)
                ORDER BY DATE(a.event_time) DESC
            ";
            $params[':user_id'] = $user_id;
            $params[':date_start'] = $date_start;
            $params[':date_end'] = $date_end;
            break;
    }
    
    // Sorguyu çalıştır
    $stmt = $conn->prepare($sql);
    
    // Parametreleri bağla
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Formata göre çıktı oluştur
    switch($format) {
        case 'excel':
            outputExcel($data, $title, $report_type);
            break;
        case 'pdf':
            outputPDF($data, $title, $report_type);
            break;
        case 'csv':
            outputCSV($data, $title, $report_type);
            break;
        case 'html':
        default:
            outputHTML($data, $title, $report_type);
            break;
    }
} catch(PDOException $e) {
    die("Veritabanı hatası: " . $e->getMessage());
}

// HTML formatında çıktı
function outputHTML($data, $title, $report_type) {
    // HTML formatında tam rapor sayfası
    ?>
    <!DOCTYPE html>
    <html lang="tr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo $title; ?></title>
        <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@5.15.4/css/all.min.css">
        <style>
            body {
                background-color: #f8f9fa;
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                padding: 20px;
            }
            .container {
                background-color: #fff;
                border-radius: 10px;
                box-shadow: 0 2px 5px rgba(0,0,0,0.1);
                padding: 20px;
            }
            .table th {
                background-color: #4CAF50;
                color: white;
            }
            .export-buttons {
                margin-bottom: 20px;
            }
            .export-buttons .btn {
                margin-right: 5px;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="row mb-4">
                <div class="col-md-8">
                    <h1><?php echo $title; ?></h1>
                </div>
                <div class="col-md-4 text-right">
                    <div class="export-buttons">
                        <a href="<?php echo $_SERVER['REQUEST_URI'].'&format=excel'; ?>" class="btn btn-success">
                            <i class="fas fa-file-excel mr-1"></i> Excel
                        </a>
                        <a href="<?php echo $_SERVER['REQUEST_URI'].'&format=pdf'; ?>" class="btn btn-danger">
                            <i class="fas fa-file-pdf mr-1"></i> PDF
                        </a>
                        <a href="<?php echo $_SERVER['REQUEST_URI'].'&format=csv'; ?>" class="btn btn-info">
                            <i class="fas fa-file-csv mr-1"></i> CSV
                        </a>
                        <a href="index.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left mr-1"></i> Geri
                        </a>
                    </div>
                </div>
            </div>
            <?php if (count($data) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <?php foreach (array_keys($data[0]) as $column): ?>
                                    <th>
                                        <?php
                                        // Sütun başlıklarını güzelleştir
                                        $header = str_replace('_', ' ', $column);
                                        echo ucwords($header);
                                        ?>
                                    </th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data as $row): ?>
                                <tr>
                                    <?php foreach ($row as $value): ?>
                                        <td><?php echo ($value !== null) ? $value : '-'; ?></td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle mr-1"></i> Seçilen kriterlere uygun veri bulunamadı.
                </div>
            <?php endif; ?>
            <div class="text-center text-muted mt-4">
                <small>PDKS Raporu - Oluşturulma Tarihi: <?php echo date('d.m.Y H:i:s'); ?></small>
            </div>
        </div>
        <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
        <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    </body>
    </html>
    <?php
    exit;
}

// Excel formatında çıktı
function outputExcel($data, $title, $report_type) {
    $filename = 'rapor_' . date('Y-m-d_H-i-s') . '.xls';
    // HTTP başlıkları
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    echo '<!DOCTYPE html>';
    echo '<html>';
    echo '<head>';
    echo '<meta charset="UTF-8">';
    echo '<title>' . $title . '</title>';
    echo '<style>';
    echo 'table { border-collapse: collapse; width: 100%; }';
    echo 'th, td { border: 1px solid #000; padding: 5px; }';
    echo 'th { background-color: #4CAF50; color: white; }';
    echo '</style>';
    echo '</head>';
    echo '<body>';
    echo '<h1>' . $title . '</h1>';
    if (count($data) > 0) {
        echo '<table>';
        echo '<thead><tr>';
        // Sütun başlıklarını oluştur
        foreach (array_keys($data[0]) as $column) {
            // Sütun başlıklarını güzelleştir
            $header = str_replace('_', ' ', $column);
            $header = ucwords($header);
            echo '<th>' . $header . '</th>';
        }
        echo '</tr></thead>';
        echo '<tbody>';
        // Veri satırlarını oluştur
        foreach ($data as $row) {
            echo '<tr>';
            foreach ($row as $value) {
                echo '<td>' . ($value !== null ? $value : '-') . '</td>';
            }
            echo '</tr>';
        }
        echo '</tbody>';
        echo '</table>';
    } else {
        echo '<p>Seçilen kriterlere uygun veri bulunamadı.</p>';
    }
    echo '</body>';
    echo '</html>';
    exit;
}

// PDF formatında çıktı
function outputPDF($data, $title, $report_type) {
    $filename = 'rapor_' . date('Y-m-d_H-i-s') . '.html';
    // HTTP başlıkları
    header('Content-Type: text/html; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
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
    if (count($data) > 0) {
        echo '<table>';
        echo '<thead><tr>';
        // Sütun başlıklarını oluştur
        foreach (array_keys($data[0]) as $column) {
            // Sütun başlıklarını güzelleştir
            $header = str_replace('_', ' ', $column);
            $header = ucwords($header);
            echo '<th>' . $header . '</th>';
        }
        echo '</tr></thead>';
        echo '<tbody>';
        // Veri satırlarını oluştur
        foreach ($data as $row) {
            echo '<tr>';
            foreach ($row as $value) {
                echo '<td>' . ($value !== null ? $value : '-') . '</td>';
            }
            echo '</tr>';
        }
        echo '</tbody>';
        echo '</table>';
    } else {
        echo '<p>Seçilen kriterlere uygun veri bulunamadı.</p>';
    }
    echo '<div style="text-align: center; margin-top: 20px; font-size: 12px;">';
    echo 'PDKS Raporu - Oluşturulma Tarihi: ' . date('d.m.Y H:i:s');
    echo '</div>';
    echo '</body>';
    echo '</html>';
    exit;
}

// CSV formatında çıktı
function outputCSV($data, $title, $report_type) {
    $filename = 'rapor_' . date('Y-m-d_H-i-s') . '.csv';
    // HTTP başlıkları
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    // Çıktı tamponu
    $output = fopen('php://output', 'w');
    // UTF-8 BOM
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    if (count($data) > 0) {
        // Başlık satırı - sütun başlıklarını oluştur
        $headers = [];
        foreach (array_keys($data[0]) as $column) {
            // Sütun başlıklarını güzelleştir
            $header = str_replace('_', ' ', $column);
            $header = ucwords($header);
            $headers[] = $header;
        }
        fputcsv($output, $headers);
        // Veri satırlarını oluştur
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
    }
    fclose($output);
    exit;
}
?>