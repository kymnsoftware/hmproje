<?php
session_start();
// Oturum kontrolü
if (!isset($_SESSION['user_id']) || $_SESSION['privilege'] < 1) {
    die('Yetkisiz erişim!');
}

// Veritabanı bağlantısı
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "db_kartlar";

// salary_calculator.php'den fonksiyonları include et
require_once('salary_calculator.php');

$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');
$department = $_GET['department'] ?? '';

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Kullanıcıları al
    $sql = "SELECT user_id, name, surname, department FROM cards WHERE enabled = 'true'";
    $params = [];
    
    if (!empty($department)) {
        $sql .= " AND department = :department";
        $params[':department'] = $department;
    }
    
    $sql .= " ORDER BY name, surname";
    
    $stmt = $conn->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Excel başlıkları
    $filename = 'maas_raporu_' . date('Y-m-d_H-i-s') . '.xls';
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    
    echo '<!DOCTYPE html>';
    echo '<html><head><meta charset="UTF-8">';
    echo '<style>
            table { border-collapse: collapse; width: 100%; }
            th, td { border: 1px solid #000; padding: 5px; text-align: center; }
            th { background-color: #4CAF50; color: white; font-weight: bold; }
            .success { background-color: #d4edda; }
            .danger { background-color: #f8d7da; }
          </style>';
    echo '</head><body>';
    
    echo '<h1>Maaş Hesaplama Raporu</h1>';
    echo '<p>Dönem: ' . date('d.m.Y', strtotime($start_date)) . ' - ' . date('d.m.Y', strtotime($end_date)) . '</p>';
    if (!empty($department)) {
        echo '<p>Departman: ' . $department . '</p>';
    }
    echo '<p>Rapor Tarihi: ' . date('d.m.Y H:i:s') . '</p>';
    
    echo '<table>';
    echo '<thead>';
    echo '<tr>';
    echo '<th>Personel</th>';
    echo '<th>Departman</th>';
    echo '<th>Sabit Maaş</th>';
    echo '<th>Çalışılan Gün</th>';
    echo '<th>İzinli Gün</th>';
    echo '<th>Toplam Devam</th>';
    echo '<th>Gerekli Minimum</th>';
    echo '<th>Eksik Gün</th>';
    echo '<th>Devam Oranı (%)</th>';
    echo '<th>Kesinti Tutarı</th>';
    echo '<th>Net Maaş</th>';
    echo '<th>Durum</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';
    
    $totalGrossSalary = 0;
    $totalNetSalary = 0;
    $totalDeductions = 0;
    $meetingCount = 0;
    $notMeetingCount = 0;
    
    foreach ($users as $user) {
        $calculation = calculateFixedSalary($user['user_id'], $start_date, $end_date, $conn);
        
        if ($calculation['success']) {
            $data = $calculation;
            $rowClass = $data['salary']['meets_minimum_requirement'] ? 'success' : 'danger';
            $status = $data['salary']['meets_minimum_requirement'] ? 'Şartı Karşılıyor' : 'Şartı Karşılamıyor';
            
            echo '<tr class="' . $rowClass . '">';
            echo '<td>' . $data['employee']['name'] . '</td>';
            echo '<td>' . $data['employee']['department'] . '</td>';
            echo '<td>' . number_format($data['salary']['fixed_salary'], 2, ',', '.') . '</td>';
            echo '<td>' . $data['attendance']['worked_days'] . '</td>';
            echo '<td>' . $data['attendance']['approved_leave_days'] . '</td>';
            echo '<td>' . $data['attendance']['total_attended_days'] . '</td>';
            echo '<td>' . $data['period']['required_work_days'] . '</td>';
            echo '<td>' . $data['attendance']['missing_days'] . '</td>';
            echo '<td>' . $data['attendance']['attendance_rate'] . '</td>';
            echo '<td>' . number_format($data['salary']['deduction_amount'], 2, ',', '.') . '</td>';
            echo '<td>' . number_format($data['salary']['net_salary'], 2, ',', '.') . '</td>';
            echo '<td>' . $status . '</td>';
            echo '</tr>';
            
            $totalGrossSalary += $data['salary']['fixed_salary'];
            $totalNetSalary += $data['salary']['net_salary'];
            $totalDeductions += $data['salary']['deduction_amount'];
            
            if ($data['salary']['meets_minimum_requirement']) {
                $meetingCount++;
            } else {
                $notMeetingCount++;
            }
        }
    }
    
    // Toplam satırı
    echo '<tr style="background-color: #f0f0f0; font-weight: bold;">';
    echo '<td colspan="2">TOPLAM</td>';
    echo '<td>' . number_format($totalGrossSalary, 2, ',', '.') . '</td>';
    echo '<td colspan="7">-</td>';
    echo '<td>' . number_format($totalDeductions, 2, ',', '.') . '</td>';
    echo '<td>' . number_format($totalNetSalary, 2, ',', '.') . '</td>';
    echo '<td>-</td>';
    echo '</tr>';
    
    echo '</tbody>';
    echo '</table>';
    
    // Özet bilgiler
    echo '<br><br>';
    echo '<h3>Özet Bilgiler</h3>';
    echo '<table style="width: 50%;">';
    echo '<tr><td><strong>Toplam Personel:</strong></td><td>' . count($users) . '</td></tr>';
    echo '<tr><td><strong>Şartı Karşılayan:</strong></td><td>' . $meetingCount . '</td></tr>';
    echo '<tr><td><strong>Şartı Karşılamayan:</strong></td><td>' . $notMeetingCount . '</td></tr>';
    echo '<tr><td><strong>Toplam Brüt Maaş:</strong></td><td>' . number_format($totalGrossSalary, 2, ',', '.') . ' TL</td></tr>';
    echo '<tr><td><strong>Toplam Kesinti:</strong></td><td>' . number_format($totalDeductions, 2, ',', '.') . ' TL</td></tr>';
    echo '<tr><td><strong>Toplam Net Maaş:</strong></td><td>' . number_format($totalNetSalary, 2, ',', '.') . ' TL</td></tr>';
    echo '</table>';
    
    echo '<div style="margin-top: 20px; font-size: 12px; text-align: center;">';
    echo 'PDKS Maaş Raporu - Oluşturulma Tarihi: ' . date('d.m.Y H:i:s');
    echo '</div>';
    
    echo '</body></html>';
    
} catch(PDOException $e) {
    die('Veritabanı hatası: ' . $e->getMessage());
}
?>