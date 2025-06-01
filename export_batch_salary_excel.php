<?php
// Veritabanı bağlantısı
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "db_kartlar";

// Salary calculator dosyasını dahil et
require_once('salary_calculator.php');

// Parametreleri al
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');
$department = isset($_GET['department']) ? $_GET['department'] : '';

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Tüm çalışanları al
    $sql = "SELECT user_id FROM cards WHERE enabled = 'true'";
    
    if (!empty($department)) {
        $sql .= " AND department = :department";
    }
    
    $stmt = $conn->prepare($sql);
    
    if (!empty($department)) {
        $stmt->bindParam(':department', $department);
    }
    
    $stmt->execute();
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $results = [];
    $totalRegularSalary = 0;
    $totalOvertimeSalary = 0;
    $totalSalary = 0;
    
    // Her çalışan için maaş hesapla
    foreach ($employees as $employee) {
        $result = calculateSalary($employee['user_id'], $startDate, $endDate, $conn);
        
        if ($result['success']) {
            $results[] = $result;
            $totalRegularSalary += $result['salary']['regular_salary'];
            $totalOvertimeSalary += $result['salary']['overtime_salary'];
            $totalSalary += $result['salary']['total_salary'];
        }
    }
    
    // Dosya adı
    $filename = 'toplu_maas_raporu_' . date('Y-m-d') . '.xls';
    
    // HTTP başlıkları
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    
    // Excel içeriği
    echo '<!DOCTYPE html>';
    echo '<html>';
    echo '<head>';
    echo '<meta charset="UTF-8">';
    echo '<title>Toplu Maaş Raporu</title>';
    echo '<style>';
    echo 'table { border-collapse: collapse; width: 100%; }';
    echo 'th, td { border: 1px solid #000; padding: 5px; }';
    echo 'th { background-color: #4CAF50; color: white; }';
    echo '.title { font-size: 16pt; font-weight: bold; margin-bottom: 20px; }';
    echo '.subtitle { font-size: 14pt; font-weight: bold; margin: 15px 0; }';
    echo '.total-row { background-color: #f2f2f2; font-weight: bold; }';
    echo '</style>';
    echo '</head>';
    echo '<body>';
    
    // Başlık
    echo '<div class="title">Toplu Maaş Hesaplama Raporu</div>';
    
    // Dönem bilgileri
    echo '<div class="subtitle">1. Dönem Bilgileri</div>';
    echo '<table>';
    echo '<tr><th width="25%">Dönem Başlangıç</th><td>' . date('d.m.Y', strtotime($startDate)) . '</td></tr>';
    echo '<tr><th>Dönem Bitiş</th><td>' . date('d.m.Y', strtotime($endDate)) . '</td></tr>';
    if (!empty($department)) {
        echo '<tr><th>Departman</th><td>' . $department . '</td></tr>';
    }
    echo '<tr><th>Toplam Personel</th><td>' . count($results) . '</td></tr>';
    echo '</table>';
    
    // Maaş özeti
    echo '<div class="subtitle">2. Maaş Özeti</div>';
    echo '<table>';
    echo '<tr><th width="25%">Toplam Normal Çalışma Ücreti</th><td>' . number_format($totalRegularSalary, 2, ',', '.') . ' ₺</td></tr>';
    echo '<tr><th>Toplam Fazla Mesai Ücreti</th><td>' . number_format($totalOvertimeSalary, 2, ',', '.') . ' ₺</td></tr>';
    echo '<tr class="total-row"><th>Toplam Maaş</th><td>' . number_format($totalSalary, 2, ',', '.') . ' ₺</td></tr>';
    echo '</table>';
    
    // Personel bazlı detaylar
    echo '<div class="subtitle">3. Personel Bazlı Detaylar</div>';
    echo '<table>';
    echo '<tr><th>Personel</th><th>Departman</th><th>Çalışma Günü</th><th>Çalışma Süresi</th><th>Fazla Mesai</th><th>Normal Ücret</th><th>Mesai Ücreti</th><th>Toplam Maaş</th></tr>';
    
    foreach ($results as $result) {
        echo '<tr>';
        echo '<td>' . $result['employee']['name'] . '</td>';
        echo '<td>' . ($result['employee']['department'] ?: '-') . '</td>';
        echo '<td>' . $result['period']['total_days'] . '</td>';
        echo '<td>' . $result['work_time']['total_hours'] . ' saat ' . $result['work_time']['total_minutes'] . ' dakika</td>';
        echo '<td>' . $result['work_time']['overtime_hours'] . ' saat ' . $result['work_time']['overtime_minutes'] . ' dakika</td>';
        echo '<td>' . number_format($result['salary']['regular_salary'], 2, ',', '.') . ' ₺</td>';
        echo '<td>' . number_format($result['salary']['overtime_salary'], 2, ',', '.') . ' ₺</td>';
        echo '<td>' . number_format($result['salary']['total_salary'], 2, ',', '.') . ' ₺</td>';
        echo '</tr>';
    }
    
    // Toplam satırı
    echo '<tr class="total-row">';
    echo '<td colspan="5" style="text-align: right;">TOPLAM</td>';
    echo '<td>' . number_format($totalRegularSalary, 2, ',', '.') . ' ₺</td>';
    echo '<td>' . number_format($totalOvertimeSalary, 2, ',', '.') . ' ₺</td>';
    echo '<td>' . number_format($totalSalary, 2, ',', '.') . ' ₺</td>';
    echo '</tr>';
    
    echo '</table>';
    
    // Altbilgi
    echo '<div style="margin-top: 20px; font-size: 10pt; text-align: center;">';
    echo 'Bu rapor PDKS (Personel Devam Kontrol Sistemi) tarafından otomatik olarak oluşturulmuştur. Oluşturulma Tarihi: ' . date('d.m.Y H:i:s');
    echo '</div>';
    
    echo '</body>';
    echo '</html>';
    
} catch(PDOException $e) {
    die("Veritabanı hatası: " . $e->getMessage());
}
?>