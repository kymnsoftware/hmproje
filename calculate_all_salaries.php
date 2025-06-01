<?php
// Veritabanı bağlantısı
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "db_kartlar";

// Salary calculator dosyasını dahil et
require_once('salary_calculator.php');

// Dönüş değerlerini JSON olarak ayarla
header('Content-Type: application/json');

// Parametreleri al
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01'); // Ayın ilk günü
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t'); // Ayın son günü
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
    
    echo json_encode([
        'success' => true,
        'period' => [
            'start_date' => $startDate,
            'end_date' => $endDate
        ],
        'employees' => count($results),
        'totals' => [
            'regular_salary' => round($totalRegularSalary, 2),
            'overtime_salary' => round($totalOvertimeSalary, 2),
            'total_salary' => round($totalSalary, 2)
        ],
        'results' => $results
    ]);
    
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Veritabanı hatası: ' . $e->getMessage()]);
}
?>