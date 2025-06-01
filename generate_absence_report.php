<?php
// Veritabanı bağlantısı
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "db_kartlar";

$report_type = isset($_GET['type']) ? $_GET['type'] : 'daily';
$format = isset($_GET['format']) ? $_GET['format'] : 'excel';

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $title = '';
    $data = [];
    $headers = [];
    
    switch($report_type) {
        case 'daily':
            $data = generateDailyReport($conn);
            break;
        case 'monthly':
            $data = generateMonthlyReport($conn);
            break;
        case 'employee':
            $data = generateEmployeeReport($conn);
            break;
        case 'department':
            $data = generateDepartmentReport($conn);
            break;
        case 'summary':
            $data = generateSummaryReport($conn);
            break;
    }
    
    if ($format === 'html') {
        outputHTML($data);
    } elseif ($format === 'pdf') {
        outputPDF($data);
    } else {
        outputExcel($data);
    }
    
} catch(PDOException $e) {
    die("Veritabanı hatası: " . $e->getMessage());
}

// Günlük rapor oluştur
function generateDailyReport($conn) {
    $date = $_GET['date'] ?? date('Y-m-d');
    $department = $_GET['department'] ?? '';
    $status = $_GET['status'] ?? '';
    
    $sql = "
        SELECT c.name, c.surname, c.department, c.position,
               at.name as absence_type_name, at.color,
               a.start_date, a.end_date, a.total_days, a.reason,
               a.is_justified, a.created_at,
               creator.name as created_by_name
        FROM absences a
        JOIN cards c ON a.user_id = c.user_id
        JOIN absence_types at ON a.absence_type_id = at.id
        LEFT JOIN cards creator ON a.created_by = creator.user_id
        WHERE :date BETWEEN a.start_date AND a.end_date
    ";
    
    $params = [':date' => $date];
    
    if (!empty($department)) {
        $sql .= " AND c.department = :department";
        $params[':department'] = $department;
    }
    
    if (!empty($status)) {
        if ($status == 'justified') {
            $sql .= " AND a.is_justified = 1";
        } elseif ($status == 'unjustified') {
            $sql .= " AND a.is_justified = 0";
        }
    }
    
    $sql .= " ORDER BY c.department, c.name, c.surname";
    
    $stmt = $conn->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    
    return [
        'title' => 'Günlük Devamsızlık Raporu - ' . date('d.m.Y', strtotime($date)),
        'headers' => ['Personel', 'Departman', 'Pozisyon', 'Devamsızlık Türü', 'Başlangıç', 'Bitiş', 'Gün', 'Durum', 'Sebep', 'Kaydeden'],
        'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)
    ];
}

// Aylık rapor oluştur
function generateMonthlyReport($conn) {
    $month = $_GET['month'] ?? date('Y-m');
    $department = $_GET['department'] ?? '';
    $absence_type = $_GET['absence_type'] ?? '';
    
    $monthStart = $month . '-01';
    $monthEnd = date('Y-m-t', strtotime($monthStart));
    
    $sql = "
        SELECT c.name, c.surname, c.department,
               COUNT(a.id) as absence_count,
               SUM(a.total_days) as total_days,
               SUM(CASE WHEN a.is_justified = 1 THEN a.total_days ELSE 0 END) as justified_days,
               SUM(CASE WHEN a.is_justified = 0 THEN a.total_days ELSE 0 END) as unjustified_days,
               GROUP_CONCAT(DISTINCT at.name SEPARATOR ', ') as absence_types
        FROM absences a
        JOIN cards c ON a.user_id = c.user_id
        JOIN absence_types at ON a.absence_type_id = at.id
        WHERE a.start_date >= :month_start AND a.end_date <= :month_end
    ";
    
    $params = [
        ':month_start' => $monthStart,
        ':month_end' => $monthEnd
    ];
    
    if (!empty($department)) {
        $sql .= " AND c.department = :department";
        $params[':department'] = $department;
    }
    
    if (!empty($absence_type)) {
        $sql .= " AND a.absence_type_id = :absence_type";
        $params[':absence_type'] = $absence_type;
    }
    
    $sql .= " GROUP BY c.user_id ORDER BY c.department, total_days DESC";
    
    $stmt = $conn->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    
    return [
        'title' => 'Aylık Devamsızlık Raporu - ' . date('F Y', strtotime($monthStart)),
        'headers' => ['Personel', 'Departman', 'Devamsızlık Sayısı', 'Toplam Gün', 'Mazeretli Gün', 'Mazeretsiz Gün', 'Devamsızlık Türleri'],
        'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)
    ];
}

