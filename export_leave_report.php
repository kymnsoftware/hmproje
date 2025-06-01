<?php
session_start();
// Oturum kontrolü
if (!isset($_SESSION['user_id']) || $_SESSION['privilege'] < 1) {
    header('Location: login.php');
    exit;
}

// Veritabanı bağlantısı
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "db_kartlar";

// Parametre kontrolü
if (!isset($_GET['report_type'])) {
    die('Rapor türü belirtilmedi!');
}

$report_type = $_GET['report_type'];
$format = isset($_GET['format']) ? $_GET['format'] : 'excel';
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-01-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-12-31');
$department = isset($_GET['department']) ? $_GET['department'] : '';
$user_id = isset($_GET['user_id']) ? $_GET['user_id'] : '';

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // İzin türlerini al
    $stmt = $conn->query("SELECT * FROM leave_types ORDER BY name");
    $leave_types = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Rapor başlığı
    $title = '';
    if ($report_type == 'department') {
        $title = 'Departman Bazlı İzin Raporu';
        if (!empty($department)) {
            $title .= ' - ' . $department;
        }
    } elseif ($report_type == 'user') {
        $title = 'Personel Bazlı İzin Raporu';
        if (!empty($user_id)) {
            // Kullanıcı adını al
            $stmt = $conn->prepare("SELECT name, surname FROM cards WHERE user_id = :user_id");
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($user) {
                $title .= ' - ' . $user['name'] . ' ' . $user['surname'];
            }
        }
    } elseif ($report_type == 'leave_type') {
        $title = 'İzin Türü Bazlı Rapor';
    }
    
    $title .= ' (' . date('d.m.Y', strtotime($start_date)) . ' - ' . date('d.m.Y', strtotime($end_date)) . ')';
    
    // Rapor verilerini hazırla
    $reportData = [];
    
    if ($report_type == 'department') {
        // Departman bazlı rapor verileri
        $sql = "
            SELECT c.department, lt.id as leave_type_id, lt.name as leave_type_name, lt.color,
                   COUNT(DISTINCT lr.id) as leave_count,
                   SUM(lr.total_days) as total_days
            FROM leave_requests lr
            JOIN cards c ON lr.user_id = c.user_id
            JOIN leave_types lt ON lr.leave_type_id = lt.id
            WHERE lr.status = 'approved'
            AND lr.start_date >= :start_date
            AND lr.end_date <= :end_date
        ";
        
        if (!empty($department)) {
            $sql .= " AND c.department = :department";
        }
        
        $sql .= " GROUP BY c.department, lt.id
                  ORDER BY c.department, lt.name";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':start_date', $start_date);
        $stmt->bindParam(':end_date', $end_date);
        
        if (!empty($department)) {
            $stmt->bindParam(':department', $department);
        }
        
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Sonuçları departmanlara göre düzenle
        $departments = [];
        $totalsByType = [];
        $grandTotal = 0;
        
        foreach ($result as $row) {
            if (!isset($departments[$row['department']])) {
                $departments[$row['department']] = [];
            }
            
            $departments[$row['department']][$row['leave_type_id']] = [
                'name' => $row['leave_type_name'],
                'color' => $row['color'],
                'count' => $row['leave_count'],
                'days' => $row['total_days']
            ];
            
            // İzin türüne göre toplamları hesapla
            if (!isset($totalsByType[$row['leave_type_id']])) {
                $totalsByType[$row['leave_type_id']] = [
                    'name' => $row['leave_type_name'],
                    'color' => $row['color'],
                    'count' => 0,
                    'days' => 0
                ];
            }
            
            $totalsByType[$row['leave_type_id']]['count'] += $row['leave_count'];
            $totalsByType[$row['leave_type_id']]['days'] += $row['total_days'];
            $grandTotal += $row['total_days'];
        }
        
        $reportData = [
            'departments' => $departments,
            'leave_types' => $leave_types,
            'totals_by_type' => $totalsByType,
            'grand_total' => $grandTotal
        ];
    } 
    // Benzer şekilde diğer rapor türleri için veriler hazırlanır
    
    // Rapor formatını belirle ve oluştur
    if ($format == 'excel') {
        exportExcel($reportData, $title, $report_type);
    } elseif ($format == 'pdf') {
        exportPDF($reportData, $title, $report_type);
    }
    
} catch(PDOException $e) {
    die("Veritabanı hatası: " . $e->getMessage());
}

