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

header('Content-Type: application/json');

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
    
    $results = [];
    $summary = [
        'total_employees' => 0,
        'meeting_requirement' => 0,
        'not_meeting_requirement' => 0,
        'total_gross_salary' => 0,
        'total_net_salary' => 0,
        'total_deductions' => 0
    ];
    
    foreach ($users as $user) {
        $calculation = calculateFixedSalary($user['user_id'], $start_date, $end_date, $conn);
        
        if ($calculation['success']) {
            $results[] = $calculation;
            $summary['total_employees']++;
            
            if ($calculation['salary']['meets_minimum_requirement']) {
                $summary['meeting_requirement']++;
            } else {
                $summary['not_meeting_requirement']++;
            }
            
            $summary['total_gross_salary'] += $calculation['salary']['fixed_salary'];
            $summary['total_net_salary'] += $calculation['salary']['net_salary'];
            $summary['total_deductions'] += $calculation['salary']['deduction_amount'];
        }
    }
    
    echo json_encode([
        'success' => true,
        'results' => $results,
        'summary' => $summary,
        'period' => [
            'start_date' => $start_date,
            'end_date' => $end_date,
            'department' => $department
        ]
    ]);
    
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Veritabanı hatası: ' . $e->getMessage()]);
}
?>