// Personel bazlı rapor oluştur
function generateEmployeeReport($conn) {
    $user_id = $_GET['user_id'] ?? '';
    $start_date = $_GET['start_date'] ?? date('Y-m-01');
    $end_date = $_GET['end_date'] ?? date('Y-m-t');
    
    if (empty($user_id)) {
        return ['title' => 'Hata', 'headers' => [], 'data' => []];
    }
    
    // Kullanıcı bilgisini al
    $stmt = $conn->prepare("SELECT name, surname, department, position FROM cards WHERE user_id = :user_id");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $userInfo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $sql = "
        SELECT a.start_date, a.end_date, a.total_days, a.reason, a.is_justified,
               at.name as absence_type_name, at.color,
               creator.name as created_by_name
        FROM absences a
        JOIN absence_types at ON a.absence_type_id = at.id
        LEFT JOIN cards creator ON a.created_by = creator.user_id
        WHERE a.user_id = :user_id
        AND a.start_date >= :start_date
        AND a.end_date <= :end_date
        ORDER BY a.start_date DESC
    ";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->bindParam(':start_date', $start_date);
    $stmt->bindParam(':end_date', $end_date);
    $stmt->execute();
    
    return [
        'title' => 'Personel Devamsızlık Raporu - ' . $userInfo['name'] . ' ' . $userInfo['surname'],
        'subtitle' => $userInfo['department'] . ' - ' . $userInfo['position'] . ' (' . date('d.m.Y', strtotime($start_date)) . ' - ' . date('d.m.Y', strtotime($end_date)) . ')',
        'headers' => ['Başlangıç Tarihi', 'Bitiş Tarihi', 'Gün Sayısı', 'Devamsızlık Türü', 'Durum', 'Sebep', 'Kaydeden'],
        'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)
    ];
}

// Departman bazlı rapor oluştur
function generateDepartmentReport($conn) {
    $start_date = $_GET['start_date'] ?? date('Y-m-01');
    $end_date = $_GET['end_date'] ?? date('Y-m-t');
    $department = $_GET['department'] ?? '';
    
    $sql = "
        SELECT c.department,
               COUNT(DISTINCT c.user_id) as employee_count,
               COUNT(a.id) as total_absences,
               SUM(a.total_days) as total_absence_days,
               SUM(CASE WHEN a.is_justified = 1 THEN a.total_days ELSE 0 END) as justified_days,
               SUM(CASE WHEN a.is_justified = 0 THEN a.total_days ELSE 0 END) as unjustified_days,
               ROUND(AVG(a.total_days), 2) as avg_absence_duration
        FROM cards c
        LEFT JOIN absences a ON c.user_id = a.user_id 
            AND a.start_date >= :start_date 
            AND a.end_date <= :end_date
        WHERE c.enabled = 'true'
        AND c.department IS NOT NULL 
        AND c.department != ''
    ";
    
    $params = [
        ':start_date' => $start_date,
        ':end_date' => $end_date
    ];
    
    if (!empty($department)) {
        $sql .= " AND c.department = :department";
        $params[':department'] = $department;
    }
    
    $sql .= " GROUP BY c.department ORDER BY total_absence_days DESC";
    
    $stmt = $conn->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    
    return [
        'title' => 'Departman Bazlı Devamsızlık Raporu',
        'subtitle' => date('d.m.Y', strtotime($start_date)) . ' - ' . date('d.m.Y', strtotime($end_date)),
        'headers' => ['Departman', 'Personel Sayısı', 'Toplam Devamsızlık', 'Toplam Gün', 'Mazeretli Gün', 'Mazeretsiz Gün', 'Ortalama Süre'],
        'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)
    ];
}

