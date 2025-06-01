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
    
    // PDF içeriği için HTML
    $html = '<!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Toplu Maaş Raporu</title>
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
                <div class="title">Toplu Maaş Hesaplama Raporu</div>
                <div class="subtitle">'.date('d.m.Y', strtotime($startDate)).' - '.date('d.m.Y', strtotime($endDate)).'</div>
            </div>
            
            <div class="section-title">1. Dönem Bilgileri</div>
            <table>
                <tr><th width="25%">Dönem Başlangıç</th><td>'.date('d.m.Y', strtotime($startDate)).'</td></tr>
                <tr><th>Dönem Bitiş</th><td>'.date('d.m.Y', strtotime($endDate)).'</td></tr>';
    if (!empty($department)) {
        $html .= '<tr><th>Departman</th><td>'.$department.'</td></tr>';
    }
    $html .= '<tr><th>Toplam Personel</th><td>'.count($results).'</td></tr>
            </table>
            
            <div class="section-title">2. Maaş Özeti</div>
            <table>
                <tr><th width="25%">Toplam Normal Çalışma Ücreti</th><td>'.number_format($totalRegularSalary, 2, ',', '.').' ₺</td></tr>
                <tr><th>Toplam Fazla Mesai Ücreti</th><td>'.number_format($totalOvertimeSalary, 2, ',', '.').' ₺</td></tr>
                <tr class="total-row"><th>Toplam Maaş</th><td>'.number_format($totalSalary, 2, ',', '.').' ₺</td></tr>
            </table>
            
            <div class="section-title">3. Personel Bazlı Detaylar</div>
            <table>
                <tr>
                    <th>Personel</th>
                    <th>Departman</th>
                    <th>Çalışma Günü</th>
                    <th>Çalışma Süresi</th>
                    <th>Fazla Mesai</th>
                    <th>Normal Ücret</th>
                    <th>Mesai Ücreti</th>
                    <th>Toplam Maaş</th>
                </tr>';
    
    foreach ($results as $result) {
        $html .= '<tr>
                    <td>'.$result['employee']['name'].'</td>
                    <td>'.($result['employee']['department'] ?: '-').'</td>
                    <td>'.$result['period']['total_days'].'</td>
                    <td>'.$result['work_time']['total_hours'].' saat '.$result['work_time']['total_minutes'].' dakika</td>
                    <td>'.$result['work_time']['overtime_hours'].' saat '.$result['work_time']['overtime_minutes'].' dakika</td>
                    <td>'.number_format($result['salary']['regular_salary'], 2, ',', '.').' ₺</td>
                    <td>'.number_format($result['salary']['overtime_salary'], 2, ',', '.').' ₺</td>
                    <td>'.number_format($result['salary']['total_salary'], 2, ',', '.').' ₺</td>
                </tr>';
    }
    
    // Toplam satırı
    $html .= '<tr class="total-row">
                <td colspan="5" style="text-align: right;">TOPLAM</td>
                <td>'.number_format($totalRegularSalary, 2, ',', '.').' ₺</td>
                <td>'.number_format($totalOvertimeSalary, 2, ',', '.').' ₺</td>
                <td>'.number_format($totalSalary, 2, ',', '.').' ₺</td>
            </tr>';
    
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
    header('Content-Disposition: attachment; filename="toplu_maas_raporu_'.date('Y-m-d').'.html"');
    
    // HTML içeriğini çıktıla
    echo $html;
    
} catch(PDOException $e) {
    die("Veritabanı hatası: " . $e->getMessage());
}
?>