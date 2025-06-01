<?php
// Veritabanı bağlantısı
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "db_kartlar";
// Salary calculator dosyasını dahil et
require_once('salary_calculator.php');
// Parametreleri al
$userId = isset($_GET['user_id']) ? $_GET['user_id'] : null;
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');
if (!$userId) {
    die("Kullanıcı ID gereklidir.");
}
try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Maaş hesapla
    $result = calculateSalary($userId, $startDate, $endDate, $conn);
    if (!$result['success']) {
        die($result['message']);
    }
    // Dosya adı
    $filename = 'maas_raporu_' . $result['employee']['name'] . '_' . date('Y-m-d') . '.xls';
    // HTTP başlıkları
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    // Excel içeriği
    echo '<!DOCTYPE html>';
    echo '<html>';
    echo '<head>';
    echo '<meta charset="UTF-8">';
    echo '<title>Maaş Raporu</title>';
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
    echo '<div class="title">Maaş Hesaplama Raporu</div>';
    // Personel ve dönem bilgileri
    echo '<div class="subtitle">1. Personel ve Dönem Bilgileri</div>';
    echo '<table>';
    echo '<tr><th width="25%">Personel Adı</th><td>' . $result['employee']['name'] . '</td></tr>';
    echo '<tr><th>Departman</th><td>' . ($result['employee']['department'] ?: '-') . '</td></tr>';
    echo '<tr><th>Pozisyon</th><td>' . ($result['employee']['position'] ?: '-') . '</td></tr>';
    echo '<tr><th>Dönem Başlangıç</th><td>' . date('d.m.Y', strtotime($result['period']['start_date'])) . '</td></tr>';
    echo '<tr><th>Dönem Bitiş</th><td>' . date('d.m.Y', strtotime($result['period']['end_date'])) . '</td></tr>';
    echo '<tr><th>Toplam Çalışma Günü</th><td>' . $result['period']['total_days'] . '</td></tr>';
    echo '<tr><th>Takvim Günü</th><td>' . $result['period']['calendar_days'] . '</td></tr>';
    echo '</table>';
    // Çalışma ve maaş özeti
    echo '<div class="subtitle">2. Çalışma ve Maaş Özeti</div>';
    echo '<table>';
    echo '<tr><th width="25%">Normal Çalışma Süresi</th><td>' . $result['work_time']['total_hours'] . ' saat ' . $result['work_time']['total_minutes'] . ' dakika</td></tr>';
    echo '<tr><th>Fazla Mesai Süresi</th><td>' . $result['work_time']['overtime_hours'] . ' saat ' . $result['work_time']['overtime_minutes'] . ' dakika</td></tr>';
    echo '<tr><th>Normal Çalışma Ücreti</th><td>' . number_format($result['salary']['regular_salary'], 2, ',', '.') . ' ₺</td></tr>';
    echo '<tr><th>Fazla Mesai Ücreti</th><td>' . number_format($result['salary']['overtime_salary'], 2, ',', '.') . ' ₺</td></tr>';
    echo '<tr><th>Toplam Maaş (Çalışma)</th><td>' . number_format($result['salary']['total_salary'], 2, ',', '.') . ' ₺</td></tr>';
    echo '<tr class="total-row"><th>Aylık Biriken Maaş</th><td>' . number_format($result['salary']['accumulated_monthly_salary'], 2, ',', '.') . ' ₺</td></tr>';
    echo '</table>';
    // Maaş parametreleri
    echo '<div class="subtitle">3. Maaş Parametreleri</div>';
    echo '<table>';
    echo '<tr><th width="25%">Temel Maaş</th><td>' . number_format($result['salary']['base_salary'], 2, ',', '.') . ' ₺</td></tr>';
    echo '<tr><th>Saatlik Ücret</th><td>' . number_format($result['salary']['hourly_rate'], 2, ',', '.') . ' ₺</td></tr>';
    echo '<tr><th>Fazla Mesai Çarpanı</th><td>' . $result['salary']['overtime_rate'] . 'x</td></tr>';
    echo '<tr><th>Aylık Çalışma Günü</th><td>' . $result['salary']['monthly_work_days'] . ' gün</td></tr>';
    echo '</table>';
    // Günlük çalışma detayları
    echo '<div class="subtitle">4. Günlük Çalışma Detayları</div>';
    echo '<table>';
    echo '<tr><th>Tarih</th><th>Giriş</th><th>Çıkış</th><th>Normal Çalışma</th><th>Fazla Mesai</th><th>Toplam</th></tr>';
    foreach ($result['daily_details'] as $day) {
        echo '<tr>';
        echo '<td>' . date('d.m.Y', strtotime($day['date'])) . '</td>';
        echo '<td>' . $day['entry'] . '</td>';
        echo '<td>' . $day['exit'] . '</td>';
        echo '<td>' . $day['normal_hours'] . ' saat ' . $day['normal_minutes'] . ' dakika</td>';
        echo '<td>' . $day['overtime_hours'] . ' saat ' . $day['overtime_minutes'] . ' dakika</td>';
        echo '<td>' . $day['work_hours'] . ' saat ' . $day['work_minutes'] . ' dakika</td>';
        echo '</tr>';
    }
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