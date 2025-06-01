<?php
session_start();
// Oturum ve yetki kontrolü
if (!isset($_SESSION['user_id']) || $_SESSION['privilege'] < 1) {
    header('Location: login.php');
    exit;
}

// Veritabanı bağlantısı
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "db_kartlar";

$success_message = '';
$error_message = '';

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Devamsızlık türlerini al
    $stmt = $conn->query("SELECT * FROM absence_types ORDER BY name");
    $absence_types = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Kullanıcıları al
    $stmt = $conn->query("SELECT user_id, name, surname, department FROM cards WHERE enabled = 'true' ORDER BY name, surname");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Devamsızlık ekleme
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_absence'])) {
        $user_id = $_POST['user_id'] ?? '';
        $absence_type_id = $_POST['absence_type_id'] ?? '';
        $start_date = $_POST['start_date'] ?? '';
        $end_date = $_POST['end_date'] ?? '';
        $reason = $_POST['reason'] ?? '';
        $is_justified = isset($_POST['is_justified']) ? 1 : 0;
        
        if (empty($user_id) || empty($absence_type_id) || empty($start_date) || empty($end_date)) {
            $error_message = 'Lütfen tüm gerekli alanları doldurun!';
        } else {
            // Toplam gün sayısını hesapla
            $start = new DateTime($start_date);
            $end = new DateTime($end_date);
            $end->modify('+1 day');
            $interval = $start->diff($end);
            $total_days = $interval->days;
            
            // Devamsızlığı kaydet
            $stmt = $conn->prepare("INSERT INTO absences (user_id, absence_type_id, start_date, end_date, total_days, reason, is_justified, created_by)
                                   VALUES (:user_id, :absence_type_id, :start_date, :end_date, :total_days, :reason, :is_justified, :created_by)");
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':absence_type_id', $absence_type_id);
            $stmt->bindParam(':start_date', $start_date);
            $stmt->bindParam(':end_date', $end_date);
            $stmt->bindParam(':total_days', $total_days);
            $stmt->bindParam(':reason', $reason);
            $stmt->bindParam(':is_justified', $is_justified);
            $stmt->bindParam(':created_by', $_SESSION['user_id']);
            $stmt->execute();
            
            $success_message = 'Devamsızlık kaydı başarıyla eklendi.';
        }
    }
    
    // Otomatik devamsızlık taraması
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['auto_scan'])) {
        $scan_date = $_POST['scan_date'] ?? date('Y-m-d');
        
        // Otomatik devamsızlık taraması yap
        $autoResult = performAutoAbsenceScan($conn, $scan_date, $_SESSION['user_id']);
        if ($autoResult['success']) {
            $success_message = $autoResult['message'];
        } else {
            $error_message = $autoResult['message'];
        }
    }
    
    // Bugünkü detaylı devamsızlık analizi
    $today = date('Y-m-d');
    $todayAnalysis = getDetailedAbsenceAnalysis($conn, $today);
    
} catch(PDOException $e) {
    $error_message = 'Veritabanı hatası: ' . $e->getMessage();
}

// Detaylı devamsızlık analizi fonksiyonu
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
        SELECT DISTINCT c.user_id, c.card_number
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
        SELECT DISTINCT a.user_id, a.is_justified, at.name as absence_type_name
        FROM absences a
        JOIN absence_types at ON a.absence_type_id = at.id
        WHERE :date BETWEEN a.start_date AND a.end_date
    ");
    $stmt->bindParam(':date', $date);
    $stmt->execute();
    $recordedAbsences = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $recordedAbsentUserIds = array_column($recordedAbsences, 'user_id');
    
    // 5. Analiz sonuçlarını kategorize et
    $analysis = [
        'present' => [], // Giriş yapanlar
        'on_leave' => $onLeaveEmployees, // İzinliler
        'absent_unrecorded' => [], // Giriş yapmayan ve kaydı olmayan
        'absent_recorded' => [] // Devamsızlık kaydı olan
    ];
    
    foreach ($allEmployees as $employee) {
        $userId = $employee['user_id'];
        
        if (in_array($userId, $presentEmployees)) {
            // Giriş yapmış
            $analysis['present'][] = $employee;
        } elseif (in_array($userId, $onLeaveUserIds)) {
            // İzinli (zaten $analysis['on_leave'] de var)
            continue;
        } elseif (in_array($userId, $recordedAbsentUserIds)) {
            // Devamsızlık kaydı var
            $absenceInfo = array_filter($recordedAbsences, function($a) use ($userId) {
                return $a['user_id'] == $userId;
            });
            $absenceInfo = reset($absenceInfo);
            $employee['absence_info'] = $absenceInfo;
            $analysis['absent_recorded'][] = $employee;
        } else {
            // Giriş yapmamış ve hiçbir kaydı yok
            $analysis['absent_unrecorded'][] = $employee;
        }
    }
    
    return $analysis;
}


