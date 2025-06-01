<?php
// Veritabanı bağlantısı
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "db_kartlar";

// Hesaplama için gerekli parametreler
function calculateFixedSalary($userId, $startDate, $endDate, $conn) {
    // Çalışan bilgilerini al
    $stmt = $conn->prepare("SELECT * FROM cards WHERE user_id = :user_id");
    $stmt->bindParam(':user_id', $userId);
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        return [
            'success' => false,
            'message' => 'Kullanıcı bulunamadı.'
        ];
    }
    
    $employee = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Sabit maaş ve sistem ayarlarını al
    $fixedSalary = floatval($employee['fixed_salary']) ?: 35000; // Varsayılan 35.000 TL
    
    // Sistem ayarlarını al
    $settings = getSystemSettings($conn);
    $minimumWorkDays = $settings['minimum_work_days'] ?? 20;
    $minimumWorkRate = $settings['minimum_work_rate'] ?? 90; // %90
    $excludeWeekends = $settings['exclude_weekends'] ?? true;
    $excludeHolidays = $settings['exclude_holidays'] ?? true;
    
    // Tarih aralığı bilgisi
    $periodStartDate = new DateTime($startDate);
    $periodEndDate = new DateTime($endDate);
    
    // Ay başı ve ay sonu tarihlerini belirle
    $monthStart = new DateTime($periodStartDate->format('Y-m-01'));
    $monthEnd = new DateTime($periodEndDate->format('Y-m-t'));
    
    // Tam ay çalışıyor mu kontrolü
    $isFullMonth = ($periodStartDate->format('Y-m-d') === $monthStart->format('Y-m-d') &&
                    $periodEndDate->format('Y-m-d') === $monthEnd->format('Y-m-d'));
    
    // İş günlerini hesapla (resmi tatiller ve hafta sonları hariç)
    $totalWorkDays = calculateWorkDays($monthStart, $monthEnd, $conn, $excludeWeekends, $excludeHolidays);
    
    // Minimum çalışma şartını hesapla
    if ($settings['minimum_type'] === 'percentage') {
        $requiredWorkDays = ceil($totalWorkDays * ($minimumWorkRate / 100));
    } else {
        $requiredWorkDays = $minimumWorkDays;
    }
    
    // Çalışılan günleri al
    $workedDays = getWorkedDays($userId, $startDate, $endDate, $conn);
    
    // İzinli günleri al (onaylanmış izinler)
    $approvedLeaveDays = getApprovedLeaveDays($userId, $startDate, $endDate, $conn);
    
    // Toplam devam edilen gün = Çalışılan gün + İzinli gün
    $totalAttendedDays = $workedDays['total_days'] + $approvedLeaveDays;
    
    // Maaş hesaplaması
    $deductionAmount = 0;
    $netSalary = $fixedSalary;
    
    if ($totalAttendedDays < $requiredWorkDays) {
        $missingDays = $requiredWorkDays - $totalAttendedDays;
        $dailyDeduction = $fixedSalary / $totalWorkDays;
        $deductionAmount = $missingDays * $dailyDeduction;
        $netSalary = $fixedSalary - $deductionAmount;
    }
    
    // Devamsızlık detaylarını al
    $absenceDetails = getAbsenceDetails($userId, $startDate, $endDate, $conn);
    
    return [
        'success' => true,
        'employee' => [
            'id' => $employee['user_id'],
            'name' => $employee['name'] . ' ' . $employee['surname'],
            'department' => $employee['department'],
            'position' => $employee['position']
        ],
        'period' => [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'is_full_month' => $isFullMonth,
            'total_work_days' => $totalWorkDays,
            'required_work_days' => $requiredWorkDays
        ],
        'attendance' => [
            'worked_days' => $workedDays['total_days'],
            'approved_leave_days' => $approvedLeaveDays,
            'total_attended_days' => $totalAttendedDays,
            'missing_days' => max(0, $requiredWorkDays - $totalAttendedDays),
            'attendance_rate' => round(($totalAttendedDays / $totalWorkDays) * 100, 2)
        ],
        'salary' => [
            'fixed_salary' => $fixedSalary,
            'daily_deduction_rate' => $fixedSalary / $totalWorkDays,
            'deduction_amount' => round($deductionAmount, 2),
            'net_salary' => round($netSalary, 2),
            'meets_minimum_requirement' => $totalAttendedDays >= $requiredWorkDays
        ],
        'details' => [
            'work_details' => $workedDays['details'],
            'leave_details' => $approvedLeaveDays > 0 ? getLeaveDetails($userId, $startDate, $endDate, $conn) : [],
            'absence_details' => $absenceDetails
        ]
    ];
}

