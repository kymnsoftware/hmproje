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
    // PDF içeriği için HTML
    $html = '<!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Maaş Raporu</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; }
            .container { width: 100%; }
            .header { text-align: center; margin-bottom: 20px; }
            .title { font-size: 24px; font-weight: bold; margin-bottom: 5px; }
            .subtitle { font-size: 18px; margin-bottom: 20px; }
            table { width: 100%; border-collapse: collapse; margin: 15px 0; }
            th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
            th { background-color: #f2f2f2; }
            .section-title { font-size: 16px; font-weight: bold; margin: 20px 0 10px 0; }
            .total-row { font-weight: bold; background-color: #f2f2f2; }
            .footer { text-align: center; font-size: 12px; margin-top: 30px; color: #777; }
            @media print {
                body { -webkit-print-color-adjust: exact; }
                .no-print { display: none; }
            }
        </style>
    </head>
    <body>
        <div class="no-print" style="text-align: center; margin: 20px;">
            <button onclick="window.print()">Yazdır</button>
            <button onclick="window.close()">Kapat</button>
        </div>
        <div class="container">
            <div class="header">
                <div class="title">Maaş Hesaplama Raporu</div>
                <div class="subtitle">'.date('d.m.Y', strtotime($result['period']['start_date'])).' - '.date('d.m.Y', strtotime($result['period']['end_date'])).'</div>
            </div>
            
            <div class="section-title">1. Personel ve Dönem Bilgileri</div>
            <table>
                <tr><th width="25%">Personel Adı</th><td>'.$result['employee']['name'].'</td></tr>
                <tr><th>Departman</th><td>'.($result['employee']['department'] ?: '-').'</td></tr>
                <tr><th>Pozisyon</th><td>'.($result['employee']['position'] ?: '-').'</td></tr>
                <tr><th>Dönem Başlangıç</th><td>'.date('d.m.Y', strtotime($result['period']['start_date'])).'</td></tr>
                <tr><th>Dönem Bitiş</th><td>'.date('d.m.Y', strtotime($result['period']['end_date'])).'</td></tr>
                <tr><th>Toplam Çalışma Günü</th><td>'.$result['period']['total_days'].'</td></tr>
                <tr><th>Takvim Günü</th><td>'.$result['period']['calendar_days'].'</td></tr>
            </table>
            
            <div class="section-title">2. Çalışma ve Maaş Özeti</div>
            <table>
                <tr><th width="25%">Normal Çalışma Süresi</th><td>'.$result['work_time']['total_hours'].' saat '.$result['work_time']['total_minutes'].' dakika</td></tr>
                <tr><th>Fazla Mesai Süresi</th><td>'.$result['work_time']['overtime_hours'].' saat '.$result['work_time']['overtime_minutes'].' dakika</td></tr>
                <tr><th>Normal Çalışma Ücreti</th><td>'.number_format($result['salary']['regular_salary'], 2, ',', '.').' ₺</td></tr>
                <tr><th>Fazla Mesai Ücreti</th><td>'.number_format($result['salary']['overtime_salary'], 2, ',', '.').' ₺</td></tr>
                <tr><th>Toplam Maaş (Çalışma)</th><td>'.number_format($result['salary']['total_salary'], 2, ',', '.').' ₺</td></tr>
                <tr class="total-row"><th>Aylık Biriken Maaş</th><td>'.number_format($result['salary']['accumulated_monthly_salary'], 2, ',', '.').' ₺</td></tr>
            </table>
            
            <div class="section-title">3. Maaş Parametreleri</div>
            <table>
                <tr><th width="25%">Temel Maaş</th><td>'.number_format($result['salary']['base_salary'], 2, ',', '.').' ₺</td></tr>
                <tr><th>Saatlik Ücret</th><td>'.number_format($result['salary']['hourly_rate'], 2, ',', '.').' ₺</td></tr>
                <tr><th>Fazla Mesai Çarpanı</th><td>'.$result['salary']['overtime_rate'].'x</td></tr>
                <tr><th>Aylık Çalışma Günü</th><td>'.$result['salary']['monthly_work_days'].' gün</td></tr>
            </table>
            
            <div class="section-title">4. Günlük Çalışma Detayları</div>
            <table>
                <tr>
                    <th>Tarih</th>
                    <th>Giriş</th>
                    <th>Çıkış</th>
                    <th>Normal Çalışma</th>
                    <th>Fazla Mesai</th>
                    <th>Toplam</th>
                </tr>';
                
    foreach ($result['daily_details'] as $day) {
        $html .= '<tr>
                    <td>'.date('d.m.Y', strtotime($day['date'])).'</td>
                    <td>'.$day['entry'].'</td>
                    <td>'.$day['exit'].'</td>
                    <td>'.$day['normal_hours'].' saat '.$day['normal_minutes'].' dakika</td>
                    <td>'.$day['overtime_hours'].' saat '.$day['overtime_minutes'].' dakika</td>
                    <td>'.$day['work_hours'].' saat '.$day['work_minutes'].' dakika</td>
                </tr>';
    }
                
    $html .= '</table>
            
            <div class="footer">
                Bu rapor PDKS (Personel Devam Kontrol Sistemi) tarafından otomatik olarak oluşturulmuştur.<br>
                Oluşturulma Tarihi: '.date('d.m.Y H:i:s').'
            </div>
        </div>
    </body>
    </html>';
    
    // HTTP başlıkları
    header('Content-Type: text/html; charset=utf-8');
    header('Content-Disposition: attachment; filename="maas_raporu_'.$result['employee']['name'].'_'.date('Y-m-d').'.html"');
    
    // HTML içeriğini çıktıla
    echo $html;
    
} catch(PDOException $e) {
    die("Veritabanı hatası: " . $e->getMessage());
}
?>