// Otomatik devamsızlık tarama fonksiyonu
function performAutoAbsenceScan($conn, $date, $createdBy) {
    try {
        $analysis = getDetailedAbsenceAnalysis($conn, $date);
        $processedCount = 0;
        
        // Kaydı olmayan devamsızları otomatik olarak ekle
        foreach ($analysis['absent_unrecorded'] as $employee) {
            // "Mazeretsiz" devamsızlık türünü al
            $stmt = $conn->prepare("SELECT id FROM absence_types WHERE name LIKE '%Mazeretsiz%' OR name LIKE '%Kayıtsız%' LIMIT 1");
            $stmt->execute();
            $absenceTypeId = $stmt->fetchColumn();
            
            if (!$absenceTypeId) {
                // Yoksa oluştur
                $stmt = $conn->prepare("INSERT INTO absence_types (name, description, color) VALUES ('Kayıtsız Devamsızlık', 'Sisteme kaydı olmayan devamsızlık', '#dc3545')");
                $stmt->execute();
                $absenceTypeId = $conn->lastInsertId();
            }
            
            // Devamsızlık kaydını ekle
            $stmt = $conn->prepare("
                INSERT INTO absences (user_id, absence_type_id, start_date, end_date, total_days, reason, is_justified, created_by, auto_generated)
                VALUES (:user_id, :absence_type_id, :date, :date, 1, 'Otomatik tespit: Giriş kaydı bulunamadı', 0, :created_by, 1)
            ");
            $stmt->bindParam(':user_id', $employee['user_id']);
            $stmt->bindParam(':absence_type_id', $absenceTypeId);
            $stmt->bindParam(':date', $date);
            $stmt->bindParam(':created_by', $createdBy);
            $stmt->execute();
            
            $processedCount++;
        }
        
        return [
            'success' => true,
            'message' => "$processedCount adet otomatik devamsızlık kaydı oluşturuldu."
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Otomatik tarama sırasında hata: ' . $e->getMessage()
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PDKS - Devamsızlık Takibi</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@5.15.4/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding-top: 20px;
            padding-bottom: 20px;
        }
        .container {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            padding: 20px;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        .user-info {
            display: flex;
            align-items: center;
        }
        .user-photo {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            margin-right: 15px;
            object-fit: cover;
        }
        .card {
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        .card-header {
            border-radius: 10px 10px 0 0 !important;
            font-weight: bold;
        }
        .absence-status {
            font-weight: bold;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
        }
        .status-justified {
            background-color: #2ecc71;
            color: white;
        }
        .status-unjustified {
            background-color: #e74c3c;
            color: white;
        }
        .employee-item {
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 5px;
            border-left: 4px solid;
        }
        .present-item {
            background-color: #d4edda;
            border-left-color: #28a745;
        }
        .on-leave-item {
            background-color: #d1ecf1;
            border-left-color: #17a2b8;
        }
        .absent-unrecorded-item {
            background-color: #f8d7da;
            border-left-color: #dc3545;
        }
        .absent-recorded-item {
            background-color: #fff3cd;
            border-left-color: #ffc107;
        }
        .stats-card {
            text-align: center;
            padding: 20px;
        }
        .stats-number {
            font-size: 2rem;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <h2><i class="fas fa-user-times mr-2"></i> Devamsızlık Takibi</h2>
            </div>
            <div class="user-info">
                <img src="<?php echo $_SESSION['photo_path']; ?>" class="user-photo" alt="Profil">
                <div>
                    <h5><?php echo $_SESSION['user_name']; ?></h5>
                    <p class="text-muted mb-0"><?php echo $_SESSION['department'] . ' - ' . $_SESSION['position']; ?></p>
                </div>
            </div>
            <div>
                <a href="index.php" class="btn btn-outline-primary mr-2">
                    <i class="fas fa-home mr-1"></i> Ana Sayfa
                </a>
                <a href="logout.php" class="btn btn-outline-secondary">
                    <i class="fas fa-sign-out-alt mr-1"></i> Çıkış Yap
                </a>
            </div>
        </div>

        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <!-- İstatistik Kartları -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-success text-white stats-card">
                    <div class="stats-number"><?php echo count($todayAnalysis['present']); ?></div>
                    <div>Giriş Yapanlar</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white stats-card">
                    <div class="stats-number"><?php echo count($todayAnalysis['on_leave']); ?></div>
                    <div>İzinli Olanlar</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-white stats-card">
                    <div class="stats-number"><?php echo count($todayAnalysis['absent_recorded']); ?></div>
                    <div>Kayıtlı Devamsız</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-danger text-white stats-card">
                    <div class="stats-number"><?php echo count($todayAnalysis['absent_unrecorded']); ?></div>
                    <div>Kayıtsız Devamsız</div>
                </div>
            </div>
        </div>

        <ul class="nav nav-tabs" id="absenceTab" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" id="today-analysis-tab" data-toggle="tab" href="#today-analysis" role="tab">
                    <i class="fas fa-chart-pie mr-1"></i> Bugünkü Durum Analizi
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="auto-scan-tab" data-toggle="tab" href="#auto-scan" role="tab">
                    <i class="fas fa-robot mr-1"></i> Otomatik Tarama
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="add-tab" data-toggle="tab" href="#add" role="tab">
                    <i class="fas fa-plus-circle mr-1"></i> Manuel Devamsızlık Ekle
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="history-tab" data-toggle="tab" href="#history" role="tab">
                    <i class="fas fa-history mr-1"></i> Devamsızlık Geçmişi
                </a>
            </li>
            <li class="nav-item">
                <a href="absence_reports_page.php" class="nav-link">
                    <i class="fas fa-history mr-1"></i> Detaylı Rapor Sayfasına Git
                </a>
            </li>
        </ul>

        <div class="tab-content" id="absenceTabContent">
            <!-- Bugünkü Durum Analizi -->
            <div class="tab-pane fade show active" id="today-analysis" role="tabpanel">
                <div class="card mt-3">
                    <div class="card-header bg-primary text-white">
                        <i class="fas fa-chart-pie mr-1"></i> Bugünkü Personel Durumu (<?php echo date('d.m.Y'); ?>)
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <!-- Giriş Yapanlar -->
                            <div class="col-md-6 mb-4">
                                <h5 class="text-success"><i class="fas fa-check-circle mr-1"></i> Giriş Yapanlar (<?php echo count($todayAnalysis['present']); ?>)</h5>
                                <div style="max-height: 300px; overflow-y: auto;">
                                    <?php if (count($todayAnalysis['present']) > 0): ?>
                                        <?php foreach ($todayAnalysis['present'] as $employee): ?>
                                            <div class="employee-item present-item">
                                                <strong><?php echo $employee['name'] . ' ' . $employee['surname']; ?></strong><br>
                                                <small><?php echo $employee['department'] . ' - ' . $employee['position']; ?></small>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="alert alert-info">Henüz kimse giriş yapmamış.</div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- İzinli Olanlar -->
                            <div class="col-md-6 mb-4">
                                <h5 class="text-info"><i class="fas fa-calendar-alt mr-1"></i> İzinli Olanlar (<?php echo count($todayAnalysis['on_leave']); ?>)</h5>
                                <div style="max-height: 300px; overflow-y: auto;">
                                    <?php if (count($todayAnalysis['on_leave']) > 0): ?>
                                        <?php foreach ($todayAnalysis['on_leave'] as $employee): ?>
                                            <div class="employee-item on-leave-item">
                                                <strong><?php echo $employee['name'] . ' ' . $employee['surname']; ?></strong><br>
                                                <small><?php echo $employee['department'] . ' - ' . $employee['position']; ?></small><br>
                                                <span class="badge mt-1" style="background-color: <?php echo $employee['color']; ?>; color: white;">
                                                    <?php echo $employee['leave_type_name']; ?>
                                                </span>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="alert alert-info">Bugün izinli kimse yok.</div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Kayıtsız Devamsızlar -->
                            <div class="col-md-6 mb-4">
                                <h5 class="text-danger">
                                    <i class="fas fa-exclamation-triangle mr-1"></i> 
                                    Kayıtsız Devamsızlar (<?php echo count($todayAnalysis['absent_unrecorded']); ?>)
                                </h5>
                                <div style="max-height: 300px; overflow-y: auto;">
                                    <?php if (count($todayAnalysis['absent_unrecorded']) > 0): ?>
                                        <?php foreach ($todayAnalysis['absent_unrecorded'] as $employee): ?>
                                            <div class="employee-item absent-unrecorded-item">
                                                <strong><?php echo $employee['name'] . ' ' . $employee['surname']; ?></strong><br>
                                                <small><?php echo $employee['department'] . ' - ' . $employee['position']; ?></small><br>
                                                <div class="mt-2">
                                                    <button type="button" class="btn btn-sm btn-success add-justified-absence"
                                                            data-user-id="<?php echo $employee['user_id']; ?>"
                                                            data-name="<?php echo $employee['name'] . ' ' . $employee['surname']; ?>">
                                                        <i class="fas fa-check mr-1"></i> Mazeretli
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-danger add-unjustified-absence"
                                                            data-user-id="<?php echo $employee['user_id']; ?>"
                                                            data-name="<?php echo $employee['name'] . ' ' . $employee['surname']; ?>">
                                                        <i class="fas fa-times mr-1"></i> Mazeretsiz
                                                    </button>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="alert alert-success">Tüm devamsızlıklar kayıt altında.</div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Kayıtlı Devamsızlar -->
                            <div class="col-md-6 mb-4">
                                <h5 class="text-warning">
                                    <i class="fas fa-clipboard-list mr-1"></i> 
                                    Kayıtlı Devamsızlar (<?php echo count($todayAnalysis['absent_recorded']); ?>)
                                </h5>
                                <div style="max-height: 300px; overflow-y: auto;">
                                    <?php if (count($todayAnalysis['absent_recorded']) > 0): ?>
                                        <?php foreach ($todayAnalysis['absent_recorded'] as $employee): ?>
                                            <div class="employee-item absent-recorded-item">
                                                <strong><?php echo $employee['name'] . ' ' . $employee['surname']; ?></strong><br>
                                                <small><?php echo $employee['department'] . ' - ' . $employee['position']; ?></small><br>
                                                <span class="absence-status <?php echo $employee['absence_info']['is_justified'] ? 'status-justified' : 'status-unjustified'; ?>">
                                                    <?php echo $employee['absence_info']['absence_type_name']; ?>
                                                    (<?php echo $employee['absence_info']['is_justified'] ? 'Mazeretli' : 'Mazeretsiz'; ?>)
                                                </span>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="alert alert-info">Kayıtlı devamsızlık yok.</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Otomatik Tarama -->
            <div class="tab-pane fade" id="auto-scan" role="tabpanel">
                <div class="card mt-3">
                    <div class="card-header bg-info text-white">
                        <i class="fas fa-robot mr-1"></i> Otomatik Devamsızlık Taraması
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <h5><i class="fas fa-info-circle mr-1"></i> Otomatik Tarama Nasıl Çalışır?</h5>
                            <ul>
                                <li>Sistem, seçilen tarihteki tüm aktif personelleri kontrol eder</li>
                                <li>Giriş-çıkış kayıtlarını ve izin durumlarını analiz eder</li>
                                <li>İzinli olmayan ancak giriş yapmayan personelleri tespit eder</li>
                                <li>Bu personeller için otomatik olarak "Kayıtsız Devamsızlık" kaydı oluşturur</li>
                                <li>Daha sonra bu kayıtları manuel olarak güncelleyebilirsiniz</li>
                            </ul>
                        </div>
                        
                        <form method="post" action="">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="scan_date">Tarama Tarihi</label>
                                        <input type="date" class="form-control" id="scan_date" name="scan_date" value="<?php echo date('Y-m-d'); ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6 d-flex align-items-end">
                                    <button type="submit" name="auto_scan" class="btn btn-info btn-lg">
                                        <i class="fas fa-search mr-1"></i> Otomatik Tarama Başlat
                                    </button>
                                </div>
                            </div>
                        </form>
                        
                        <div class="mt-4">
                            <h6>Son Tarama Sonuçları:</h6>
                            <div id="scan-results">
                                <!-- AJAX ile doldurulacak -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Manuel Devamsızlık Ekleme (önceki kod aynı) -->
            <div class="tab-pane fade" id="add" role="tabpanel">
                <div class="card mt-3">
                    <div class="card-header bg-primary text-white">
                        <i class="fas fa-plus-circle mr-1"></i> Manuel Devamsızlık Kaydı
                    </div>
                    <div class="card-body">
                        <form method="post" action="">
                            <div class="row">
                                <div class="form-group col-md-6">
                                    <label for="user_id">Personel</label>
                                    <select class="form-control" id="user_id" name="user_id" required>
                                        <option value="">Seçiniz</option>
                                        <?php foreach ($users as $user): ?>
                                            <option value="<?php echo $user['user_id']; ?>">
                                                <?php echo $user['name'] . ' ' . $user['surname'] . ' - ' . $user['department']; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="absence_type_id">Devamsızlık Türü</label>
                                    <select class="form-control" id="absence_type_id" name="absence_type_id" required>
                                        <option value="">Seçiniz</option>
                                        <?php foreach ($absence_types as $type): ?>
                                            <option value="<?php echo $type['id']; ?>"><?php echo $type['name']; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="row">
                                <div class="form-group col-md-6">
                                    <label for="start_date">Başlangıç Tarihi</label>
                                    <input type="date" class="form-control" id="start_date" name="start_date" required>
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="end_date">Bitiş Tarihi</label>
                                    <input type="date" class="form-control" id="end_date" name="end_date" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="reason">Açıklama / Sebep</label>
                                <textarea class="form-control" id="reason" name="reason" rows="3"></textarea>
                            </div>
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="is_justified" name="is_justified">
                                <label class="form-check-label" for="is_justified">
                                    Mazeretli
                                </label>
                            </div>
                            <button type="submit" name="add_absence" class="btn btn-primary">
                                <i class="fas fa-save mr-1"></i> Kaydet
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Devamsızlık Geçmişi -->
            <div class="tab-pane fade" id="history" role="tabpanel">
                <div class="card mt-3">
                    <div class="card-header bg-secondary text-white">
                        <i class="fas fa-history mr-1"></i> Devamsızlık Geçmişi
                    </div>
                    <div class="card-body">
                        <div id="absence-history-container">
                            <!-- AJAX ile doldurulacak -->
                            <div class="text-center">
                                <div class="spinner-border text-secondary" role="status">
                                    <span class="sr-only">Yükleniyor...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
     <!-- Devamsızlık Detay Modal -->
    <div class="modal fade" id="absenceDetailModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title">Devamsızlık Detayı</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="absence-detail-content">
                    <div class="text-center">
                        <div class="spinner-border text-info" role="status">
                            <span class="sr-only">Yükleniyor...</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Kapat</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Devamsızlık Düzenleme Modal -->
    <div class="modal fade" id="editAbsenceModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-warning text-white">
                    <h5 class="modal-title">Devamsızlık Kaydını Düzenle</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <form id="edit-absence-form">
                    <div class="modal-body">
                        <input type="hidden" id="edit_absence_id" name="absence_id">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="edit_absence_type_id">Devamsızlık Türü</label>
                                    <select class="form-control" id="edit_absence_type_id" name="absence_type_id" required>
                                        <option value="">Seçiniz</option>
                                        <?php foreach ($absence_types as $type): ?>
                                            <option value="<?php echo $type['id']; ?>"><?php echo $type['name']; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check mt-4 pt-2">
                                    <input class="form-check-input" type="checkbox" id="edit_is_justified" name="is_justified">
                                    <label class="form-check-label" for="edit_is_justified">
                                        Mazeretli
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="edit_start_date">Başlangıç Tarihi</label>
                                    <input type="date" class="form-control" id="edit_start_date" name="start_date" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="edit_end_date">Bitiş Tarihi</label>
                                    <input type="date" class="form-control" id="edit_end_date" name="end_date" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="edit_reason">Açıklama / Sebep</label>
                            <textarea class="form-control" id="edit_reason" name="reason" rows="3"></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="edit_admin_note">Yönetici Notu</label>
                            <textarea class="form-control" id="edit_admin_note" name="admin_note" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">İptal</button>
                        <button type="submit" class="btn btn-warning">Güncelle</button>
                    </div>
                    
                </form>
            </div>
        </div>
    </div>

    <!-- Hızlı Devamsızlık Ekleme Modal (önceki kod aynı) -->
    <div class="modal fade" id="quickAbsenceModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Devamsızlık Kaydı Ekle</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <form method="post" action="">
                    <div class="modal-body">
                        <input type="hidden" id="quick_user_id" name="user_id">
                        <input type="hidden" id="quick_start_date" name="start_date">
                        <input type="hidden" id="quick_end_date" name="end_date">
                        <input type="hidden" id="quick_is_justified" name="is_justified">
                        
                        <div class="alert alert-info">
                            <strong>Personel:</strong> <span id="quick_employee_name"></span><br>
                            <strong>Tarih:</strong> <span id="quick_date"></span>
                        </div>
                        
                        <div class="form-group">
                            <label for="quick_absence_type_id">Devamsızlık Türü</label>
                            <select class="form-control" id="quick_absence_type_id" name="absence_type_id" required>
                                <option value="">Seçiniz</option>
                                <?php foreach ($absence_types as $type): ?>
                                    <option value="<?php echo $type['id']; ?>"><?php echo $type['name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="quick_reason">Açıklama</label>
                            <textarea class="form-control" id="quick_reason" name="reason" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">İptal</button>
                        <button type="submit" name="add_absence" class="btn btn-primary">Kaydet</button>
                    </div>
                    
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Mazeretli devamsızlık ekleme
            $('.add-justified-absence').click(function() {
                var userId = $(this).data('user-id');
                var name = $(this).data('name');
                var today = '<?php echo date('Y-m-d'); ?>';
                
                $('#quick_user_id').val(userId);
                $('#quick_start_date').val(today);
                $('#quick_end_date').val(today);
                $('#quick_is_justified').val('1');
                $('#quick_employee_name').text(name);
                $('#quick_date').text('<?php echo date('d.m.Y'); ?>');
                
                $('#quickAbsenceModal').modal('show');
            });
            
            // Mazeretsiz devamsızlık ekleme
            $('.add-unjustified-absence').click(function() {
                var userId = $(this).data('user-id');
                var name = $(this).data('name');
                var today = '<?php echo date('Y-m-d'); ?>';
                
                $('#quick_user_id').val(userId);
                $('#quick_start_date').val(today);
                $('#quick_end_date').val(today);
                $('#quick_is_justified').val('0');
                $('#quick_employee_name').text(name);
                $('#quick_date').text('<?php echo date('d.m.Y'); ?>');
                
                $('#quickAbsenceModal').modal('show');
            });
            
            // Tarih aralığı kontrolü
            $('#start_date').change(function() {
                $('#end_date').attr('min', $(this).val());
            });
            
            // Devamsızlık geçmişini yükle
            $('#history-tab').on('shown.bs.tab', function() {
                loadAbsenceHistory();
            });
        });
        
        function loadAbsenceHistory() {
            $.ajax({
                url: 'get_absence_history.php',
                success: function(data) {
                    $('#absence-history-container').html(data);
                },
                error: function() {
                    $('#absence-history-container').html('<div class="alert alert-danger">Veriler yüklenirken bir hata oluştu!</div>');
                }
            });
        }

        // Filtreleme fonksiyonu
        function filterAbsences() {
            var search = $('#filter-search').val();
            var department = $('#filter-department').val();
            var absenceType = $('#filter-absence-type').val();
            var date = $('#filter-date').val();
            var status = $('#filter-status').val();
            
            var params = new URLSearchParams();
            if (search) params.append('search', search);
            if (department) params.append('department', department);
            if (absenceType) params.append('absence_type', absenceType);
            if (date) params.append('date_filter', date);
            if (status) params.append('status_filter', status);
            
            $.ajax({
                url: 'get_absence_history.php?' + params.toString(),
                success: function(data) {
                    $('#absence-history-container').html(data);
                },
                error: function() {
                    $('#absence-history-container').html('<div class="alert alert-danger">Veriler yüklenirken bir hata oluştu!</div>');
                }
            });
        }
        
        // Filtreleri temizle
        function clearFilters() {
            $('#filter-search').val('');
            $('#filter-department').val('');
            $('#filter-absence-type').val('');
            $('#filter-date').val('');
            $('#filter-status').val('');
            loadAbsenceHistory();
        }
        
        // Devamsızlık geçmişini yükle (güncellendi)
        function loadAbsenceHistory() {
            $.ajax({
                url: 'get_absence_history.php',
                success: function(data) {
                    $('#absence-history-container').html(data);
                },
                error: function() {
                    $('#absence-history-container').html('<div class="alert alert-danger">Veriler yüklenirken bir hata oluştu!</div>');
                }
            });
        }
        
        // Event delegation ile dinamik butonlara event bağla
        $(document).on('click', '.view-absence', function() {
            var absenceId = $(this).data('id');
            
            $.ajax({
                url: 'get_absence_detail.php',
                data: { id: absenceId },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        $('#absence-detail-content').html(response.html);
                        $('#absenceDetailModal').modal('show');
                    } else {
                        alert('Hata: ' + response.message);
                    }
                },
                error: function() {
                    alert('Devamsızlık detayı yüklenirken bir hata oluştu!');
                }
            });
        });
        
        // Devamsızlık düzenleme
        $(document).on('click', '.edit-absence', function() {
            var absenceId = $(this).data('id');
            
            $.ajax({
                url: 'get_absence_detail.php',
                data: { id: absenceId },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        var data = response.data;
                        $('#edit_absence_id').val(data.id);
                        $('#edit_absence_type_id').val(data.absence_type_id);
                        $('#edit_start_date').val(data.start_date);
                        $('#edit_end_date').val(data.end_date);
                        $('#edit_reason').val(data.reason);
                        $('#edit_admin_note').val(data.admin_note);
                        $('#edit_is_justified').prop('checked', data.is_justified == 1);
                        
                        $('#editAbsenceModal').modal('show');
                    } else {
                        alert('Hata: ' + response.message);
                    }
                },
                error: function() {
                    alert('Devamsızlık bilgisi yüklenirken bir hata oluştu!');
                }
            });
        });
        
        // Devamsızlık güncelleme formu
        $('#edit-absence-form').submit(function(e) {
            e.preventDefault();
            
            $.ajax({
                type: 'POST',
                url: 'update_absence.php',
                data: $(this).serialize(),
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        $('#editAbsenceModal').modal('hide');
                        loadAbsenceHistory();
                        alert(response.message);
                    } else {
                        alert('Hata: ' + response.message);
                    }
                },
                error: function() {
                    alert('Güncelleme sırasında bir hata oluştu!');
                }
            });
        });
        
        // Devamsızlık silme
        $(document).on('click', '.delete-absence', function() {
            var absenceId = $(this).data('id');
            
            if (confirm('Bu devamsızlık kaydını silmek istediğinizden emin misiniz?')) {
                $.ajax({
                    type: 'POST',
                    url: 'delete_absence.php',
                    data: { absence_id: absenceId },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            $('#absence-row-' + absenceId).fadeOut(500, function() {
                                $(this).remove();
                            });
                            alert(response.message);
                        } else {
                            alert('Hata: ' + response.message);
                        }
                    },
                    error: function() {
                        alert('Silme işlemi sırasında bir hata oluştu!');
                    }
                });
            }
        });
        
        // Tarih aralığı kontrolü
        $(document).on('change', '#edit_start_date', function() {
            $('#edit_end_date').attr('min', $(this).val());
        });
        
        // Enter tuşuyla filtreleme
        $(document).on('keypress', '#filter-search', function(e) {
            if (e.which == 13) {
                filterAbsences();
            }
        });
    </script>
</body>
</html>