// Özet rapor oluştur
function generateSummaryReport($conn) {
    $start_date = $_GET['start_date'] ?? date('Y-m-01');
    $end_date = $_GET['end_date'] ?? date('Y-m-t');
    $group_by = $_GET['group_by'] ?? 'department';
    
    if ($group_by == 'department') {
        $sql = "
            SELECT c.department as group_name,
                   COUNT(a.id) as absence_count,
                   SUM(a.total_days) as total_days,
                   SUM(CASE WHEN a.is_justified = 1 THEN 1 ELSE 0 END) as justified_count,
                   SUM(CASE WHEN a.is_justified = 0 THEN 1 ELSE 0 END) as unjustified_count
            FROM absences a
            JOIN cards c ON a.user_id = c.user_id
            WHERE a.start_date >= :start_date AND a.end_date <= :end_date
            GROUP BY c.department
            ORDER BY total_days DESC
        ";
        $title = 'Departman Bazlı Özet Rapor';
    } elseif ($group_by == 'absence_type') {
        $sql = "
            SELECT at.name as group_name,
                   COUNT(a.id) as absence_count,
                   SUM(a.total_days) as total_days,
                   SUM(CASE WHEN a.is_justified = 1 THEN 1 ELSE 0 END) as justified_count,
                   SUM(CASE WHEN a.is_justified = 0 THEN 1 ELSE 0 END) as unjustified_count
            FROM absences a
            JOIN absence_types at ON a.absence_type_id = at.id
            WHERE a.start_date >= :start_date AND a.end_date <= :end_date
            GROUP BY at.id
            ORDER BY total_days DESC
        ";
        $title = 'Devamsızlık Türü Bazlı Özet Rapor';
    } else { // monthly
        $sql = "
            SELECT DATE_FORMAT(a.start_date, '%Y-%m') as group_name,
                   COUNT(a.id) as absence_count,
                   SUM(a.total_days) as total_days,
                   SUM(CASE WHEN a.is_justified = 1 THEN 1 ELSE 0 END) as justified_count,
                   SUM(CASE WHEN a.is_justified = 0 THEN 1 ELSE 0 END) as unjustified_count
            FROM absences a
            WHERE a.start_date >= :start_date AND a.end_date <= :end_date
            GROUP BY DATE_FORMAT(a.start_date, '%Y-%m')
            ORDER BY group_name DESC
        ";
        $title = 'Aylık Özet Rapor';
    }
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':start_date', $start_date);
    $stmt->bindParam(':end_date', $end_date);
    $stmt->execute();
    
    return [
        'title' => $title,
        'subtitle' => date('d.m.Y', strtotime($start_date)) . ' - ' . date('d.m.Y', strtotime($end_date)),
        'headers' => ['Grup', 'Devamsızlık Sayısı', 'Toplam Gün', 'Mazeretli', 'Mazeretsiz'],
        'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)
    ];
}

// HTML çıktısı
function outputHTML($reportData) {
    echo '<div class="report-content">';
    echo '<h4>' . $reportData['title'] . '</h4>';
    if (isset($reportData['subtitle'])) {
        echo '<p class="text-muted">' . $reportData['subtitle'] . '</p>';
    }
    
    if (count($reportData['data']) > 0) {
        echo '<div class="table-responsive">';
        echo '<table class="table table-striped table-bordered">';
        echo '<thead class="thead-dark"><tr>';
        foreach ($reportData['headers'] as $header) {
            echo '<th>' . $header . '</th>';
        }
        echo '</tr></thead><tbody>';
        
        foreach ($reportData['data'] as $row) {
            echo '<tr>';
            foreach ($row as $key => $value) {
                if ($key == 'is_justified') {
                    echo '<td>' . ($value ? 'Mazeretli' : 'Mazeretsiz') . '</td>';
                } elseif (strpos($key, 'date') !== false && $value) {
                    echo '<td>' . date('d.m.Y', strtotime($value)) . '</td>';
                } else {
                    echo '<td>' . ($value ?: '-') . '</td>';
                }
            }
            echo '</tr>';
        }
        
        echo '</tbody></table>';
        echo '</div>';
    } else {
        echo '<div class="alert alert-info">Bu kriterlere uygun veri bulunamadı.</div>';
    }
    echo '</div>';
}

