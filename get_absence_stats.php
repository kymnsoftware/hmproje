<?php
// Veritabanı bağlantısı
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "db_kartlar";

header('Content-Type: application/json');

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $currentMonth = date('Y-m');
    $monthStart = $currentMonth . '-01';
    $monthEnd = date('Y-m-t', strtotime($monthStart));
    
    // Bu ayki toplam devamsızlık
    $stmt = $conn->prepare("
        SELECT COUNT(*) as total_absences,
               SUM(total_days) as total_days,
               SUM(CASE WHEN is_justified = 1 THEN total_days ELSE 0 END) as justified_days,
               SUM(CASE WHEN is_justified = 0 THEN total_days ELSE 0 END) as unjustified_days
        FROM absences
        WHERE start_date >= :month_start AND end_date <= :month_end
    ");
    $stmt->bindParam(':month_start', $monthStart);
    $stmt->bindParam(':month_end', $monthEnd);
    $stmt->execute();
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // En çok devamsızlık yapan departman
    $stmt = $conn->prepare("
        SELECT c.department, COUNT(a.id) as absence_count
        FROM absences a
        JOIN cards c ON a.user_id = c.user_id
        WHERE a.start_date >= :month_start AND a.end_date <= :month_end
        GROUP BY c.department
        ORDER BY absence_count DESC
        LIMIT 1
    ");
    $stmt->bindParam(':month_start', $monthStart);
    $stmt->bindParam(':month_end', $monthEnd);
    $stmt->execute();
    $topDept = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $html = '<div class="row">';
    $html .= '<div class="col-md-6 mb-3">';
    $html .= '<div class="text-center p-3" style="background-color: #f8f9fa; border-radius: 5px;">';
    $html .= '<h4 class="text-danger">'.($stats['total_absences'] ?: 0).'</h4>';
    $html .= '<small>Bu Ayki Toplam Devamsızlık</small>';
    $html .= '</div>';
    $html .= '</div>';
    
    $html .= '<div class="col-md-6 mb-3">';
    $html .= '<div class="text-center p-3" style="background-color: #f8f9fa; border-radius: 5px;">';
    $html .= '<h4 class="text-warning">'.($stats['total_days'] ?: 0).'</h4>';
    $html .= '<small>Toplam Devamsızlık Günü</small>';
    $html .= '</div>';
    $html .= '</div>';
    
    $html .= '<div class="col-md-6 mb-3">';
    $html .= '<div class="text-center p-3" style="background-color: #f8f9fa; border-radius: 5px;">';
    $html .= '<h4 class="text-success">'.($stats['justified_days'] ?: 0).'</h4>';
    $html .= '<small>Mazeretli Gün</small>';
    $html .= '</div>';
    $html .= '</div>';
    
    $html .= '<div class="col-md-6 mb-3">';
    $html .= '<div class="text-center p-3" style="background-color: #f8f9fa; border-radius: 5px;">';
    $html .= '<h4 class="text-danger">'.($stats['unjustified_days'] ?: 0).'</h4>';
    $html .= '<small>Mazeretsiz Gün</small>';
    $html .= '</div>';
    $html .= '</div>';
    
    if ($topDept) {
        $html .= '<div class="col-md-12">';
        $html .= '<div class="alert alert-info">';
        $html .= '<strong>En Çok Devamsızlık:</strong> '.$topDept['department'].' ('.$topDept['absence_count'].' kayıt)';
        $html .= '</div>';
        $html .= '</div>';
    }
    
    $html .= '</div>';
    
    echo json_encode(['success' => true, 'html' => $html]);
    
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Veritabanı hatası: ' . $e->getMessage()]);
}
?>