// İş günlerini hesapla (hafta sonları ve tatiller hariç)
function calculateWorkDays($startDate, $endDate, $conn, $excludeWeekends = true, $excludeHolidays = true) {
    $workDays = 0;
    $current = clone $startDate;
    
    // Resmi tatilleri al
    $holidays = [];
    if ($excludeHolidays) {
        $holidays = getHolidays($conn, $startDate->format('Y'));
    }
    
    while ($current <= $endDate) {
        $isWorkDay = true;
        
        // Hafta sonu kontrolü
        if ($excludeWeekends && in_array($current->format('w'), [0, 6])) { // 0=Pazar, 6=Cumartesi
            $isWorkDay = false;
        }
        
        // Resmi tatil kontrolü
        if ($excludeHolidays && in_array($current->format('Y-m-d'), $holidays)) {
            $isWorkDay = false;
        }
        
        if ($isWorkDay) {
            $workDays++;
        }
        
        $current->modify('+1 day');
    }
    
    return $workDays;
}

// Çalışılan günleri al
function getWorkedDays($userId, $startDate, $endDate, $conn) {
    $stmt = $conn->prepare("
        SELECT DATE(al.event_time) as work_date,
               MIN(CASE WHEN al.event_type = 'ENTRY' THEN al.event_time END) as first_entry,
               MAX(CASE WHEN al.event_type = 'EXIT' THEN al.event_time END) as last_exit,
               COUNT(DISTINCT DATE(al.event_time)) as total_days
        FROM attendance_logs al
        JOIN cards c ON al.card_number = c.card_number
        WHERE c.user_id = :user_id
        AND DATE(al.event_time) BETWEEN :start_date AND :end_date
        AND al.event_type IN ('ENTRY', 'EXIT')
        GROUP BY DATE(al.event_time)
        ORDER BY DATE(al.event_time)
    ");
    
    $stmt->bindParam(':user_id', $userId);
    $stmt->bindParam(':start_date', $startDate);
    $stmt->bindParam(':end_date', $endDate);
    $stmt->execute();
    
    $details = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Sadece giriş yapılan günleri say
    $stmt = $conn->prepare("
        SELECT COUNT(DISTINCT DATE(al.event_time)) as total_days
        FROM attendance_logs al
        JOIN cards c ON al.card_number = c.card_number
        WHERE c.user_id = :user_id
        AND DATE(al.event_time) BETWEEN :start_date AND :end_date
        AND al.event_type = 'ENTRY'
    ");
    
    $stmt->bindParam(':user_id', $userId);
    $stmt->bindParam(':start_date', $startDate);
    $stmt->bindParam(':end_date', $endDate);
    $stmt->execute();
    
    $totalDays = $stmt->fetchColumn() ?: 0;
    
    return [
        'total_days' => $totalDays,
        'details' => $details
    ];
}

// Onaylanmış izin günlerini al
function getApprovedLeaveDays($userId, $startDate, $endDate, $conn) {
    $stmt = $conn->prepare("
        SELECT SUM(
            CASE 
                WHEN lr.start_date < :start_date AND lr.end_date > :end_date THEN
                    DATEDIFF(:end_date, :start_date) + 1
                WHEN lr.start_date < :start_date THEN
                    DATEDIFF(lr.end_date, :start_date) + 1
                WHEN lr.end_date > :end_date THEN
                    DATEDIFF(:end_date, lr.start_date) + 1
                ELSE
                    lr.total_days
            END
        ) as total_leave_days
        FROM leave_requests lr
        WHERE lr.user_id = :user_id
        AND lr.status = 'approved'
        AND (
            (lr.start_date BETWEEN :start_date AND :end_date) OR
            (lr.end_date BETWEEN :start_date AND :end_date) OR
            (lr.start_date <= :start_date AND lr.end_date >= :end_date)
        )
    ");
    
    $stmt->bindParam(':user_id', $userId);
    $stmt->bindParam(':start_date', $startDate);
    $stmt->bindParam(':end_date', $endDate);
    $stmt->execute();
    
    return floatval($stmt->fetchColumn()) ?: 0;
}

// İzin detaylarını al
function getLeaveDetails($userId, $startDate, $endDate, $conn) {
    $stmt = $conn->prepare("
        SELECT lr.start_date, lr.end_date, lr.total_days, lr.reason,
               lt.name as leave_type_name, lt.color
        FROM leave_requests lr
        JOIN leave_types lt ON lr.leave_type_id = lt.id
        WHERE lr.user_id = :user_id
        AND lr.status = 'approved'
        AND (
            (lr.start_date BETWEEN :start_date AND :end_date) OR
            (lr.end_date BETWEEN :start_date AND :end_date) OR
            (lr.start_date <= :start_date AND lr.end_date >= :end_date)
        )
        ORDER BY lr.start_date
    ");
    
    $stmt->bindParam(':user_id', $userId);
    $stmt->bindParam(':start_date', $startDate);
    $stmt->bindParam(':end_date', $endDate);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Devamsızlık detaylarını al
function getAbsenceDetails($userId, $startDate, $endDate, $conn) {
    $stmt = $conn->prepare("
        SELECT a.start_date, a.end_date, a.total_days, a.reason, a.is_justified,
               at.name as absence_type_name, at.color
        FROM absences a
        JOIN absence_types at ON a.absence_type_id = at.id
        WHERE a.user_id = :user_id
        AND (
            (a.start_date BETWEEN :start_date AND :end_date) OR
            (a.end_date BETWEEN :start_date AND :end_date) OR
            (a.start_date <= :start_date AND a.end_date >= :end_date)
        )
        ORDER BY a.start_date
    ");
    
    $stmt->bindParam(':user_id', $userId);
    $stmt->bindParam(':start_date', $startDate);
    $stmt->bindParam(':end_date', $endDate);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Resmi tatilleri al
function getHolidays($conn, $year) {
    $stmt = $conn->prepare("
        SELECT holiday_date 
        FROM holidays 
        WHERE YEAR(holiday_date) = :year
        ORDER BY holiday_date
    ");
    $stmt->bindParam(':year', $year);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

// Sistem ayarlarını al
function getSystemSettings($conn) {
    $stmt = $conn->prepare("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'salary_%'");
    $stmt->execute();
    $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    return [
        'minimum_work_days' => intval($settings['salary_minimum_work_days'] ?? 20),
        'minimum_work_rate' => intval($settings['salary_minimum_work_rate'] ?? 90),
        'minimum_type' => $settings['salary_minimum_type'] ?? 'percentage', // 'percentage' or 'days'
        'exclude_weekends' => ($settings['salary_exclude_weekends'] ?? 'true') === 'true',
        'exclude_holidays' => ($settings['salary_exclude_holidays'] ?? 'true') === 'true'
    ];
}

// API isteği için
if (isset($_GET['action']) && $_GET['action'] == 'calculate') {
    header('Content-Type: application/json');
    
    $userId = isset($_GET['user_id']) ? $_GET['user_id'] : null;
    $startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
    $endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');
    
    if (!$userId) {
        echo json_encode(['success' => false, 'message' => 'Kullanıcı ID eksik!']);
        exit;
    }
    
    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $result = calculateFixedSalary($userId, $startDate, $endDate, $conn);
        echo json_encode($result);
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Veritabanı hatası: ' . $e->getMessage()]);
    }
}
?>