// Excel çıktısı
function outputExcel($reportData) {
    $filename = 'devamsizlik_raporu_' . date('Y-m-d_H-i-s') . '.xls';
    
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    
    echo '<!DOCTYPE html>';
    echo '<html><head><meta charset="UTF-8">';
    echo '<style>table{border-collapse:collapse;width:100%;}th,td{border:1px solid #000;padding:5px;}th{background-color:#f44336;color:white;}</style>';
    echo '</head><body>';
    echo '<h1>' . $reportData['title'] . '</h1>';
    if (isset($reportData['subtitle'])) {
        echo '<p>' . $reportData['subtitle'] . '</p>';
    }
    
    if (count($reportData['data']) > 0) {
        echo '<table><thead><tr>';
        foreach ($reportData['headers'] as $header) {
            echo '<th>' . $header . '</th>';
        }
        echo '</tr></thead><tbody>';
        
        foreach ($reportData['data'] as $row) {
            echo '<tr>';
            foreach ($row as $key => $value) {
                if ($key == 'is_justified') {
                    echo '<td>' . ($value ? 'Mazeretli' : 'Mazeretsiz') . '</td>';
                } elseif (strpos($key, 'date') !== false && $value) {
                    echo '<td>' . date('d.m.Y', strtotime($value)) . '</td>';
                } else {
                    echo '<td>' . ($value ?: '-') . '</td>';
                }
            }
            echo '</tr>';
        }
        
        echo '</tbody></table>';
    }
    
    echo '<div style="margin-top:20px;font-size:12px;">PDKS Devamsızlık Raporu - ' . date('d.m.Y H:i:s') . '</div>';
    echo '</body></html>';
}

// PDF çıktısı (HTML olarak)
function outputPDF($reportData) {
    $filename = 'devamsizlik_raporu_' . date('Y-m-d_H-i-s') . '.html';
    
    header('Content-Type: text/html; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    echo '<!DOCTYPE html>';
    echo '<html><head><meta charset="UTF-8">';
    echo '<style>body{font-family:Arial,sans-serif;}table{border-collapse:collapse;width:100%;margin-top:20px;}th,td{border:1px solid #ddd;padding:8px;text-align:left;}th{background-color:#f44336;color:white;}tr:nth-child(even){background-color:#f2f2f2;}h1{text-align:center;color:#333;}@media print{.no-print{display:none;}}</style>';
    echo '</head><body>';
    echo '<div class="no-print" style="text-align:center;margin:20px;"><button onclick="window.print()">Yazdır</button> <button onclick="window.close()">Kapat</button></div>';
    echo '<h1>' . $reportData['title'] . '</h1>';
    if (isset($reportData['subtitle'])) {
        echo '<p style="text-align:center;">' . $reportData['subtitle'] . '</p>';
    }
    
    if (count($reportData['data']) > 0) {
        echo '<table><thead><tr>';
        foreach ($reportData['headers'] as $header) {
            echo '<th>' . $header . '</th>';
        }
        echo '</tr></thead><tbody>';
        
        foreach ($reportData['data'] as $row) {
            echo '<tr>';
            foreach ($row as $key => $value) {
                if ($key == 'is_justified') {
                    echo '<td>' . ($value ? 'Mazeretli' : 'Mazeretsiz') . '</td>';
                } elseif (strpos($key, 'date') !== false && $value) {
                    echo '<td>' . date('d.m.Y', strtotime($value)) . '</td>';
                } else {
                    echo '<td>' . ($value ?: '-') . '</td>';
                }
            }
            echo '</tr>';
        }
        
        echo '</tbody></table>';
    }
    
    echo '<div style="text-align:center;margin-top:20px;font-size:12px;">PDKS Devamsızlık Raporu - ' . date('d.m.Y H:i:s') . '</div>';
    echo '</body></html>';
}
?>