// Excel formatında rapor oluştur
function exportExcel($data, $title, $report_type) {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="izin_raporu_' . date('Y-m-d') . '.xls"');
    header('Cache-Control: max-age=0');
    
    echo '<!DOCTYPE html>';
    echo '<html>';
    echo '<head>';
    echo '<meta charset="UTF-8">';
    echo '<style>';
    echo 'table { border-collapse: collapse; width: 100%; }';
    echo 'th, td { border: 1px solid #000; padding: 5px; }';
    echo 'th { background-color: #ccc; }';
    echo '.total-row { font-weight: bold; background-color: #eee; }';
    echo '</style>';
    echo '</head>';
    echo '<body>';
    echo '<h1>' . $title . '</h1>';
    
    if ($report_type == 'department') {
        echo '<table>';
        echo '<tr>';
        echo '<th>Departman</th>';
        
        foreach ($data['leave_types'] as $type) {
            echo '<th>' . $type['name'] . '</th>';
        }
        
        echo '<th>Toplam Gün</th>';
        echo '</tr>';
        
        $departmentTotals = [];
        foreach ($data['departments'] as $dept => $types) {
            $deptTotal = 0;
            echo '<tr>';
            echo '<td>' . $dept . '</td>';
            
            foreach ($data['leave_types'] as $type) {
                $typeId = $type['id'];
                $days = isset($types[$typeId]) ? $types[$typeId]['days'] : 0;
                $deptTotal += $days;
                echo '<td>' . ($days > 0 ? $days . ' gün' : '-') . '</td>';
            }
            
            echo '<td><b>' . $deptTotal . ' gün</b></td>';
            echo '</tr>';
            
            $departmentTotals[$dept] = $deptTotal;
        }
        
        // Toplamlar satırı
        echo '<tr class="total-row">';
        echo '<td><b>Toplam</b></td>';
        
        $totalAllTypes = 0;
        foreach ($data['leave_types'] as $type) {
            $typeId = $type['id'];
            $typeDays = isset($data['totals_by_type'][$typeId]) ? $data['totals_by_type'][$typeId]['days'] : 0;
            $totalAllTypes += $typeDays;
            echo '<td><b>' . ($typeDays > 0 ? $typeDays . ' gün' : '-') . '</b></td>';
        }
        
        echo '<td><b>' . $totalAllTypes . ' gün</b></td>';
        echo '</tr>';
        
        echo '</table>';
    }
    // Diğer rapor türleri için benzer tablolar oluşturulur
    
    echo '</body>';
    echo '</html>';
    exit;
}

// PDF formatında rapor oluştur (HTML olarak indirip yazdırılabilir)
function exportPDF($data, $title, $report_type) {
    header('Content-Type: text/html');
    header('Content-Disposition: attachment; filename="izin_raporu_' . date('Y-m-d') . '.html"');
    
    echo '<!DOCTYPE html>';
    echo '<html>';
    echo '<head>';
    echo '<meta charset="UTF-8">';
    echo '<style>';
    echo 'body { font-family: Arial, sans-serif; }';
    echo 'table { border-collapse: collapse; width: 100%; margin-top: 20px; }';
    echo 'th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }';
    echo 'th { background-color: #f2f2f2; }';
    echo 'tr:nth-child(even) { background-color: #f9f9f9; }';
    echo '.total-row { font-weight: bold; background-color: #eee; }';
    echo 'h1 { text-align: center; }';
    echo '@media print { .no-print { display: none; } }';
    echo '</style>';
    echo '</head>';
    echo '<body>';
    
    echo '<div class="no-print" style="text-align: center; margin: 20px;">';
    echo '<button onclick="window.print()">Yazdır</button> ';
    echo '<button onclick="window.close()">Kapat</button>';
    echo '</div>';
    
    echo '<h1>' . $title . '</h1>';
    
    if ($report_type == 'department') {
        // Benzer şekilde Excel raporuna benzer bir tablo oluşturulur
    }
    
    echo '<div style="text-align: center; margin-top: 30px; font-size: 12px; color: #777;">';
    echo 'Bu rapor PDKS (Personel Devam Kontrol Sistemi) tarafından oluşturulmuştur. Oluşturulma Tarihi: ' . date('d.m.Y H:i:s');
    echo '</div>';
    
    echo '</body>';
    echo '</html>';
    exit;
}
?>