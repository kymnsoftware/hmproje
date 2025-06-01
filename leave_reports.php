<?php
session_start();
// Oturum kontrolü
if (!isset($_SESSION['user_id']) || $_SESSION['privilege'] < 1) {
    header('Location: login.php');
    exit;
}

// Veritabanı bağlantısı
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "db_kartlar";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // İzin türlerini al
    $stmt = $conn->query("SELECT * FROM leave_types ORDER BY name");
    $leave_types = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Departmanları al
    $stmt = $conn->query("SELECT DISTINCT department FROM cards WHERE department != '' ORDER BY department");
    $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Kullanıcıları al
    $stmt = $conn->query("SELECT user_id, name, surname FROM cards WHERE enabled = 'true' ORDER BY name, surname");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Rapor türünü al
    $report_type = isset($_GET['report_type']) ? $_GET['report_type'] : '';
    $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-01-01');
    $end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-12-31');
    
    // Departman filtresi
    $department = isset($_GET['department']) ? $_GET['department'] : '';
    
    // Kullanıcı filtresi
    $user_id = isset($_GET['user_id']) ? $_GET['user_id'] : '';
    
} catch(PDOException $e) {
    die("Veritabanı hatası: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PDKS - İzin Raporları</title>
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
        .report-card {
            height: 100%;
        }
        .leave-type {
            display: inline-block;
            width: 15px;
            height: 15px;
            border-radius: 50%;
            margin-right: 5px;
        }
        .total-row {
            font-weight: bold;
            background-color: #f8f9fa;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <h2><i class="fas fa-chart-bar mr-2"></i> İzin Raporları</h2>
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
                <a href="leave_management.php" class="btn btn-outline-success mr-2">
                    <i class="fas fa-tasks mr-1"></i> İzin Yönetimi
                </a>
                <a href="logout.php" class="btn btn-outline-secondary">
                    <i class="fas fa-sign-out-alt mr-1"></i> Çıkış Yap
                </a>
            </div>
        </div>
        
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card report-card">
                    <div class="card-header bg-primary text-white">
                        <i class="fas fa-filter mr-1"></i> Rapor Filtreleri
                    </div>
                    <div class="card-body">
                        <form method="get" action="">
                            <div class="form-group">
                                <label for="report_type">Rapor Türü</label>
                                <select class="form-control" id="report_type" name="report_type" required>
                                    <option value="department" <?php echo $report_type == 'department' ? 'selected' : ''; ?>>Departman Bazlı Rapor</option>
                                    <option value="user" <?php echo $report_type == 'user' ? 'selected' : ''; ?>>Personel Bazlı Rapor</option>
                                    <option value="leave_type" <?php echo $report_type == 'leave_type' ? 'selected' : ''; ?>>İzin Türü Bazlı Rapor</option>
                                </select>
                            </div>
                            
                            <div class="form-group" id="department-group" style="display: <?php echo ($report_type != 'user') ? 'block' : 'none'; ?>">
                                <label for="department">Departman</label>
                                <select class="form-control" id="department" name="department">
                                    <option value="">Tüm Departmanlar</option>
                                    <?php foreach ($departments as $dept): ?>
                                        <option value="<?php echo $dept['department']; ?>" <?php echo $dept['department'] == $department ? 'selected' : ''; ?>>
                                            <?php echo $dept['department']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group" id="user-group" style="display: <?php echo $report_type == 'user' ? 'block' : 'none'; ?>">
                                <label for="user_id">Personel</label>
                                <select class="form-control" id="user_id" name="user_id">
                                    <option value="">Tüm Personel</option>
                                    <?php foreach ($users as $user): ?>
                                        <option value="<?php echo $user['user_id']; ?>" <?php echo $user['user_id'] == $user_id ? 'selected' : ''; ?>>
                                            <?php echo $user['name'] . ' ' . $user['surname']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="start_date">Başlangıç Tarihi</label>
                                <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo $start_date; ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="end_date">Bitiş Tarihi</label>
                                <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo $end_date; ?>" required>
                            </div>
                            
                            <button type="submit" class="btn btn-primary btn-block">
                                <i class="fas fa-search mr-1"></i> Raporu Göster
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-9">
                <div class="card report-card">
                    <div class="card-header bg-info text-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fas fa-chart-bar mr-1"></i> 
                                <?php
                                if ($report_type == 'department') {
                                    echo 'Departman Bazlı İzin Raporu';
                                } elseif ($report_type == 'user') {
                                    echo 'Personel Bazlı İzin Raporu';
                                } elseif ($report_type == 'leave_type') {
                                    echo 'İzin Türü Bazlı Rapor';
                                } else {
                                    echo 'İzin Raporu';
                                }
                                ?>
                                <small class="ml-2">(<?php echo date('d.m.Y', strtotime($start_date)) . ' - ' . date('d.m.Y', strtotime($end_date)); ?>)</small>
                            </div>
                            <div>
                                <button class="btn btn-sm btn-light" id="export-excel">
                                    <i class="fas fa-file-excel mr-1"></i> Excel
                                </button>
                                <button class="btn btn-sm btn-light" id="export-pdf">
                                    <i class="fas fa-file-pdf mr-1"></i> PDF
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($report_type)): ?>
                            <?php if ($report_type == 'department'): ?>
                                <!-- Departman Bazlı Rapor -->
                                <?php
                                // SQL sorgusu
                                $sql = "
                                    SELECT c.department, lt.id as leave_type_id, lt.name as leave_type_name, lt.color,
                                           COUNT(DISTINCT lr.id) as leave_count,
                                           SUM(lr.total_days) as total_days
                                    FROM leave_requests lr
                                    JOIN cards c ON lr.user_id = c.user_id
                                    JOIN leave_types lt ON lr.leave_type_id = lt.id
                                    WHERE lr.status = 'approved'
                                    AND lr.start_date >= :start_date
                                    AND lr.end_date <= :end_date
                                ";
                                
                                if (!empty($department)) {
                                    $sql .= " AND c.department = :department";
                                }
                                
                                $sql .= " GROUP BY c.department, lt.id
                                          ORDER BY c.department, lt.name";
                                
                                $stmt = $conn->prepare($sql);
                                $stmt->bindParam(':start_date', $start_date);
                                $stmt->bindParam(':end_date', $end_date);
                                
                                if (!empty($department)) {
                                    $stmt->bindParam(':department', $department);
                                }
                                
                                $stmt->execute();
                                $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                
                                // Sonuçları departmanlara göre düzenle
                                $departments = [];
                                $totalsByType = [];
                                $grandTotal = 0;
                                
                                foreach ($result as $row) {
                                    if (!isset($departments[$row['department']])) {
                                        $departments[$row['department']] = [];
                                    }
                                    
                                    $departments[$row['department']][$row['leave_type_id']] = [
                                        'name' => $row['leave_type_name'],
                                        'color' => $row['color'],
                                        'count' => $row['leave_count'],
                                        'days' => $row['total_days']
                                    ];
                                    
                                    // İzin türüne göre toplamları hesapla
                                    if (!isset($totalsByType[$row['leave_type_id']])) {
                                        $totalsByType[$row['leave_type_id']] = [
                                            'name' => $row['leave_type_name'],
                                            'color' => $row['color'],
                                            'count' => 0,
                                            'days' => 0
                                        ];
                                    }
                                    
                                    $totalsByType[$row['leave_type_id']]['count'] += $row['leave_count'];
                                    $totalsByType[$row['leave_type_id']]['days'] += $row['total_days'];
                                    $grandTotal += $row['total_days'];
                                }
                                ?>
                                
                                <?php if (count($departments) > 0): ?>
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Departman</th>
                                                    <?php foreach ($leave_types as $type): ?>
                                                        <th>
                                                            <span class="leave-type" style="background-color: <?php echo $type['color']; ?>"></span>
                                                            <?php echo $type['name']; ?>
                                                        </th>
                                                    <?php endforeach; ?>
                                                    <th>Toplam Gün</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php 
                                                $departmentTotals = [];
                                                foreach ($departments as $dept => $types): 
                                                    $deptTotal = 0;
                                                ?>
                                                    <tr>
                                                        <td><?php echo $dept; ?></td>
                                                        <?php 
                                                        foreach ($leave_types as $type): 
                                                            $typeId = $type['id'];
                                                            $days = isset($types[$typeId]) ? $types[$typeId]['days'] : 0;
                                                            $deptTotal += $days;
                                                        ?>
                                                            <td><?php echo $days > 0 ? $days . ' gün' : '-'; ?></td>
                                                        <?php endforeach; ?>
                                                        <td><strong><?php echo $deptTotal; ?> gün</strong></td>
                                                    </tr>
                                                <?php 
                                                    $departmentTotals[$dept] = $deptTotal;
                                                endforeach; 
                                                ?>
                                                <tr class="total-row">
                                                    <td><strong>Toplam</strong></td>
                                                    <?php 
                                                    $totalAllTypes = 0;
                                                    foreach ($leave_types as $type): 
                                                        $typeId = $type['id'];
                                                        $typeDays = isset($totalsByType[$typeId]) ? $totalsByType[$typeId]['days'] : 0;
                                                        $totalAllTypes += $typeDays;
                                                    ?>
                                                        <td><strong><?php echo $typeDays > 0 ? $typeDays . ' gün' : '-'; ?></strong></td>
                                                    <?php endforeach; ?>
                                                    <td><strong><?php echo $totalAllTypes; ?> gün</strong></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle mr-1"></i> Seçilen kriterlere uygun veri bulunamadı.
                                    </div>
                                <?php endif; ?>
                                
                            <?php elseif ($report_type == 'user'): ?>
                                <!-- Personel Bazlı Rapor -->
                                <?php
                                // SQL sorgusu
                                $sql = "
                                    SELECT c.user_id, c.name, c.surname, c.department, c.position,
                                           lt.id as leave_type_id, lt.name as leave_type_name, lt.color,
                                           COUNT(lr.id) as leave_count,
                                           SUM(lr.total_days) as total_days
                                    FROM leave_requests lr
                                    JOIN cards c ON lr.user_id = c.user_id
                                    JOIN leave_types lt ON lr.leave_type_id = lt.id
                                    WHERE lr.status = 'approved'
                                    AND lr.start_date >= :start_date
                                    AND lr.end_date <= :end_date
                                ";
                                
                                if (!empty($user_id)) {
                                    $sql .= " AND c.user_id = :user_id";
                                }
                                
                                if (!empty($department)) {
                                    $sql .= " AND c.department = :department";
                                }
                                
                                $sql .= " GROUP BY c.user_id, lt.id
                                          ORDER BY c.name, c.surname, lt.name";
                                
                                $stmt = $conn->prepare($sql);
                                $stmt->bindParam(':start_date', $start_date);
                                $stmt->bindParam(':end_date', $end_date);
                                
                                if (!empty($user_id)) {
                                    $stmt->bindParam(':user_id', $user_id);
                                }
                                
                                if (!empty($department)) {
                                    $stmt->bindParam(':department', $department);
                                }
                                
                                $stmt->execute();
                                $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                
                                // Sonuçları kullanıcılara göre düzenle
                                $users = [];
                                $totalsByType = [];
                                $grandTotal = 0;
                                
                                foreach ($result as $row) {
                                    $userId = $row['user_id'];
                                    
                                    if (!isset($users[$userId])) {
                                        $users[$userId] = [
                                            'name' => $row['name'] . ' ' . $row['surname'],
                                            'department' => $row['department'],
                                            'position' => $row['position'],
                                            'leaves' => []
                                        ];
                                    }
                                    
                                    $users[$userId]['leaves'][$row['leave_type_id']] = [
                                        'name' => $row['leave_type_name'],
                                        'color' => $row['color'],
                                        'count' => $row['leave_count'],
                                        'days' => $row['total_days']
                                    ];
                                    
                                    // İzin türüne göre toplamları hesapla
                                    if (!isset($totalsByType[$row['leave_type_id']])) {
                                        $totalsByType[$row['leave_type_id']] = [
                                            'name' => $row['leave_type_name'],
                                            'color' => $row['color'],
                                            'count' => 0,
                                            'days' => 0
                                        ];
                                    }
                                    
                                    $totalsByType[$row['leave_type_id']]['count'] += $row['leave_count'];
                                    $totalsByType[$row['leave_type_id']]['days'] += $row['total_days'];
                                    $grandTotal += $row['total_days'];
                                }
                                ?>
                                
                                <?php if (count($users) > 0): ?>
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Personel</th>
                                                    <th>Departman</th>
                                                    <?php foreach ($leave_types as $type): ?>
                                                        <th>
                                                            <span class="leave-type" style="background-color: <?php echo $type['color']; ?>"></span>
                                                            <?php echo $type['name']; ?>
                                                        </th>
                                                    <?php endforeach; ?>
                                                    <th>Toplam Gün</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php 
                                                $userTotals = [];
                                                foreach ($users as $userId => $userData): 
                                                    $userTotal = 0;
                                                ?>
                                                    <tr>
                                                        <td><?php echo $userData['name']; ?></td>
                                                        <td><?php echo $userData['department']; ?></td>
                                                        <?php 
                                                        foreach ($leave_types as $type): 
                                                            $typeId = $type['id'];
                                                            $days = isset($userData['leaves'][$typeId]) ? $userData['leaves'][$typeId]['days'] : 0;
                                                            $userTotal += $days;
                                                        ?>
                                                            <td><?php echo $days > 0 ? $days . ' gün' : '-'; ?></td>
                                                        <?php endforeach; ?>
                                                        <td><strong><?php echo $userTotal; ?> gün</strong></td>
                                                    </tr>
                                                <?php 
                                                    $userTotals[$userId] = $userTotal;
                                                endforeach; 
                                                ?>
                                                <tr class="total-row">
                                                    <td colspan="2"><strong>Toplam</strong></td>
                                                    <?php 
                                                    $totalAllTypes = 0;
                                                    foreach ($leave_types as $type): 
                                                        $typeId = $type['id'];
                                                        $typeDays = isset($totalsByType[$typeId]) ? $totalsByType[$typeId]['days'] : 0;
                                                        $totalAllTypes += $typeDays;
                                                    ?>
                                                        <td><strong><?php echo $typeDays > 0 ? $typeDays . ' gün' : '-'; ?></strong></td>
                                                    <?php endforeach; ?>
                                                    <td><strong><?php echo $totalAllTypes; ?> gün</strong></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle mr-1"></i> Seçilen kriterlere uygun veri bulunamadı.
                                    </div>
                                <?php endif; ?>
                                
                            <?php elseif ($report_type == 'leave_type'): ?>
                                <!-- İzin Türü Bazlı Rapor -->
                                <?php
                                // SQL sorgusu
                                $sql = "
                                    SELECT lt.id as leave_type_id, lt.name as leave_type_name, lt.color,
                                           COUNT(lr.id) as leave_count,
                                           SUM(lr.total_days) as total_days,
                                           c.department
                                    FROM leave_requests lr
                                    JOIN cards c ON lr.user_id = c.user_id
                                    JOIN leave_types lt ON lr.leave_type_id = lt.id
                                    WHERE lr.status = 'approved'
                                    AND lr.start_date >= :start_date
                                    AND lr.end_date <= :end_date
                                ";
                                
                                if (!empty($department)) {
                                    $sql .= " AND c.department = :department";
                                }
                                
                                $sql .= " GROUP BY lt.id, c.department
                                          ORDER BY lt.name, c.department";
                                
                                $stmt = $conn->prepare($sql);
                                $stmt->bindParam(':start_date', $start_date);
                                $stmt->bindParam(':end_date', $end_date);
                                
                                if (!empty($department)) {
                                    $stmt->bindParam(':department', $department);
                                }
                                
                                $stmt->execute();
                                $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                
                                // Sonuçları izin türlerine göre düzenle
                                $leaveTypes = [];
                                $departments = [];
                                $totalsByDept = [];
                                $grandTotal = 0;
                                
                                foreach ($result as $row) {
                                    if (!isset($leaveTypes[$row['leave_type_id']])) {
                                        $leaveTypes[$row['leave_type_id']] = [
                                            'name' => $row['leave_type_name'],
                                            'color' => $row['color'],
                                            'departments' => []
                                        ];
                                    }
                                    
                                    $leaveTypes[$row['leave_type_id']]['departments'][$row['department']] = [
                                        'count' => $row['leave_count'],
                                        'days' => $row['total_days']
                                    ];
                                    
                                    // Departmanlara göre toplamları hesapla
                                    if (!isset($totalsByDept[$row['department']])) {
                                        $totalsByDept[$row['department']] = 0;
                                    }
                                    
                                    $totalsByDept[$row['department']] += $row['total_days'];
                                    $grandTotal += $row['total_days'];
                                    
                                    // Tüm departmanların listesini oluştur
                                    if (!in_array($row['department'], $departments)) {
                                        $departments[] = $row['department'];
                                    }
                                }
                                
                                // Departmanları alfabetik sırala
                                sort($departments);
                                ?>
                                
                                <?php if (count($leaveTypes) > 0): ?>
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-striped">
                                            <thead>
                                                <tr>
                                                    <th>İzin Türü</th>
                                                    <?php foreach ($departments as $dept): ?>
                                                        <th><?php echo $dept; ?></th>
                                                    <?php endforeach; ?>
                                                    <th>Toplam Gün</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php 
                                                $typeTotals = [];
                                                foreach ($leaveTypes as $typeId => $typeData): 
                                                    $typeTotal = 0;
                                                ?>
                                                    <tr>
                                                        <td>
                                                            <span class="leave-type" style="background-color: <?php echo $typeData['color']; ?>"></span>
                                                            <?php echo $typeData['name']; ?>
                                                        </td>
                                                        <?php 
                                                        foreach ($departments as $dept): 
                                                            $days = isset($typeData['departments'][$dept]) ? $typeData['departments'][$dept]['days'] : 0;
                                                            $typeTotal += $days;
                                                        ?>
                                                            <td><?php echo $days > 0 ? $days . ' gün' : '-'; ?></td>
                                                        <?php endforeach; ?>
                                                        <td><strong><?php echo $typeTotal; ?> gün</strong></td>
                                                    </tr>
                                                <?php 
                                                    $typeTotals[$typeId] = $typeTotal;
                                                endforeach; 
                                                ?>
                                                <tr class="total-row">
                                                    <td><strong>Toplam</strong></td>
                                                    <?php 
                                                    foreach ($departments as $dept): 
                                                        $deptTotal = isset($totalsByDept[$dept]) ? $totalsByDept[$dept] : 0;
                                                    ?>
                                                        <td><strong><?php echo $deptTotal > 0 ? $deptTotal . ' gün' : '-'; ?></strong></td>
                                                    <?php endforeach; ?>
                                                    <td><strong><?php echo $grandTotal; ?> gün</strong></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle mr-1"></i> Seçilen kriterlere uygun veri bulunamadı.
                                    </div>
                                <?php endif; ?>
                                
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle mr-1"></i> Lütfen bir rapor türü seçin ve kriterleri belirleyin.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        $(document).ready(function() {
            // Rapor türüne göre form alanlarını göster/gizle
            $('#report_type').change(function() {
                var reportType = $(this).val();
                
                if (reportType == 'user') {
                    $('#user-group').show();
                    $('#department-group').hide();
                } else {
                    $('#user-group').hide();
                    $('#department-group').show();
                }
            });
            
            // Excel export
            $('#export-excel').click(function() {
                var url = 'export_leave_report.php?' + window.location.search.substring(1) + '&format=excel';
                window.open(url, '_blank');
            });
            
            // PDF export
            $('#export-pdf').click(function() {
                var url = 'export_leave_report.php?' + window.location.search.substring(1) + '&format=pdf';
                window.open(url, '_blank');
            });
        });
    </script>
</body>
</html>