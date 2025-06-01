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
    
    $today = date('Y-m-d');
    
    // Detaylı devamsızlık analizi
    function getDetailedAbsenceAnalysis($conn, $date) {
        // 1. Tüm aktif personelleri al
        $stmt = $conn->prepare("
            SELECT c.user_id, c.name, c.surname, c.department, c.position, c.card_number
            FROM cards c
            WHERE c.enabled = 'true'
            ORDER BY c.name, c.surname
        ");
        $stmt->execute();
        $allEmployees = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // 2. Bugün giriş yapan personelleri al
        $stmt = $conn->prepare("
            SELECT DISTINCT c.user_id
            FROM attendance_logs al
            JOIN cards c ON al.card_number = c.card_number
            WHERE DATE(al.event_time) = :date
            AND al.event_type = 'ENTRY'
            AND c.enabled = 'true'
        ");
        $stmt->bindParam(':date', $date);
        $stmt->execute();
        $presentEmployees = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
        
        // 3. Bugün izinli olan personelleri al (onaylanmış izinler)
        $stmt = $conn->prepare("
            SELECT DISTINCT lr.user_id, c.name, c.surname, c.department, c.position,
                   lt.name as leave_type_name, lt.color
            FROM leave_requests lr
            JOIN cards c ON lr.user_id = c.user_id
            JOIN leave_types lt ON lr.leave_type_id = lt.id
            WHERE lr.status = 'approved'
            AND :date BETWEEN lr.start_date AND lr.end_date
            AND c.enabled = 'true'
        ");
        $stmt->bindParam(':date', $date);
        $stmt->execute();
        $onLeaveEmployees = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $onLeaveUserIds = array_column($onLeaveEmployees, 'user_id');
        
        // 4. Bugün için kayıtlı devamsızlığı olan personelleri al
        $stmt = $conn->prepare("
            SELECT DISTINCT a.user_id
            FROM absences a
            WHERE :date BETWEEN a.start_date AND a.end_date
        ");
        $stmt->bindParam(':date', $date);
        $stmt->execute();
        $recordedAbsentUserIds = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
        
        // 5. Analiz sonuçlarını kategorize et
        $analysis = [
            'present' => 0,
            'on_leave' => $onLeaveEmployees,
            'absent_unrecorded' => [],
            'absent_recorded' => 0
        ];
        
        foreach ($allEmployees as $employee) {
            $userId = $employee['user_id'];
            
            if (in_array($userId, $presentEmployees)) {
                $analysis['present']++;
            } elseif (in_array($userId, $onLeaveUserIds)) {
                // İzinli (zaten $analysis['on_leave'] de var)
                continue;
            } elseif (in_array($userId, $recordedAbsentUserIds)) {
                $analysis['absent_recorded']++;
            } else {
                // Giriş yapmamış ve hiçbir kaydı yok
                $analysis['absent_unrecorded'][] = $employee;
            }
        }
        
        return $analysis;
    }
    
    $analysis = getDetailedAbsenceAnalysis($conn, $today);
    
    $html = '';
    
    // Özet bilgiler
    $html .= '<div class="row mb-3">';
    $html .= '<div class="col-6"><div class="alert alert-success mb-2">Giriş Yapan: <strong>'.$analysis['present'].'</strong></div></div>';
    $html .= '<div class="col-6"><div class="alert alert-info mb-2">İzinli: <strong>'.count($analysis['on_leave']).'</strong></div></div>';
    $html .= '<div class="col-6"><div class="alert alert-warning mb-2">Kayıtlı Devamsız: <strong>'.$analysis['absent_recorded'].'</strong></div></div>';
    $html .= '<div class="col-6"><div class="alert alert-danger mb-2">Kayıtsız Devamsız: <strong>'.count($analysis['absent_unrecorded']).'</strong></div></div>';
    $html .= '</div>';
    
    // Kayıtsız devamsızlar
    if (count($analysis['absent_unrecorded']) > 0) {
        $html .= '<h6 class="text-danger"><i class="fas fa-exclamation-triangle mr-1"></i> Kayıtsız Devamsızlar:</h6>';
        $displayCount = 0;
        foreach ($analysis['absent_unrecorded'] as $employee) {
            if ($displayCount >= 5) break; // Sadece ilk 5'ini göster
            $html .= '<div class="mb-2 p-2" style="background-color: #f8d7da; border-left: 4px solid #dc3545; border-radius: 3px;">';
            $html .= '<strong>'.$employee['name'].' '.$employee['surname'].'</strong><br>';
            $html .= '<small class="text-muted">'.$employee['department'].' - '.$employee['position'].'</small>';
            $html .= '</div>';
            $displayCount++;
        }
        
        if (count($analysis['absent_unrecorded']) > 5) {
            $html .= '<div class="text-center mt-2">';
            $html .= '<small class="text-muted">+'.(count($analysis['absent_unrecorded']) - 5).' kişi daha...</small>';
            $html .= '</div>';
        }
    }
    
    // İzinli olanlar
    if (count($analysis['on_leave']) > 0) {
        $html .= '<h6 class="text-info mt-3"><i class="fas fa-calendar-alt mr-1"></i> İzinli Personel:</h6>';
        $displayCount = 0;
        foreach ($analysis['on_leave'] as $employee) {
            if ($displayCount >= 3) break; // Sadece ilk 3'ünü göster
            $html .= '<div class="mb-2 p-2" style="background-color: #d1ecf1; border-left: 4px solid #17a2b8; border-radius: 3px;">';
            $html .= '<strong>'.$employee['name'].' '.$employee['surname'].'</strong><br>';
            $html .= '<small class="text-muted">'.$employee['department'].'</small><br>';
            $html .= '<span class="badge mt-1" style="background-color: '.$employee['color'].'; color: white; font-size: 10px;">';
            $html .= $employee['leave_type_name'];
            $html .= '</span>';
            $html .= '</div>';
            $displayCount++;
        }
        
        if (count($analysis['on_leave']) > 3) {
            $html .= '<div class="text-center mt-2">';
            $html .= '<small class="text-muted">+'.(count($analysis['on_leave']) - 3).' kişi daha...</small>';
            $html .= '</div>';
        }
    }
    
    // Detay sayfası linki
    $html .= '<div class="text-center mt-3">';
    $html .= '<a href="attendance_tracking.php" class="btn btn-outline-primary btn-sm">Detaylı Görünüm</a>';
    $html .= '</div>';
    
    $totalAbsent = count($analysis['absent_unrecorded']) + $analysis['absent_recorded'];
    
    echo json_encode([
        'success' => true, 
        'html' => $html, 
        'count' => $totalAbsent,
        'details' => [
            'present' => $analysis['present'],
            'on_leave' => count($analysis['on_leave']),
            'absent_unrecorded' => count($analysis['absent_unrecorded']),
            'absent_recorded' => $analysis['absent_recorded']
        ]
    ]);
    
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Veritabanı hatası: ' . $e->getMessage()]);
}
?>