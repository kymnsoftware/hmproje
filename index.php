<?php
session_start();
// Oturum kontrolü
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Yetki kontrolü - sadece admin erişebilir
if ($_SESSION['privilege'] < 1) {
    header('Location: leave_request.php');
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
} catch(PDOException $e) {
    die("Veritabanı bağlantı hatası: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PDKS - Personel Devam Kontrol Sistemi</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@5.15.4/css/all.min.css">
    <style>
        /* Tema renkleri */
        :root {
            --primary-color: #3498db;
            --secondary-color: #2ecc71;
            --danger-color: #e74c3c;
            --warning-color: #f39c12;
            --info-color: #3498db;
            --success-color: #2ecc71;
            --light-color: #f8f9fa;
            --dark-color: #343a40;
            --bg-color: #f8f9fa;
            --text-color: #333;
            --border-color: #dee2e6;
            --nav-bg: #fff;
            --card-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        /* Koyu tema */
        [data-theme="dark"] {
            --primary-color: #375a7f;
            --secondary-color: #3e9451;
            --danger-color: #e74c3c;
            --warning-color: #f39c12;
            --info-color: #3498db;
            --success-color: #00bc8c;
            --light-color: #444;
            --dark-color: #222;
            --bg-color: #222;
            --text-color: #fff;
            --border-color: #444;
            --nav-bg: #375a7f;
            --card-shadow: 0 2px 5px rgba(0,0,0,0.3);
        }
        
        /* Yeşil tema */
        [data-theme="green"] {
            --primary-color: #2ecc71;
            --secondary-color: #27ae60;
            --danger-color: #e74c3c;
            --warning-color: #f39c12;
            --info-color: #3498db;
            --success-color: #27ae60;
            --light-color: #f8f9fa;
            --dark-color: #1e8449;
            --bg-color: #ecf0f1;
            --text-color: #333;
            --border-color: #bdc3c7;
            --nav-bg: #fff;
            --card-shadow: 0 2px 5px rgba(0,150,0,0.1);
        }
        
        /* Mavi tema */
        [data-theme="blue"] {
            --primary-color: #3498db;
            --secondary-color: #2980b9;
            --danger-color: #e74c3c;
            --warning-color: #f39c12;
            --info-color: #2980b9;
            --success-color: #2ecc71;
            --light-color: #f8f9fa;
            --dark-color: #2c3e50;
            --bg-color: #ecf0f1;
            --text-color: #2c3e50;
            --border-color: #bdc3c7;
            --nav-bg: #fff;
            --card-shadow: 0 2px 5px rgba(0,0,150,0.1);
        }
        
        /* Kırmızı tema */
        [data-theme="red"] {
            --primary-color: #e74c3c;
            --secondary-color: #c0392b;
            --danger-color: #c0392b;
            --warning-color: #f39c12;
            --info-color: #3498db;
            --success-color: #2ecc71;
            --light-color: #f8f9fa;
            --dark-color: #922b21;
            --bg-color: #f5f5f5;
            --text-color: #333;
            --border-color: #bdc3c7;
            --nav-bg: #fff;
            --card-shadow: 0 2px 5px rgba(150,0,0,0.1);
        }
        
        /* Temel stil tanımları */
        body {
            background-color: var(--bg-color);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding-top: 20px;
            color: var(--text-color);
            transition: background-color 0.3s ease;
        }
        
        .header-container, .main-container {
            background-color: var(--nav-bg);
            border-radius: 10px;
            box-shadow: var(--card-shadow);
            padding: 20px;
            margin-bottom: 20px;
            border: 1px solid var(--border-color);
        }
        
        .nav-tabs .nav-item .nav-link {
            font-weight: 500;
            color: var(--text-color);
            border: 1px solid transparent;
        }
        
        .nav-tabs .nav-item .nav-link.active {
            color: var(--primary-color);
            background-color: var(--nav-bg);
            border-color: var(--border-color) var(--border-color) var(--nav-bg);
        }
        
        .card {
            background-color: var(--nav-bg);
            border-color: var(--border-color);
            color: var(--text-color);
            margin-bottom: 20px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .card-header {
            border-color: var(--border-color);
            border-radius: 8px 8px 0 0 !important;
            font-weight: 600;
        }
        
        .table {
            color: var(--text-color);
        }
        
        .table-striped tbody tr:nth-of-type(odd) {
            background-color: rgba(0, 0, 0, 0.05);
        }
        
        .table thead th {
            border-color: var(--border-color);
        }
        
        .table td, .table th {
            border-color: var(--border-color);
        }
        
        .form-control {
            background-color: var(--light-color);
            border-color: var(--border-color);
            color: var(--text-color);
        }
        
        .form-control:focus {
            background-color: var(--light-color);
            color: var(--text-color);
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-secondary {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }
        
        .btn-success {
            background-color: var(--success-color);
            border-color: var(--success-color);
        }
        
        .btn-danger {
            background-color: var(--danger-color);
            border-color: var(--danger-color);
        }
        
        .btn-warning {
            background-color: var(--warning-color);
            border-color: var(--warning-color);
        }
        
        .btn-info {
            background-color: var(--info-color);
            border-color: var(--info-color);
        }
        
        .badge-success {
            background-color: var(--success-color);
        }
        
        .badge-danger {
            background-color: var(--danger-color);
        }
        
        .badge-warning {
            background-color: var(--warning-color);
        }
        
        .badge-info {
            background-color: var(--info-color);
        }
        
        .alert-success {
            background-color: var(--success-color);
            border-color: var(--success-color);
            color: #fff;
        }
        
        .alert-danger {
            background-color: var(--danger-color);
            border-color: var(--danger-color);
            color: #fff;
        }
        
        .alert-warning {
            background-color: var(--warning-color);
            border-color: var(--warning-color);
            color: #fff;
        }
        
        .alert-info {
            background-color: var(--info-color);
            border-color: var(--info-color);
            color: #fff;
        }
        
        .bg-primary {
            background-color: var(--primary-color) !important;
        }
        
        .bg-secondary {
            background-color: var(--secondary-color) !important;
        }
        
        .bg-success {
            background-color: var(--success-color) !important;
        }
        
        .bg-danger {
            background-color: var(--danger-color) !important;
        }
        
        .bg-warning {
            background-color: var(--warning-color) !important;
        }
        
        .bg-info {
            background-color: var(--info-color) !important;
        }
        
        .bg-light {
            background-color: var(--light-color) !important;
        }
        
        .bg-dark {
            background-color: var(--dark-color) !important;
        }
        
        .text-primary {
            color: var(--primary-color) !important;
        }
        
        /* Tema değiştirici */
        .theme-selector {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1000;
            background-color: var(--nav-bg);
            border-radius: 50%;
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: var(--card-shadow);
            transition: all 0.3s ease;
        }
        
        .theme-selector:hover {
            transform: scale(1.1);
        }
        
        .theme-options {
            position: fixed;
            bottom: 80px;
            right: 20px;
            z-index: 1000;
            background-color: var(--nav-bg);
            border-radius: 10px;
            padding: 10px;
            box-shadow: var(--card-shadow);
            display: none;
        }
        
        .theme-option {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            margin: 5px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .theme-option:hover {
            transform: scale(1.2);
        }
        
        .theme-light {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border: 2px solid #ced4da;
        }
        
        .theme-dark {
            background: linear-gradient(135deg, #343a40 0%, #212529 100%);
            border: 2px solid #495057;
        }
        
        .theme-green {
            background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%);
            border: 2px solid #27ae60;
        }
        
        .theme-blue {
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            border: 2px solid #2980b9;
        }
        
        .theme-red {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            border: 2px solid #c0392b;
        }
        
        /* İstatistik kartları */
        .dashboard-card {
            text-align: center;
            padding: 15px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: none;
        }
        
        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        
        .dashboard-number {
            font-size: 2.5rem;
            font-weight: bold;
        }
        
        .dashboard-label {
            font-size: 1rem;
            color: rgba(255, 255, 255, 0.8);
        }
        
        /* Animasyonlar */
        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        /* Kullanıcı fotoğrafı */
        .user-photo {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--light-color);
            box-shadow: var(--card-shadow);
        }
        
        .user-photo-small {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--light-color);
        }
        
        /* Bildirimler */
        .alerts-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1050;
            width: 350px;
        }
        
        /* Diğer özelleştirmeler */
        .action-btn {
            margin-right: 5px;
        }
        
        .badge-status {
            font-size: 90%;
        }
        
        /* Rapor formatı seçici */
        .report-format-selector {
            display: flex;
            align-items: center;
        }
        
        .format-option {
            cursor: pointer;
            padding: 5px 10px;
            margin-left: 5px;
            border-radius: 5px;
            transition: all 0.2s ease;
        }
        
        .format-option:hover {
            background-color: rgba(0,0,0,0.1);
        }
        
        .format-option.active {
            background-color: var(--primary-color);
            color: white;
        }
        
        .tab-content {
            padding: 20px;
            border: 1px solid var(--border-color);
            border-top: none;
            border-radius: 0 0 5px 5px;
        }
    </style>
</head>
<body>
    <div class="row align-items-center">
    <div class="col-md-8">
        <h1 class="text-primary"><i class="fas fa-id-card mr-2"></i>PDKS - Personel Devam Kontrol Sistemi</h1>
        <p class="text-muted">Personel geçiş ve kontrol yönetimi</p>
    </div>
    <div class="col-md-4 text-right">
        <div class="dropdown mr-2 d-inline-block">
            <button class="btn btn-outline-primary dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <i class="fas fa-cog mr-1"></i> Sistem İşlemleri
            </button>
            <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
    <a class="dropdown-item" href="#" id="trigger-sync-btn"><i class="fas fa-sync mr-1"></i> Kartları Cihaza Senkronize Et</a>
    <a class="dropdown-item" href="#" id="backup-btn"><i class="fas fa-database mr-1"></i> Veritabanı Yedekle</a>
    <a class="dropdown-item" href="leave_management.php"><i class="fas fa-calendar-alt mr-1"></i> İzin Yönetimi</a>
    <div class="dropdown-divider"></div>
    <a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt mr-1"></i> Çıkış Yap</a>
</div>
        </div>
        <div class="dropdown d-inline-block">
            <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="themeDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <i class="fas fa-palette mr-1"></i> Tema
            </button>
            <div class="dropdown-menu" aria-labelledby="themeDropdown">
                <a class="dropdown-item change-theme" href="#" data-theme="light"><i class="fas fa-sun mr-1"></i> Açık Tema</a>
                <a class="dropdown-item change-theme" href="#" data-theme="dark"><i class="fas fa-moon mr-1"></i> Koyu Tema</a>
                <a class="dropdown-item change-theme" href="#" data-theme="blue"><i class="fas fa-tint mr-1"></i> Mavi Tema</a>
                <a class="dropdown-item change-theme" href="#" data-theme="green"><i class="fas fa-leaf mr-1"></i> Yeşil Tema</a>
                <a class="dropdown-item change-theme" href="#" data-theme="red"><i class="fas fa-heart mr-1"></i> Kırmızı Tema</a>
            </div>
        </div>
        <span class="ml-3 badge badge-info">
            <?php echo $_SESSION['user_name']; ?> olarak giriş yapıldı
        </span>
    </div>
</div>

    <div class="container main-container">
        <!-- Ana Sekmeler -->
        <ul class="nav nav-tabs" id="myTab" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" id="dashboard-tab" data-toggle="tab" href="#dashboard" role="tab">
                    <i class="fas fa-tachometer-alt mr-1"></i> Gösterge Paneli
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="cards-tab" data-toggle="tab" href="#cards" role="tab">
                    <i class="fas fa-address-card mr-1"></i> Personel Kartları
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="attendance-tab" data-toggle="tab" href="#attendance" role="tab">
                    <i class="fas fa-clock mr-1"></i> Giriş-Çıkış Kayıtları
                </a>
            </li>
            <li class="nav-item">
    <a class="nav-link" id="absence-tab" data-toggle="tab" href="#absence" role="tab">
        <i class="fas fa-user-times mr-1"></i> Devamsızlık Takibi
    </a>
</li>
            <li class="nav-item">
    <a class="nav-link" id="leave-tab" data-toggle="tab" href="#leave" role="tab">
        <i class="fas fa-calendar-alt mr-1"></i> İzin Yönetimi
    </a>
</li>
             <li class="nav-item">
        <a class="nav-link" id="salary-tab" data-toggle="tab" href="#salary" role="tab">
            <i class="fas fa-money-bill-wave mr-1"></i> Maaş Yönetimi
        </a>
    </li>
            <li class="nav-item">
                <a class="nav-link" id="logs-tab" data-toggle="tab" href="#logs" role="tab">
                    <i class="fas fa-list-alt mr-1"></i> Kart Logları
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="reports-tab" data-toggle="tab" href="#reports" role="tab">
                    <i class="fas fa-chart-bar mr-1"></i> Raporlar
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="settings-tab" data-toggle="tab" href="#settings" role="tab">
                    <i class="fas fa-cogs mr-1"></i> Sistem Ayarları
                </a>
            </li>
        </ul>

        <!-- Sekme İçerikleri -->
        <div class="tab-content" id="myTabContent">
            <!-- Gösterge Paneli Sekmesi -->
            <div class="tab-pane fade show active" id="dashboard" role="tabpanel">
                <h3 class="mb-4">Gösterge Paneli</h3>
                
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card bg-primary text-white dashboard-card">
                            <div class="dashboard-number" id="total-personnel">
                                <?php
                                try {
                                    $stmt = $conn->query("SELECT COUNT(*) FROM cards");
                                    echo $stmt->fetchColumn();
                                } catch(PDOException $e) {
                                    echo "0";
                                }
                                ?>
                            </div>
                            <div class="dashboard-label">Toplam Personel</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white dashboard-card">
                            <div class="dashboard-number" id="currently-inside">
                                <?php
                                try {
                                    $stmt = $conn->prepare("
                                        SELECT COUNT(DISTINCT t1.card_number)
                                        FROM attendance_logs t1
                                        WHERE t1.event_type = 'ENTRY'
                                        AND t1.event_time = (
                                            SELECT MAX(t2.event_time)
                                            FROM attendance_logs t2
                                            WHERE t2.card_number = t1.card_number
                                        )
                                    ");
                                    $stmt->execute();
                                    echo $stmt->fetchColumn();
                                } catch(PDOException $e) {
                                    echo "0";
                                }
                                ?>
                            </div>
                            <div class="dashboard-label">İçeride Bulunanlar</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-info text-white dashboard-card">
                            <div class="dashboard-number" id="today-entries">
                                <?php
                                try {
                                    $today = date('Y-m-d');
                                    $stmt = $conn->prepare("SELECT COUNT(DISTINCT card_number) FROM attendance_logs WHERE DATE(event_time) = :today AND event_type = 'ENTRY'");
                                    $stmt->bindParam(':today', $today);
                                    $stmt->execute();
                                    echo $stmt->fetchColumn();
                                } catch(PDOException $e) {
                                    echo "0";
                                }
                                ?>
                            </div>
                            <div class="dashboard-label">Bugün Giriş Yapanlar</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-warning text-white dashboard-card">
                            <div class="dashboard-number" id="today-exits">
                                <?php
                                try {
                                    $today = date('Y-m-d');
                                    $stmt = $conn->prepare("SELECT COUNT(DISTINCT card_number) FROM attendance_logs WHERE DATE(event_time) = :today AND event_type = 'EXIT'");
                                    $stmt->bindParam(':today', $today);
                                    $stmt->execute();
                                    echo $stmt->fetchColumn();
                                } catch(PDOException $e) {
                                    echo "0";
                                }
                                ?>
                            </div>
                            <div class="dashboard-label">Bugün Çıkış Yapanlar</div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header bg-light">
                                <i class="fas fa-chart-bar mr-1"></i> Son Giriş-Çıkış Hareketleri
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped table-sm">
                                        <thead>
                                            <tr>
                                                <th>İsim</th>
                                                <th>İşlem Tipi</th>
                                                <th>Tarih/Saat</th>
                                                <th>Cihaz</th>
                                            </tr>
                                        </thead>
                                        <tbody id="recent-activities">
                                            <?php
                                            try {
                                                $stmt = $conn->prepare("
                                                    SELECT a.*, c.name, c.photo_path 
                                                    FROM attendance_logs a
                                                    LEFT JOIN cards c ON a.card_number = c.card_number
                                                    ORDER BY a.event_time DESC LIMIT 10
                                                ");
                                                $stmt->execute();
                                                $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                                foreach($activities as $activity) {
                                                    $photoPath = !empty($activity['photo_path']) ? $activity['photo_path'] : 'uploads/default-user.png';
                                                    echo "<tr>";
                                                    echo "<td>";
                                                    echo "<img src='".$photoPath."' class='user-photo-small mr-2' alt='Profil'>";
                                                    echo (!empty($activity['name']) ? $activity['name'] : 'Bilinmeyen Kullanıcı');
                                                    echo "</td>";
                                                    $eventTypeText = ($activity['event_type'] == 'ENTRY') ? 'Giriş' : 'Çıkış';
                                                    $eventTypeClass = ($activity['event_type'] == 'ENTRY') ? 'success' : 'danger';
                                                    echo "<td><span class='badge badge-".$eventTypeClass."'>".$eventTypeText."</span></td>";
                                                    echo "<td>".date('d.m.Y H:i', strtotime($activity['event_time']))."</td>";
                                                    echo "<td>Cihaz #".$activity['device_id']."</td>";
                                                    echo "</tr>";
                                                }
                                            } catch(PDOException $e) {
                                                echo "<tr><td colspan='4' class='text-danger'>Veritabanı hatası: " . $e->getMessage() . "</td></tr>";
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header bg-light">
                                <i class="fas fa-bell mr-1"></i> Son Kart Okutmaları
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Kart No</th>
                                                <th>Zaman</th>
                                            </tr>
                                        </thead>
                                        <tbody id="recent-scans">
                                            <?php
                                            try {
                                                $stmt = $conn->prepare("
                                                    SELECT * FROM card_logs
                                                    ORDER BY scan_time DESC LIMIT 10
                                                ");
                                                $stmt->execute();
                                                $scans = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                                foreach($scans as $scan) {
                                                    echo "<tr>";
                                                    echo "<td><span class='badge badge-info'>".$scan['card_number']."</span></td>";
                                                    echo "<td>".date('H:i:s', strtotime($scan['scan_time']))."</td>";
                                                    echo "</tr>";
                                                }
                                            } catch(PDOException $e) {
                                                echo "<tr><td colspan='2' class='text-danger'>Veritabanı hatası: " . $e->getMessage() . "</td></tr>";
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card mt-3">
                            <div class="card-header bg-light">
                                <i class="fas fa-user-plus mr-1"></i> Hızlı İşlemler
                            </div>
                            <div class="card-body">
                                <a href="#" class="btn btn-primary btn-block mb-2" id="quick-add-card">
                                    <i class="fas fa-plus-circle mr-1"></i> Yeni Personel Ekle
                                </a>
                                <a href="#" class="btn btn-info btn-block mb-2" id="quick-scan-card">
                                    <i class="fas fa-id-card mr-1"></i> Kart Okut
                                </a>
                                <a href="#" class="btn btn-success btn-block" id="quick-reports">
                                    <i class="fas fa-file-alt mr-1"></i> Raporlar
                                </a>
                                <a href="absence_reports_page.php" class="btn btn-secondary btn-block mb-2">
    <i class="fas fa-chart-bar mr-1"></i> Devamsızlık Raporları
</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Devamsızlık Takibi Sekmesi -->
<div class="tab-pane fade" id="absence" role="tabpanel">
    <h3 class="mb-4">Devamsızlık Takibi</h3>
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-warning text-white">
                    <h5 class="mb-0"><i class="fas fa-calendar-day mr-1"></i> Bugünkü Devamsızlar</h5>
                </div>
                <div class="card-body" id="today-absences-container">
                    <div class="text-center">
                        <div class="spinner-border text-warning" role="status">
                            <span class="sr-only">Yükleniyor...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0"><i class="fas fa-chart-line mr-1"></i> Devamsızlık İstatistikleri</h5>
                </div>
                <div class="card-body" id="absence-stats-container">
                    <div class="text-center">
                        <div class="spinner-border text-danger" role="status">
                            <span class="sr-only">Yükleniyor...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-cog mr-1"></i> Devamsızlık Yönetimi</h5>
                </div>
                <div class="card-body">
                    <p>Detaylı devamsızlık yönetimi için aşağıdaki butona tıklayabilirsiniz:</p>
                    <a href="attendance_tracking.php" class="btn btn-info">
                        <i class="fas fa-external-link-alt mr-1"></i> Devamsızlık Takip Sayfasına Git
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
            <!-- İzin Yönetimi Sekmesi -->
<div class="tab-pane fade" id="leave" role="tabpanel">
    <h3 class="mb-4">İzin Yönetimi</h3>
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-hourglass-half mr-1"></i> Bekleyen İzin Talepleri</h5>
                </div>
                <div class="card-body" id="pending-leaves-container">
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="sr-only">Yükleniyor...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-calculator mr-1"></i> İzin Bakiyeleri</h5>
                </div>
                <div class="card-body" id="leave-balances-container">
                    <div class="text-center">
                        <div class="spinner-border text-success" role="status">
                            <span class="sr-only">Yükleniyor...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-cog mr-1"></i> İzin Yönetim Sayfası</h5>
                </div>
                <div class="card-body">
                    <p>Detaylı izin yönetimi için aşağıdaki butona tıklayabilirsiniz:</p>
                    <a href="leave_management.php" class="btn btn-info">
                        <i class="fas fa-external-link-alt mr-1"></i> İzin Yönetim Sayfasına Git
                    </a>
                    <a href="leave_reports.php" class="btn btn-outline-info mr-2">
    <i class="fas fa-chart-bar mr-1"></i> İzin Raporları
</a>
                </div>
            </div>
        </div>
    </div>
</div>

            <!-- Personel Kartları Sekmesi -->
            <div class="tab-pane fade" id="cards" role="tabpanel">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3>Personel Kartları</h3>
                    <div class="btn-group">
                        <button class="btn btn-primary" id="add-new-card-btn">
                            <i class="fas fa-plus-circle mr-1"></i> Yeni Personel Ekle
                        </button>
                        <button class="btn btn-warning" id="sync-cards-btn">
                            <i class="fas fa-sync mr-1"></i> Kartları Senkronize Et
                        </button>
                    </div>
                </div>
                
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                                    </div>
                                    <input type="text" class="form-control" id="search-cards" placeholder="Ara... (Ad, Kart No, Departman)">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <select class="form-control" id="filter-department">
                                    <option value="">Tüm Departmanlar</option>
                                    <?php
                                    try {
                                        $stmt = $conn->query("SELECT DISTINCT department FROM cards WHERE department != '' ORDER BY department");
                                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                            echo '<option value="'.$row['department'].'">'.$row['department'].'</option>';
                                        }
                                    } catch(PDOException $e) {
                                        // Hata yönetimi
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered">
                                <thead class="thead-dark">
                                    <tr>
                                        <th width="5%">ID</th>
                                        <th width="10%">Fotoğraf</th>
                                        <th width="20%">Ad Soyad</th>
                                        <th width="15%">Departman</th>
                                        <th width="15%">Kart Numarası</th>
                                        <th width="10%">Yetki</th>
                                        <th width="10%">Durum</th>
                                        <th width="15%">İşlemler</th>
                                    </tr>
                                </thead>
                                <tbody id="cards-table-body">
                                    <?php
                                    try {
                                        $stmt = $conn->prepare("SELECT * FROM cards ORDER BY id DESC");
                                        $stmt->execute();
                                        $cards = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                        foreach($cards as $card) {
                                            $photoPath = !empty($card['photo_path']) ? $card['photo_path'] : 'uploads/default-user.png';
                                            $fullName = $card['name'] . ' ' . $card['surname'];
                                            
                                            echo "<tr>";
                                            echo "<td>".$card['user_id']."</td>";
                                            echo "<td><img src='".$photoPath."' class='user-photo-small' alt='Profil'></td>";
                                            echo "<td>".$fullName."</td>";
                                            echo "<td>".(!empty($card['department']) ? $card['department'] : '-')."</td>";
                                            echo "<td>".$card['card_number']."</td>";
                                            
                                            // Yetki seviyesi
                                            $privilegeText = '';
                                            switch($card['privilege']) {
                                                case '0': $privilegeText = 'Normal Kullanıcı'; break;
                                                case '1': $privilegeText = 'Kayıt Yetkilisi'; break;
                                                case '2': $privilegeText = 'Yönetici'; break;
                                                case '3': $privilegeText = 'Süper Admin'; break;
                                                default: $privilegeText = 'Bilinmiyor';
                                            }
                                            echo "<td>".$privilegeText."</td>";
                                            
                                            // Durum
                                            echo "<td>".($card['enabled'] == 'true' ? '<span class="badge badge-success badge-status">Aktif</span>' : '<span class="badge badge-danger badge-status">Pasif</span>')."</td>";
                                            
                                            // İşlem butonları
                                            echo "<td>
                                                <button class='btn btn-sm btn-info action-btn view-details' data-user-id='".$card['user_id']."' title='Detay'><i class='fas fa-eye'></i></button>
                                                <button class='btn btn-sm btn-primary action-btn edit-user' data-user-id='".$card['user_id']."' title='Düzenle'><i class='fas fa-edit'></i></button>
                                                <button class='btn btn-sm btn-danger action-btn delete-user' data-user-id='".$card['user_id']."' title='Sil'><i class='fas fa-trash'></i></button>
                                              </td>";
                                            echo "</tr>";
                                        }
                                    } catch(PDOException $e) {
                                        echo "<tr><td colspan='8' class='text-danger'>Veritabanı bağlantı hatası: " . $e->getMessage() . "</td></tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Giriş-Çıkış Kayıtları Sekmesi -->
            <div class="tab-pane fade" id="attendance" role="tabpanel">
                <h3 class="mb-4">Giriş-Çıkış Kayıtları</h3>
                
                <!-- Özet Kartları -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card bg-primary text-white dashboard-card">
                            <div class="dashboard-number" id="today-entries-att">
                                <?php
                                try {
                                    $today = date('Y-m-d');
                                    $stmt = $conn->prepare("SELECT COUNT(DISTINCT card_number) FROM attendance_logs WHERE DATE(event_time) = :today AND event_type = 'ENTRY'");
                                    $stmt->bindParam(':today', $today);
                                    $stmt->execute();
                                    echo $stmt->fetchColumn();
                                } catch(PDOException $e) {
                                    echo "0";
                                }
                                ?>
                            </div>
                            <div class="dashboard-label">Bugün Giriş Yapanlar</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-danger text-white dashboard-card">
                            <div class="dashboard-number" id="today-exits-att">
                                <?php
                                try {
                                    $stmt = $conn->prepare("SELECT COUNT(DISTINCT card_number) FROM attendance_logs WHERE DATE(event_time) = :today AND event_type = 'EXIT'");
                                    $stmt->bindParam(':today', $today);
                                    $stmt->execute();
                                    echo $stmt->fetchColumn();
                                } catch(PDOException $e) {
                                    echo "0";
                                }
                                ?>
                            </div>
                            <div class="dashboard-label">Bugün Çıkış Yapanlar</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-success text-white dashboard-card">
                            <div class="dashboard-number" id="currently-inside-att">
                                <?php
                                try {
                                    $stmt = $conn->prepare("
                                        SELECT COUNT(DISTINCT t1.card_number)
                                        FROM attendance_logs t1
                                        WHERE t1.event_type = 'ENTRY'
                                        AND t1.event_time = (
                                            SELECT MAX(t2.event_time)
                                            FROM attendance_logs t2
                                            WHERE t2.card_number = t1.card_number
                                        )
                                    ");
                                    $stmt->execute();
                                    echo $stmt->fetchColumn();
                                } catch(PDOException $e) {
                                    echo "0";
                                }
                                ?>
                            </div>
                            <div class="dashboard-label">İçeride Bulunanlar</div>
                        </div>
                    </div>
                </div>
                
                <!-- Filtreleme -->
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h5>Filtreleme</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 mb-2">
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                                    </div>
                                    <input type="text" id="search-attendance" class="form-control" placeholder="Ara (Ad, Kart No)">
                                </div>
                            </div>
                            <div class="col-md-3 mb-2">
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
                                    </div>
                                    <input type="date" id="date-filter" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                                </div>
                            </div>
                            <div class="col-md-3 mb-2">
                                <select id="type-filter" class="form-control">
                                    <option value="">Tüm İşlemler</option>
                                    <option value="ENTRY">Giriş</option>
                                    <option value="EXIT">Çıkış</option>
                                </select>
                            </div>
                            <div class="col-md-2 mb-2">
                                <div class="dropdown">
                                    <button class="btn btn-success dropdown-toggle btn-block" type="button" id="exportDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                        <i class="fas fa-file-export mr-1"></i> Dışa Aktar
                                    </button>
                                    <div class="dropdown-menu dropdown-menu-right" aria-labelledby="exportDropdown">
                                        <a class="dropdown-item export-format" href="#" data-format="excel">
                                            <i class="fas fa-file-excel mr-1"></i> Excel Formatı
                                        </a>
                                        <a class="dropdown-item export-format" href="#" data-format="pdf">
                                            <i class="fas fa-file-pdf mr-1"></i> PDF Formatı
                                        </a>
                                        <a class="dropdown-item export-format" href="#" data-format="csv">
                                            <i class="fas fa-file-csv mr-1"></i> CSV Formatı
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Giriş-Çıkış Tablosu -->
                <div class="card">
                    <div class="card-header bg-light">
                        <h5>Giriş-Çıkış Kayıtları</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered">
                                <thead class="thead-dark">
                                    <tr>
                                        <th>ID</th>
                                        <th>Fotoğraf</th>
                                        <th>Kullanıcı Adı</th>
                                        <th>Kart Numarası</th>
                                        <th>İşlem Tipi</th>
                                        <th>Tarih/Saat</th>
                                        <th>Cihaz</th>
                                    </tr>
                                </thead>
                                <tbody id="attendance-tbody">
                                    <?php
                                    try {
                                        $query = "
                                            SELECT a.*, c.name, c.surname, c.photo_path 
                                            FROM attendance_logs a
                                            LEFT JOIN cards c ON a.card_number = c.card_number
                                            ORDER BY a.event_time DESC
                                            LIMIT 500
                                        ";
                                        $stmt = $conn->prepare($query);
                                        $stmt->execute();
                                        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                        foreach($logs as $log) {
                                            $photoPath = !empty($log['photo_path']) ? $log['photo_path'] : 'uploads/default-user.png';
                                            $fullName = !empty($log['name']) ? $log['name'] . ' ' . $log['surname'] : 'Bilinmeyen Kullanıcı';
                                            
                                            echo "<tr>";
                                            echo "<td>".$log['id']."</td>";
                                            echo "<td><img src='".$photoPath."' class='user-photo-small' alt='Profil'></td>";
                                            echo "<td>".$fullName."</td>";
                                            echo "<td>".$log['card_number']."</td>";
                                            
                                            // İşlem tipini renkli göster
                                            $eventTypeText = ($log['event_type'] == 'ENTRY') ? 'Giriş' : 'Çıkış';
                                            $eventTypeClass = ($log['event_type'] == 'ENTRY') ? 'success' : 'danger';
                                            echo "<td><span class='badge badge-".$eventTypeClass."'>".$eventTypeText."</span></td>";
                                            
                                            echo "<td>".date('d.m.Y H:i:s', strtotime($log['event_time']))."</td>";
                                            echo "<td>Cihaz #".$log['device_id']."</td>";
                                            echo "</tr>";
                                        }
                                    } catch(PDOException $e) {
                                        echo "<tr><td colspan='7' class='text-danger'>Veritabanı sorgu hatası: " . $e->getMessage() . "</td></tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Maaş Yönetimi Sekmesi -->
<div class="tab-pane fade" id="salary" role="tabpanel">
    <h3 class="mb-4">Maaş Yönetimi</h3>
    
    <!-- Maaş Sistem Kartları -->
    <div class="row">
        <!-- Maaş Hesaplama Kartı -->
        <div class="col-md-4">
            <div class="card dashboard-card h-100">
                <div class="card-body text-center">
                    <i class="fas fa-calculator fa-3x text-success mb-3"></i>
                    <h5>Maaş Hesaplama</h5>
                    <p class="text-muted">Personel maaşlarını hesaplayın ve devam kontrolü yapın</p>
                    <a href="salary_management.php" class="btn btn-success btn-block">
                        <i class="fas fa-external-link-alt mr-1"></i> Maaş Yönetimine Git
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Resmi Tatil Yönetimi Kartı -->
        <div class="col-md-4">
            <div class="card dashboard-card h-100">
                <div class="card-body text-center">
                    <i class="fas fa-calendar-times fa-3x text-warning mb-3"></i>
                    <h5>Resmi Tatiller</h5>
                    <p class="text-muted">Resmi tatil günlerini tanımlayın ve yönetin</p>
                    <a href="holiday_management.php" class="btn btn-warning btn-block">
                        <i class="fas fa-external-link-alt mr-1"></i> Tatil Yönetimine Git
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Hızlı Maaş Hesaplama Widget -->
        <div class="col-md-4">
            <div class="card dashboard-card h-100">
                <div class="card-header bg-info text-white">
                    <i class="fas fa-tachometer-alt mr-1"></i> Hızlı Hesaplama
                </div>
                <div class="card-body">
                    <form id="quick-salary-calc">
                        <div class="form-group">
                            <label for="quick_user_select">Personel</label>
                            <select class="form-control form-control-sm" id="quick_user_select" name="user_id" required>
                                <option value="">Seçiniz</option>
                                <?php
                                // Kullanıcıları çek
                                try {
                                    $stmt = $conn->query("SELECT user_id, name, surname FROM cards WHERE enabled = 'true' ORDER BY name");
                                    while($user = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                        echo '<option value="'.$user['user_id'].'">'.$user['name'].' '.$user['surname'].'</option>';
                                    }
                                } catch(PDOException $e) {
                                    echo '<option value="">Veri alınamadı</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="quick_month">Ay</label>
                            <input type="month" class="form-control form-control-sm" id="quick_month" name="month" value="<?php echo date('Y-m'); ?>">
                        </div>
                        <button type="submit" class="btn btn-info btn-sm btn-block">
                            <i class="fas fa-calculator mr-1"></i> Hesapla
                        </button>
                    </form>
                    <div id="quick-salary-result" style="display: none; margin-top: 10px;"></div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Maaş İstatistikleri -->
    <div class="row mt-4">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-secondary text-white">
                    <i class="fas fa-chart-bar mr-1"></i> Bu Ay Maaş İstatistikleri
                </div>
                <div class="card-body">
                    <div id="salary-stats">
                        <!-- AJAX ile yüklenecek -->
                        <div class="text-center">
                            <div class="spinner-border text-secondary" role="status">
                                <span class="sr-only">Yükleniyor...</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Sistem Ayarları Özeti -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-dark text-white">
                    <i class="fas fa-cogs mr-1"></i> Sistem Ayarları
                </div>
                <div class="card-body">
                    <div id="salary-settings-summary">
                        <!-- AJAX ile yüklenecek -->
                        <div class="text-center">
                            <div class="spinner-border text-dark" role="status">
                                <span class="sr-only">Yükleniyor...</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Personel Maaş Durumu -->
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <i class="fas fa-users mr-1"></i> Personel Maaş Durumu
                        </div>
                        <div>
                            <button type="button" class="btn btn-sm btn-light" onclick="refreshSalaryOverview()">
                                <i class="fas fa-sync mr-1"></i> Yenile
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <select class="form-control form-control-sm" id="salary-filter-department">
                                <option value="">Tüm Departmanlar</option>
                                <?php
                                try {
                                    $stmt = $conn->query("SELECT DISTINCT department FROM cards WHERE department != '' ORDER BY department");
                                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                        echo '<option value="'.$row['department'].'">'.$row['department'].'</option>';
                                    }
                                } catch(PDOException $e) {
                                    // Hata yönetimi
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <input type="month" class="form-control form-control-sm" id="salary-filter-month" value="<?php echo date('Y-m'); ?>">
                        </div>
                        <div class="col-md-4">
                            <button type="button" class="btn btn-primary btn-sm" onclick="filterSalaryOverview()">
                                <i class="fas fa-filter mr-1"></i> Filtrele
                            </button>
                        </div>
                    </div>
                    <div id="salary-overview-table">
                        <!-- AJAX ile yüklenecek -->
                        <div class="text-center">
                            <div class="spinner-border text-primary" role="status">
                                <span class="sr-only">Yükleniyor...</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

            <!-- Kart Logları Sekmesi -->
            <div class="tab-pane fade" id="logs" role="tabpanel">
                <h3 class="mb-4">Kart Okutma Logları</h3>
                
                <div class="card">
                    <div class="card-header bg-light">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                                    </div>
                                    <input type="text" id="search-logs" class="form-control" placeholder="Ara (Kart No, Zaman)">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <input type="date" id="date-filter-logs" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                            </div>
                            <div class="col-md-2">
                                <button id="clear-log-filters" class="btn btn-secondary btn-block">Filtreyi Temizle</button>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered">
                                <thead class="thead-dark">
                                    <tr>
                                        <th>ID</th>
                                        <th>Kart Numarası</th>
                                        <th>Okutma Zamanı</th>
                                        <th>Kayıt Zamanı</th>
                                        <th>Kullanıcı Bilgisi</th>
                                    </tr>
                                </thead>
                                <tbody id="logs-table-body">
                                    <?php
                                    try {
                                        // Kart loglarını sorgula
                                        $stmt = $conn->prepare("
                                            SELECT l.*, c.name, c.surname, c.photo_path, c.user_id
                                            FROM card_logs l
                                            LEFT JOIN cards c ON l.card_number = c.card_number
                                            ORDER BY l.scan_time DESC
                                            LIMIT 500
                                        ");
                                        $stmt->execute();
                                        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                        foreach($logs as $log) {
                                            $fullName = !empty($log['name']) ? $log['name'] . ' ' . $log['surname'] : 'Bilinmeyen';
                                            echo "<tr>";
                                            echo "<td>".$log['id']."</td>";
                                            echo "<td><span class='badge badge-info'>".$log['card_number']."</span></td>";
                                            echo "<td>".date('d.m.Y H:i:s', strtotime($log['scan_time']))."</td>";
                                            echo "<td>".date('d.m.Y H:i:s', strtotime($log['created_at']))."</td>";
                                            if (!empty($log['name'])) {
                                                echo "<td>".$fullName." (ID: ".$log['user_id'].")</td>";
                                            } else {
                                                echo "<td><span class='text-muted'>Kullanıcı bulunamadı</span></td>";
                                            }
                                            echo "</tr>";
                                        }
                                    } catch(PDOException $e) {
                                        echo "<tr><td colspan='5' class='text-danger'>Veritabanı sorgu hatası: " . $e->getMessage() . "</td></tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Raporlar Sekmesi -->
            <div class="tab-pane fade" id="reports" role="tabpanel">
                <h3 class="mb-4">Raporlar</h3>
                
                <div class="row">
                    <div class="col-md-4 mb-4">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0"><i class="fas fa-calendar-day mr-1"></i> Günlük Rapor</h5>
                            </div>
                            <div class="card-body">
                                <p>Seçilen güne ait personel giriş-çıkış raporunu görüntüleyin.</p>
                                <form action="reports.php" method="get" target="_blank">
                                    <input type="hidden" name="type" value="daily">
                                    <div class="form-group">
                                        <label for="daily-date">Tarih</label>
                                        <input type="date" class="form-control" id="daily-date" name="date_start" value="<?php echo date('Y-m-d'); ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="daily-department">Departman (Opsiyonel)</label>
                                        <select class="form-control" id="daily-department" name="department">
                                            <option value="">Tüm Departmanlar</option>
                                            <?php
                                            try {
                                                $stmt = $conn->query("SELECT DISTINCT department FROM cards WHERE department != '' ORDER BY department");
                                                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                                    echo '<option value="'.$row['department'].'">'.$row['department'].'</option>';
                                                }
                                            } catch(PDOException $e) {
                                                // Hata yönetimi
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    <button type="submit" class="btn btn-primary btn-block">
                                        <i class="fas fa-search mr-1"></i> Raporu Görüntüle
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4 mb-4">
                        <div class="card">
                            <div class="card-header bg-success text-white">
                                <h5 class="mb-0"><i class="fas fa-calendar-alt mr-1"></i> Aylık Rapor</h5>
                            </div>
                            <div class="card-body">
                                <p>Seçilen aya ait personel mesai özet raporunu görüntüleyin.</p>
                                <form action="reports.php" method="get" target="_blank">
                                    <input type="hidden" name="type" value="monthly">
                                    <div class="form-group">
                                        <label for="monthly-date">Ay</label>
                                        <input type="month" class="form-control" id="monthly-date" name="date_start" value="<?php echo date('Y-m'); ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="monthly-department">Departman (Opsiyonel)</label>
                                        <select class="form-control" id="monthly-department" name="department">
                                            <option value="">Tüm Departmanlar</option>
                                            <?php
                                            try {
                                                $stmt = $conn->query("SELECT DISTINCT department FROM cards WHERE department != '' ORDER BY department");
                                                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                                    echo '<option value="'.$row['department'].'">'.$row['department'].'</option>';
                                                }
                                            } catch(PDOException $e) {
                                                // Hata yönetimi
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    <button type="submit" class="btn btn-success btn-block">
                                        <i class="fas fa-search mr-1"></i> Raporu Görüntüle
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4 mb-4">
                        <div class="card">
                            <div class="card-header bg-info text-white">
                                <h5 class="mb-0"><i class="fas fa-user-clock mr-1"></i> Personel Raporu</h5>
                            </div>
                            <div class="card-body">
                                <p>Seçilen personelin giriş-çıkış kayıtlarını görüntüleyin.</p>
                                <form action="reports.php" method="get" target="_blank">
                                    <input type="hidden" name="type" value="user">
                                    <div class="form-group">
                                        <label for="user-id">Personel</label>
                                        <select class="form-control" id="user-id" name="user_id" required>
                                            <option value="">Personel Seçin</option>
                                            <?php
                                            try {
                                                $stmt = $conn->query("SELECT user_id, name, surname FROM cards ORDER BY name, surname");
                                                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                                    echo '<option value="'.$row['user_id'].'">'.$row['name'].' '.$row['surname'].'</option>';
                                                }
                                            } catch(PDOException $e) {
                                                // Hata yönetimi
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="user-date-start">Başlangıç Tarihi</label>
                                        <input type="date" class="form-control" id="user-date-start" name="date_start" value="<?php echo date('Y-m-d', strtotime('-30 days')); ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="user-date-end">Bitiş Tarihi</label>
                                        <input type="date" class="form-control" id="user-date-end" name="date_end" value="<?php echo date('Y-m-d'); ?>" required>
                                    </div>
                                    <button type="submit" class="btn btn-info btn-block">
                                        <i class="fas fa-search mr-1"></i> Raporu Görüntüle
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-4 mb-4">
                        <div class="card">
                            <div class="card-header bg-warning text-white">
                                <h5 class="mb-0"><i class="fas fa-building mr-1"></i> Departman Raporu</h5>
                            </div>
                            <div class="card-body">
                                <p>Seçilen tarih aralığında departmanlara göre çalışma sürelerini görüntüleyin.</p>
                                <form action="reports.php" method="get" target="_blank">
                                    <input type="hidden" name="type" value="department">
                                    <div class="form-group">
                                        <label for="dept-date-start">Başlangıç Tarihi</label>
                                        <input type="date" class="form-control" id="dept-date-start" name="date_start" value="<?php echo date('Y-m-d', strtotime('-30 days')); ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="dept-date-end">Bitiş Tarihi</label>
                                        <input type="date" class="form-control" id="dept-date-end" name="date_end" value="<?php echo date('Y-m-d'); ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="dept-select">Departman (Opsiyonel)</label>
                                        <select class="form-control" id="dept-select" name="department">
                                            <option value="">Tüm Departmanlar</option>
                                            <?php
                                            try {
                                                $stmt = $conn->query("SELECT DISTINCT department FROM cards WHERE department != '' ORDER BY department");
                                                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                                    echo '<option value="'.$row['department'].'">'.$row['department'].'</option>';
                                                }
                                            } catch(PDOException $e) {
                                                // Hata yönetimi
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    <button type="submit" class="btn btn-warning btn-block">
                                        <i class="fas fa-search mr-1"></i> Raporu Görüntüle
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-8 mb-4">
                        <div class="card h-100">
                            <div class="card-header bg-secondary text-white">
                                <h5 class="mb-0"><i class="fas fa-info-circle mr-1"></i> Raporlar Hakkında</h5>
                            </div>
                            <div class="card-body">
                                <h5>Rapor Türleri</h5>
                                <ul>
                                    <li><strong>Günlük Rapor:</strong> Seçilen günde personelin ilk giriş, son çıkış ve toplam çalışma süresi bilgilerini gösterir.</li>
                                    <li><strong>Aylık Rapor:</strong> Seçilen ayda personelin çalıştığı gün sayısı ve toplam çalışma süresi bilgilerini gösterir.</li>
                                    <li><strong>Personel Raporu:</strong> Seçilen personelin belirtilen tarih aralığındaki günlük giriş-çıkış ve çalışma süresi bilgilerini gösterir.</li>
                                    <li><strong>Departman Raporu:</strong> Departmanlara göre çalışan sayısı ve toplam çalışma süresi istatistiklerini gösterir.</li>
                                </ul>
                                
                                <h5>Dışa Aktarma</h5>
                                <p>Tüm raporlar Excel, PDF veya CSV formatlarında dışa aktarılabilir. Rapor sayfasının üst kısmındaki ilgili butonları kullanabilirsiniz.</p>
                                
                                <h5>İpuçları</h5>
                                <ul>
                                    <li>Daha detaylı filtreler için departman seçeneğini kullanabilirsiniz.</li>
                                    <li>Personel raporunda, tarih aralığını geniş tutarak geçmiş çalışma düzenini inceleyebilirsiniz.</li>
                                    <li>Excel formatında indirilen raporlarda renklendirme ve biçimlendirme yapılır, daha detaylı analiz için bu format önerilir.</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sistem Ayarları Sekmesi -->
            <div class="tab-pane fade" id="settings" role="tabpanel">
                <h3 class="mb-4">Sistem Ayarları</h3>
                
                <div class="row">
                    <!-- Kart İşlemleri Kartı -->
                    <div class="col-md-6">
                        <div class="card mb-4">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0"><i class="fas fa-id-card mr-1"></i> Kart İşlemleri</h5>
                            </div>
                            <div class="card-body">
                                <div class="list-group">
                                    <a href="#" class="list-group-item list-group-item-action" id="new-card-btn">
                                        <i class="fas fa-plus-circle mr-1"></i> Yeni Kart Kaydı
                                    </a>
                                    <a href="#" class="list-group-item list-group-item-action" id="scan-card-btn">
                                        <i class="fas fa-id-badge mr-1"></i> Kart Okut
                                    </a>
                                    <a href="#" class="list-group-item list-group-item-action" id="sync-devices-btn">
                                        <i class="fas fa-sync mr-1"></i> Cihazları Senkronize Et
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Kullanıcı Silme Kartı -->
                    <div class="col-md-6">
                        <div class="card mb-4">
                            <div class="card-header bg-danger text-white">
                                <h5 class="mb-0"><i class="fas fa-trash mr-1"></i> Kullanıcı Silme İşlemleri</h5>
                            </div>
                            <div class="card-body">
                                <div id="delete-message-area"></div>
                                
                                <!-- Tek Kullanıcı Silme Formu -->
                                <form id="single-delete-form" class="mb-4">
                                    <h6 class="text-danger">Tek Kullanıcı Silme</h6>
                                    <div class="form-group">
                                        <label for="user_id_to_delete">Kullanıcı ID</label>
                                        <select class="form-control" id="user_id_to_delete" name="user_id" required>
                                            <option value="">Kullanıcı Seçin</option>
                                            <?php
                                            try {
                                                $stmt = $conn->prepare("SELECT user_id, name, surname FROM cards ORDER BY user_id");
                                                $stmt->execute();
                                                $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                                foreach($users as $user) {
                                                    $fullName = $user['name'] . ' ' . $user['surname'];
                                                    echo '<option value="'.$user['user_id'].'">'.$user['user_id'].' - '.$fullName.'</option>';
                                                }
                                            } catch(PDOException $e) {
                                                echo '<option value="">Veritabanı hatası: '.$e->getMessage().'</option>';
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    <div class="form-group">
    <label for="device_only_user_id">Sadece Cihazdan Silme</label>
    <select class="form-control" id="device_only_user_id" name="device_only_user_id" required>
        <option value="">Kullanıcı Seçin</option>
        <?php
        try {
            $stmt = $conn->prepare("SELECT user_id, name, surname FROM cards ORDER BY user_id");
            $stmt->execute();
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach($users as $user) {
                $fullName = $user['name'] . ' ' . $user['surname'];
                echo '<option value="'.$user['user_id'].'">'.$user['user_id'].' - '.$fullName.'</option>';
            }
        } catch(PDOException $e) {
            echo '<option value="">Veritabanı hatası: '.$e->getMessage().'</option>';
        }
        ?>
    </select>
</div>
<button type="button" id="delete-device-only" class="btn btn-warning">
    <i class="fas fa-trash-alt mr-1"></i> Sadece Cihazdan Sil
</button>
                                    <div class="form-group">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="delete_type" id="delete_db_only" value="db_only" checked>
                                            <label class="form-check-label" for="delete_db_only">
                                                Sadece Veritabanından Sil
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="delete_type" id="delete_both" value="both">
                                            <label class="form-check-label" for="delete_both">
                                                Hem Veritabanından Hem Cihazdan Sil
                                            </label>
                                        </div>
                                    </div>
                                    <button type="submit" class="btn btn-danger"><i class="fas fa-user-minus mr-1"></i> Kullanıcıyı Sil</button>
                                </form>
                                
                                <hr>
                                
                                <!-- Tüm Kullanıcıları Silme Formu -->
                                <form id="all-delete-form">
                                    <h6 class="text-danger">Tüm Kullanıcıları Silme</h6>
                                    <div class="alert alert-warning">
                                        <i class="fas fa-exclamation-triangle mr-1"></i> Bu işlem tüm kullanıcıları silecektir. Bu işlem geri alınamaz!
                                    </div>
                                    
                                    <div class="form-group">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="delete_all_type" id="delete_all_db_only" value="db_only" checked>
                                            <label class="form-check-label" for="delete_all_db_only">
                                                Sadece Veritabanından Sil
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="delete_all_type" id="delete_all_both" value="both">
                                            <label class="form-check-label" for="delete_all_both">
                                                Hem Veritabanından Hem Cihazdan Sil
                                            </label>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label for="confirm_text">Onay için "TÜM KULLANICILARI SİL" yazın:</label>
                                        <input type="text" class="form-control" id="confirm_text" name="confirm_text" required>
                                    </div>
                                    <button type="submit" class="btn btn-danger"><i class="fas fa-users-slash mr-1"></i> Tüm Kullanıcıları Sil</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                
                
                <!-- Sistem Ayarları Kartı -->
                <div class="card mb-4">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-cogs mr-1"></i> Genel Sistem Ayarları</h5>
                    </div>
                    <div class="card-body">
                        <form id="system-settings-form">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="company_name">Şirket Adı</label>
                                        <input type="text" class="form-control" id="company_name" name="company_name" value="Şirket Adı">
                                    </div>
                                    <div class="form-group">
                                        <label for="system_title">Sistem Başlığı</label>
                                        <input type="text" class="form-control" id="system_title" name="system_title" value="PDKS - Personel Devam Kontrol Sistemi">
                                    </div>
                                    <div class="form-group">
                                        <label for="auto_sync">Otomatik Senkronizasyon</label>
                                        <select class="form-control" id="auto_sync" name="auto_sync">
                                            <option value="enabled">Etkin</option>
                                            <option value="disabled">Devre Dışı</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="smtp_server">SMTP Sunucu</label>
                                        <input type="text" class="form-control" id="smtp_server" name="smtp_server" placeholder="smtp.example.com">
                                    </div>
                                    <div class="form-group">
                                        <label for="smtp_email">SMTP E-posta</label>
                                        <input type="email" class="form-control" id="smtp_email" name="smtp_email" placeholder="info@example.com">
                                    </div>
                                    <div class="form-group">
                                        <label for="smtp_password">SMTP Şifre</label>
                                        <input type="password" class="form-control" id="smtp_password" name="smtp_password" placeholder="******">
                                    </div>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary">Ayarları Kaydet</button>
                        </form>
                    </div>
                </div>
      
                <!-- Sistem Bilgisi Kartı -->
                <div class="card">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0"><i class="fas fa-info-circle mr-1"></i> Sistem Bilgisi</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Versiyon:</strong> 1.0.0</p>
                                <p><strong>Son Güncelleme:</strong> <?php echo date('d.m.Y'); ?></p>
                                <p><strong>PHP Versiyonu:</strong> <?php echo phpversion(); ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>MySQL Versiyonu:</strong> <?php echo $conn->getAttribute(PDO::ATTR_SERVER_VERSION); ?></p>
                                <p><strong>Veritabanı Boyutu:</strong> <?php 
                                    try {
                                        $stmt = $conn->query("SELECT SUM(data_length + index_length) / 1024 / 1024 'size' FROM information_schema.TABLES WHERE table_schema = 'db_kartlar'");
                                        $size = $stmt->fetchColumn();
                                        echo round($size, 2) . ' MB';
                                    } catch(PDOException $e) {
                                        echo "Hesaplanamadı";
                                    }
                                ?></p>
                                <p><strong>Toplam Kart Sayısı:</strong> <?php 
                                    try {
                                        $stmt = $conn->query("SELECT COUNT(*) FROM cards");
                                        echo $stmt->fetchColumn();
                                    } catch(PDOException $e) {
                                        echo "0";
                                    }
                                ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modaller -->
    
    <!-- Kullanıcı Detay Modal -->
    <div class="modal fade" id="userDetailsModal" tabindex="-1" role="dialog" aria-labelledby="userDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title" id="userDetailsModalLabel"><i class="fas fa-user mr-1"></i> Personel Detayları</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div id="userDetails"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Kapat</button>
                    <button type="button" class="btn btn-primary" id="edit-user-from-details">Düzenle</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Kullanıcı Düzenleme Modal -->
    <div class="modal fade" id="editUserModal" tabindex="-1" role="dialog" aria-labelledby="editUserModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="editUserModalLabel"><i class="fas fa-edit mr-1"></i> Personel Düzenle</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="edit-user-form" enctype="multipart/form-data">
                        <input type="hidden" id="edit_user_id" name="user_id">
                        
                        <div class="row">
                            <div class="col-md-4 text-center mb-3">
                                <div id="edit-photo-container">
                                    <img id="edit-user-photo" src="uploads/default-user.png" class="user-photo mb-2" alt="Profil">
                                </div>
                                <div class="custom-file">
                                    <input type="file" class="custom-file-input" id="edit_photo" name="photo" accept="image/*">
                                    <label class="custom-file-label" for="edit_photo">Fotoğraf Seç</label>
                                </div>
                            </div>
                            
                            <div class="col-md-8">
                                <div class="card mb-3">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0">Kart ve Sistem Bilgileri</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-row">
                                            <div class="form-group col-md-6">
                                                <label for="edit_card_number">Kart Numarası</label>
                                                <div class="input-group">
                                                    <input type="text" class="form-control" id="edit_card_number" name="card_number" required>
                                                    <div class="input-group-append">
                                                        <button class="btn btn-outline-secondary" type="button" id="edit-scan-card-btn">Okut</button>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="form-group col-md-6">
                                                <label for="edit_privilege">Yetki Seviyesi</label>
                                                <select class="form-control" id="edit_privilege" name="privilege">
                                                    <option value="0">0 - Normal Kullanıcı</option>
                                                    <option value="1">1 - Kayıt Yetkilisi</option>
                                                    <option value="2">2 - Yönetici</option>
                                                    <option value="3">3 - Süper Admin</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="form-row">
                                            <div class="form-group col-md-6">
                                                <label for="edit_password">Şifre (değiştirmek için doldurun)</label>
                                                <input type="password" class="form-control" id="edit_password" name="password">
                                            </div>
                                            <div class="form-group col-md-6">
                                                <div class="form-check mt-4">
                                                    <input class="form-check-input" type="checkbox" id="edit_enabled" name="enabled">
                                                    <label class="form-check-label" for="edit_enabled">
                                                        Aktif
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card mb-3">
                            <div class="card-header bg-light">
                                <h6 class="mb-0">Personel Bilgileri</h6>
                            </div>
                            <div class="card-body">
                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label for="edit_name">Adı</label>
                                        <input type="text" class="form-control" id="edit_name" name="name">
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="edit_surname">Soyadı</label>
                                        <input type="text" class="form-control" id="edit_surname" name="surname">
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label for="edit_department">Departman</label>
                                        <input type="text" class="form-control" id="edit_department" name="department">
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="edit_position">Pozisyon</label>
                                        <input type="text" class="form-control" id="edit_position" name="position">
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label for="edit_phone">Telefon</label>
                                        <input type="tel" class="form-control" id="edit_phone" name="phone">
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="edit_email">E-posta</label>
                                        <input type="email" class="form-control" id="edit_email" name="email">
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label for="edit_hire_date">İşe Giriş Tarihi</label>
                                        <input type="date" class="form-control" id="edit_hire_date" name="hire_date">
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="edit_birth_date">Doğum Tarihi</label>
                                        <input type="date" class="form-control" id="edit_birth_date" name="birth_date">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="edit_address">Adres</label>
                                    <textarea class="form-control" id="edit_address" name="address" rows="3"></textarea>
                                </div>
                            </div>
                        <!-- "Personel Bilgileri" kartından sonra, modal-body içinde -->
<div class="card mb-3">
    <div class="card-header bg-light">
        <h6 class="mb-0">Maaş Bilgileri</h6>
    </div>
    <div class="card-body">
        <div class="form-row">
            <div class="form-group col-md-6">
                <label for="edit_base_salary">Aylık Sabit Maaş (₺)</label>
                <input type="number" step="0.01" min="0" class="form-control" id="edit_base_salary" name="base_salary">
            </div>
            <div class="form-group col-md-6">
                <label for="edit_hourly_rate">Saatlik Ücret (₺/saat)</label>
                <input type="number" step="0.01" min="0" class="form-control" id="edit_hourly_rate" name="hourly_rate">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group col-md-4">
                <label for="edit_overtime_rate">Fazla Mesai Çarpanı</label>
                <input type="number" step="0.1" min="1" class="form-control" id="edit_overtime_rate" name="overtime_rate" value="1.5">
            </div>
            <div class="form-group col-md-4">
                <label for="edit_daily_work_hours">Günlük Çalışma Saati</label>
                <input type="number" step="0.5" min="0" max="24" class="form-control" id="edit_daily_work_hours" name="daily_work_hours" value="8.0">
            </div>
            <div class="form-group col-md-4">
                <label for="edit_monthly_work_days">Aylık Çalışma Günü</label>
                <input type="number" step="1" min="0" max="31" class="form-control" id="edit_monthly_work_days" name="monthly_work_days" value="22">
            </div>
        </div>
    </div>
</div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">İptal</button>
                    <button type="button" class="btn btn-primary" id="save-edit-user">Kaydet</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Yeni Kart Ekleme Modal -->
    <div class="modal fade" id="addCardModal" tabindex="-1" role="dialog" aria-labelledby="addCardModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="addCardModalLabel"><i class="fas fa-plus-circle mr-1"></i> Yeni Personel Ekle</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div id="add-message-area"></div>
                    <form id="add-card-form" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-4 text-center mb-3">
                                <div>
                                    <img id="new-user-photo" src="uploads/default-user.png" class="user-photo mb-2" alt="Profil">
                                </div>
                                <div class="custom-file">
                                    <input type="file" class="custom-file-input" id="photo" name="photo" accept="image/*">
                                    <label class="custom-file-label" for="photo">Fotoğraf Seç</label>
                                </div>
                            </div>
                            <div class="col-md-8">
                                <div class="card mb-3">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0">Kart ve Sistem Bilgileri</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-row">
                                            <div class="form-group col-md-6">
                                                <label for="user_id">Kullanıcı ID</label>
                                                <input type="text" class="form-control" id="user_id" name="user_id" required>
                                            </div>
                                            <div class="form-group col-md-6">
                                                <label for="card_number">Kart Numarası</label>
                                                <div class="input-group">
                                                    <input type="text" class="form-control" id="card_number" name="card_number" required>
                                                    <div class="input-group-append">
                                                        <button class="btn btn-outline-secondary" type="button" id="add-scan-card-btn">Okut</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-row">
                                            <div class="form-group col-md-6">
                                                <label for="privilege">Yetki Seviyesi</label>
                                                <select class="form-control" id="privilege" name="privilege">
                                                    <option value="0">0 - Normal Kullanıcı</option>
                                                    <option value="1">1 - Kayıt Yetkilisi</option>
                                                    <option value="2">2 - Yönetici</option>
                                                    <option value="3">3 - Süper Admin</option>
                                                </select>
                                            </div>
                                            <div class="form-group col-md-6">
                                                <label for="password">Şifre (Opsiyonel)</label>
                                                <input type="password" class="form-control" id="password" name="password">
                                            </div>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="enabled" name="enabled" checked>
                                            <label class="form-check-label" for="enabled">
                                                Aktif
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card mb-3">
                            <div class="card-header bg-light">
                                <h6 class="mb-0">Personel Bilgileri</h6>
                            </div>
                            <div class="card-body">
                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label for="name">Adı</label>
                                        <input type="text" class="form-control" id="name" name="name">
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="surname">Soyadı</label>
                                        <input type="text" class="form-control" id="surname" name="surname">
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label for="department">Departman</label>
                                        <input type="text" class="form-control" id="department" name="department">
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="position">Pozisyon</label>
                                        <input type="text" class="form-control" id="position" name="position">
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label for="phone">Telefon</label>
                                        <input type="tel" class="form-control" id="phone" name="phone">
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="email">E-posta</label>
                                        <input type="email" class="form-control" id="email" name="email">
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label for="hire_date">İşe Giriş Tarihi</label>
                                        <input type="date" class="form-control" id="hire_date" name="hire_date" value="<?php echo date('Y-m-d'); ?>">
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="birth_date">Doğum Tarihi</label>
                                        <input type="date" class="form-control" id="birth_date" name="birth_date">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="address">Adres</label>
                                    <textarea class="form-control" id="address" name="address" rows="3"></textarea>
                                </div>
                            </div>
                        </div>
                    </form>
                     <div class="card mb-3">
    <div class="card-header bg-light">
        <h6 class="mb-0">Maaş Bilgileri</h6>
    </div>
    <div class="card-body">
        <div class="form-row">
            <div class="form-group col-md-6">
                <label for="base_salary">Aylık Sabit Maaş (₺)</label>
                <input type="number" step="0.01" min="0" class="form-control" id="base_salary" name="base_salary" placeholder="0.00">
            </div>
            <div class="form-group col-md-6">
                <label for="hourly_rate">Saatlik Ücret (₺/saat)</label>
                <input type="number" step="0.01" min="0" class="form-control" id="hourly_rate" name="hourly_rate" placeholder="0.00">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group col-md-4">
                <label for="overtime_rate">Fazla Mesai Çarpanı</label>
                <input type="number" step="0.1" min="1" class="form-control" id="overtime_rate" name="overtime_rate" value="1.5">
            </div>
            <div class="form-group col-md-4">
                <label for="daily_work_hours">Günlük Çalışma Saati</label>
                <input type="number" step="0.5" min="0" max="24" class="form-control" id="daily_work_hours" name="daily_work_hours" value="8.0">
            </div>
            <div class="form-group col-md-4">
                <label for="monthly_work_days">Aylık Çalışma Günü</label>
                <input type="number" step="1" min="0" max="31" class="form-control" id="monthly_work_days" name="monthly_work_days" value="22">
            </div>
        </div>
    </div>
</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">İptal</button>
                    <button type="button" class="btn btn-success" id="save-new-card">Kaydet</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Alerts Container -->
    <div class="alerts-container"></div>

    <!-- Tema Seçici Butonu -->
    <div class="theme-selector" id="theme-toggle">
        <i class="fas fa-palette"></i>
    </div>
    <div class="theme-options" id="theme-options">
        <div class="d-flex">
            <div class="theme-option theme-light" data-theme="light" title="Açık Tema"></div>
            <div class="theme-option theme-dark" data-theme="dark" title="Koyu Tema"></div>
            <div class="theme-option theme-blue" data-theme="blue" title="Mavi Tema"></div>
            <div class="theme-option theme-green" data-theme="green" title="Yeşil Tema"></div>
            <div class="theme-option theme-red" data-theme="red" title="Kırmızı Tema"></div>
        </div>
    </div>

    <!-- JavaScript Kütüphaneleri -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

    <!-- JavaScript Kodları -->
    <script>
    $(document).ready(function() {
        // Global değişkenler
        var currentCardScanInterval = null;
        
        // Maaş personel açılır menüsünü güncelleme fonksiyonu
        function updateSalaryPersonnelDropdown() {
            $.ajax({
                url: 'get_users.php',
                dataType: 'json',
                success: function(data) {
                    var dropdown = $('#salary-user-id');
                    dropdown.empty();
                    dropdown.append('<option value="">Personel Seçin</option>');
                    if (data && data.length > 0) {
                        $.each(data, function(i, user) {
                            dropdown.append($('<option></option>').attr('value', user.user_id).text(user.name + ' ' + user.surname));
                        });
                    } else {
                        dropdown.append('<option value="" disabled>Kayıtlı personel bulunamadı</option>');
                    }
                }
            });
        }

        // Sadece cihazdan silme
$('#delete-device-only').click(function() {
    var userId = $('#device_only_user_id').val();
    if (!userId) {
        showAlert('Lütfen bir kullanıcı seçin.', 'warning');
        return;
    }
    
    if (confirm('Bu kullanıcıyı sadece cihazdan silmek istediğinizden emin misiniz? Veritabanında kayıtları durmaya devam edecek.')) {
        $.ajax({
            type: 'POST',
            url: 'delete_device_only.php',
            data: {
                user_id: userId
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showAlert(response.message, 'success');
                    $('#device_only_user_id').val('');
                } else {
                    showAlert(response.message, 'danger');
                }
            },
            error: function() {
                showAlert('Silme işlemi sırasında bir hata oluştu!', 'danger');
            }
        });
    }
});
// Devamsızlık sekmesi yüklendiğinde
$('#absence-tab').on('shown.bs.tab', function() {
    loadTodayAbsences();
    loadAbsenceStats();
});

// Bugünkü devamsızları yükle
function loadTodayAbsences() {
    $.ajax({
        url: 'get_today_absences.php',
        dataType: 'json',
        success: function(data) {
            if (data.success) {
                $('#today-absences-container').html(data.html);
                // Sekme başlığına sayı ekle
                if (data.count > 0) {
                    $('#absence-tab').html('<i class="fas fa-user-times mr-1"></i> Devamsızlık Takibi <span class="badge badge-warning">' + data.count + '</span>');
                } else {
                    $('#absence-tab').html('<i class="fas fa-user-times mr-1"></i> Devamsızlık Takibi');
                }
            } else {
                $('#today-absences-container').html('<div class="alert alert-danger">' + data.message + '</div>');
            }
        },
        error: function() {
            $('#today-absences-container').html('<div class="alert alert-danger">Veriler yüklenirken bir hata oluştu!</div>');
        }
    });
}

// Devamsızlık istatistiklerini yükle
function loadAbsenceStats() {
    $.ajax({
        url: 'get_absence_stats.php',
        dataType: 'json',
        success: function(data) {
            if (data.success) {
                $('#absence-stats-container').html(data.html);
            } else {
                $('#absence-stats-container').html('<div class="alert alert-danger">' + data.message + '</div>');
            }
        },
        error: function() {
            $('#absence-stats-container').html('<div class="alert alert-danger">Veriler yüklenirken bir hata oluştu!</div>');
        }
    });
}
        // Bugünkü devamsızları yükle (güncellendi)
function loadTodayAbsences() {
    $.ajax({
        url: 'get_today_absences.php',
        dataType: 'json',
        success: function(data) {
            if (data.success) {
                $('#today-absences-container').html(data.html);
                // Sekme başlığına detaylı bilgi ekle
                if (data.count > 0) {
                    $('#absence-tab').html('<i class="fas fa-user-times mr-1"></i> Devamsızlık Takibi <span class="badge badge-danger">' + data.count + '</span>');
                } else {
                    $('#absence-tab').html('<i class="fas fa-user-times mr-1"></i> Devamsızlık Takibi <span class="badge badge-success">✓</span>');
                }
            } else {
                $('#today-absences-container').html('<div class="alert alert-danger">' + data.message + '</div>');
            }
        },
        error: function() {
            $('#today-absences-container').html('<div class="alert alert-danger">Veriler yüklenirken bir hata oluştu!</div>');
        }
    });
}
        // İzin Yönetimi sekmesi veri yükleme
$('#leave-tab').on('shown.bs.tab', function (e) {
    loadPendingLeaves();
    loadLeaveBalances();
});
// Bekleyen izin taleplerini yükle
function loadPendingLeaves() {
    $.ajax({
        url: 'get_pending_leaves.php',
        dataType: 'json',
        success: function(data) {
            if (data.success) {
                $('#pending-leaves-container').html(data.html);
                // İzin sayısı badge'i güncelle
                if (data.count > 0) {
                    $('#leave-tab').html('<i class="fas fa-calendar-alt mr-1"></i> İzin Yönetimi <span class="badge badge-warning">' + data.count + '</span>');
                } else {
                    $('#leave-tab').html('<i class="fas fa-calendar-alt mr-1"></i> İzin Yönetimi');
                }
            } else {
                $('#pending-leaves-container').html('<div class="alert alert-danger">' + data.message + '</div>');
            }
        },
        error: function() {
            $('#pending-leaves-container').html('<div class="alert alert-danger">Veriler yüklenirken bir hata oluştu!</div>');
        }
    });
}
// İzin bakiyelerini yükle
function loadLeaveBalances() {
    $.ajax({
        url: 'get_leave_balances.php',
        dataType: 'json',
        success: function(data) {
            if (data.success) {
                $('#leave-balances-container').html(data.html);
            } else {
                $('#leave-balances-container').html('<div class="alert alert-danger">' + data.message + '</div>');
            }
        },
        error: function() {
            $('#leave-balances-container').html('<div class="alert alert-danger">Veriler yüklenirken bir hata oluştu!</div>');
        }
    });
}
        // Kart okutma fonksiyonu
        function startCardScan(targetField) {
            // Önceki tarama işlemi varsa durdur
            if (currentCardScanInterval) {
                clearInterval(currentCardScanInterval);
            }
            // Tarama durumunu göster
            $(targetField).siblings('.input-group-append').find('button').html('<span class="spinner-border spinner-border-sm"></span> Bekleniyor...');
            // Her 2 saniyede bir son kartı kontrol et
            currentCardScanInterval = setInterval(function() {
                $.ajax({
                    url: 'get_last_card.php',
                    dataType: 'json',
                    success: function(data) {
                        if (data.success && data.card_number) {
                            clearInterval(currentCardScanInterval);
                            currentCardScanInterval = null;
                            $(targetField).val(data.card_number);
                            $(targetField).siblings('.input-group-append').find('button').text('Okut');
                            showAlert('Kart başarıyla okundu: ' + data.card_number, 'info');
                        }
                    }
                });
            }, 2000);
            // 30 saniye sonra işlemi durdur
            setTimeout(function() {
                if (currentCardScanInterval) {
                    clearInterval(currentCardScanInterval);
                    currentCardScanInterval = null;
                    $(targetField).siblings('.input-group-append').find('button').text('Okut');
                    showAlert('Kart okutma zaman aşımına uğradı. Lütfen tekrar deneyin.', 'warning');
                }
            }, 30000);
        }
        // Tab değiştiğinde veri yükle
        $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
            var target = $(e.target).attr("href");
            if (target == '#dashboard') {
                updateDashboard();
            } else if (target == '#cards') {
                updateCardsTable();
            } else if (target == '#attendance') {
                updateAttendanceData();
            } else if (target == '#logs') {
                updateLogs();
            } else if (target == '#settings') {
                refreshUserDropdown();
            } else if (target == '#salary') {
                // Maaş sekmesine geçildiğinde personel listesini güncelle
                updateSalaryPersonnelDropdown();
            }
        });
        // Gösterge panelini güncelleme
        function updateDashboard() {
            $.ajax({
                url: 'dashboard_data.php',
                dataType: 'json',
                success: function(data) {
                    $('#total-personnel').text(data.total_personnel);
                    $('#currently-inside').text(data.currently_inside);
                    $('#today-entries').text(data.today_entries);
                    $('#today-exits').text(data.today_exits);
                    $('#recent-activities').html(data.recent_activities);
                    $('#recent-scans').html(data.recent_scans);
                }
            });
        }
        // Kartlar tablosunu güncelleme
        function updateCardsTable() {
            var search = $('#search-cards').val();
            var department = $('#filter-department').val();
            $.ajax({
                url: 'get_cards.php',
                data: {
                    search: search,
                    department: department
                },
                success: function(data) {
                    $('#cards-table-body').html(data);
                }
            });
        }
        // Periyodik güncellemeler
        setInterval(function() {
            if ($('#dashboard').hasClass('show')) {
                updateDashboard();
            } else if ($('#cards').hasClass('show')) {
                updateCardsTable();
            } else if ($('#attendance').hasClass('show')) {
                updateAttendanceData();
            } else if ($('#logs').hasClass('show')) {
                updateLogs();
            }
        }, 10000);
        // Kart arama
        $('#search-cards, #filter-department').on('keyup change', function() {
            updateCardsTable();
        });
        // Giriş-çıkış tablosunu güncelleme
        function updateAttendanceData() {
            var search = $('#search-attendance').val();
            var date = $('#date-filter').val();
            var type = $('#type-filter').val();
            $.ajax({
                url: 'get_attendance.php',
                data: {
                    search: search,
                    date: date,
                    type: type
                },
                success: function(data) {
                    $('#attendance-tbody').html(data);
                    updateAttendanceStats();
                }
            });
        }
        // Giriş-çıkış filtreleme
        $('#search-attendance, #date-filter, #type-filter').on('change keyup', function() {
            updateAttendanceData();
        });
        // Giriş-çıkış istatistiklerini güncelleme
        function updateAttendanceStats() {
            $.ajax({
                url: 'attendance_stats.php',
                dataType: 'json',
                success: function(data) {
                    $('#today-entries-att').text(data.entries);
                    $('#today-exits-att').text(data.exits);
                    $('#currently-inside-att').text(data.inside);
                }
            });
        }
        // Logları güncelleme
        function updateLogs() {
            var search = $('#search-logs').val();
            var date = $('#date-filter-logs').val();
            $.ajax({
                url: 'get_logs.php',
                data: {
                    search: search,
                    date: date
                },
                success: function(data) {
                    $('#logs-table-body').html(data);
                }
            });
        }
        // Log filtreleme
        $('#search-logs, #date-filter-logs').on('change keyup', function() {
            updateLogs();
        });
        // Log filtrelerini temizle
        $('#clear-log-filters').click(function() {
            $('#search-logs').val('');
            $('#date-filter-logs').val('<?php echo date('Y-m-d'); ?>');
            updateLogs();
        });
        // Kullanıcı listesi dropdown'ını güncelle
        function refreshUserDropdown() {
            $.ajax({
                url: 'get_users.php',
                dataType: 'json',
                success: function(data) {
                    var dropdown = $('#user_id_to_delete');
                    dropdown.empty();
                    dropdown.append('<option value="">Kullanıcı Seçin</option>');
                    if (data && data.length > 0) {
                        $.each(data, function(i, user) {
                            var userName = user.name + ' ' + user.surname;
                            dropdown.append($('<option></option>').attr('value', user.user_id).text(user.user_id + ' - ' + userName));
                        });
                    } else {
                        dropdown.append('<option value="" disabled>Kayıtlı kullanıcı bulunamadı</option>');
                    }
                },
                error: function() {
                    $('#delete-message-area').html('<div class="alert alert-danger">Kullanıcı listesi alınırken bir hata oluştu!</div>');
                }
            });
        }
        // Senkronizasyon butonu tıklama
        $('#trigger-sync-btn, #sync-cards-btn, #sync-devices-btn').click(function() {
            $.ajax({
                url: 'trigger_sync.php',
                success: function(response) {
                    showAlert('Senkronizasyon talebi gönderildi. Cihaz uygulaması çalışıyorsa kartlar cihaza aktarılacaktır.');
                }
            });
        });
        // Yeni kart ekleme butonları tıklama
        $('#add-new-card-btn, #new-card-btn, #quick-add-card').click(function() {
            // Form alanlarını sıfırla
            $('#add-card-form')[0].reset();
            $('#new-user-photo').attr('src', 'uploads/default-user.png');
            // Yeni kullanıcı ID önerisi al
            $.ajax({
                url: 'get_next_user_id.php',
                dataType: 'json',
                success: function(data) {
                    $('#user_id').val(data.next_id);
                }
            });
            // Modal göster
            $('#addCardModal').modal('show');
        });
        // Kart okutma butonları
        $('#add-scan-card-btn').click(function() {
            startCardScan('#card_number');
        });
        $('#edit-scan-card-btn').click(function() {
            startCardScan('#edit_card_number');
        });
        $('#scan-card-btn, #quick-scan-card').click(function() {
            startCardScan('#card_number');
            $('#addCardModal').modal('show');
        });
        // Yeni personel kaydetme
        $('#save-new-card').click(function() {
            var formData = new FormData($('#add-card-form')[0]);
            $.ajax({
                type: 'POST',
                url: 'save_card.php',
                data: formData,
                contentType: false,
                processData: false,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        showAlert(response.message);
                        $('#addCardModal').modal('hide');
                        // Kartlar tablosunu güncelle
                        updateCardsTable();
                        // Maaş hesaplama için personel listesini güncelle
                        updateSalaryPersonnelDropdown();
                        // Kullanıcı listelerini güncelle
                        refreshUserDropdown();
                    } else {
                        $('#add-message-area').html('<div class="alert alert-danger">' + response.message + '</div>');
                    }
                },
                error: function() {
                    $('#add-message-area').html('<div class="alert alert-danger">İşlem sırasında bir hata oluştu!</div>');
                }
            });
        });
        // Maaş hesaplama formu
$('#salary-calculation-form').submit(function(e) {
    e.preventDefault();
    var userId = $('#salary-user-id').val();
    var startDate = $('#salary-start-date').val();
    var endDate = $('#salary-end-date').val();
    if (!userId) {
        showAlert('Lütfen personel seçin.', 'warning');
        return;
    }
    $.ajax({
        url: 'salary_calculator.php',
        data: {
            action: 'calculate',
            user_id: userId,
            start_date: startDate,
            end_date: endDate
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                displaySalaryResult(response);
            } else {
                showAlert(response.message, 'danger');
            }
        },
        error: function() {
            showAlert('Maaş hesaplanırken bir hata oluştu!', 'danger');
        }
    });
});
// Maaş sonucunu görüntüleme
function displaySalaryResult(data) {
    // Personel bilgileri
    $('#salary-employee-name').text(data.employee.name);
    $('#salary-employee-department').text(data.employee.department || '-');
    $('#salary-employee-position').text(data.employee.position || '-');
    
    // Dönem bilgileri
    $('#salary-period-start').text(formatDate(data.period.start_date));
    $('#salary-period-end').text(formatDate(data.period.end_date));
    $('#salary-period-days').text(data.period.total_days);
    
    // Tam ay çalışma bilgisi
    var periodInfo = data.period.is_full_month ? 
                     'Tam ay çalışma (' + data.period.days_in_month + ' gün)' : 
                     'Kısmi ay çalışma (' + data.period.total_days + ' gün)';
    $('#salary-period-info').text(periodInfo);
    
    // Çalışma süresi
    var normalWorkHours = data.work_time.total_hours;
    var normalWorkMinutes = data.work_time.total_minutes;
    $('#salary-work-normal').text(formatTime(normalWorkHours, normalWorkMinutes));
    
    var overtimeHours = data.work_time.overtime_hours;
    var overtimeMinutes = data.work_time.overtime_minutes;
    $('#salary-work-overtime').text(formatTime(overtimeHours, overtimeMinutes));
    
    var totalHours = normalWorkHours + overtimeHours;
    var totalMinutes = normalWorkMinutes + overtimeMinutes;
    if (totalMinutes >= 60) {
        totalHours += Math.floor(totalMinutes / 60);
        totalMinutes %= 60;
    }
    $('#salary-work-total').text(formatTime(totalHours, totalMinutes));
    
    // Maaş bilgileri
    $('#salary-amount-normal').text(formatCurrency(data.salary.regular_salary));
    $('#salary-amount-overtime').text(formatCurrency(data.salary.overtime_salary));
    $('#salary-amount-total').text(formatCurrency(data.salary.total_salary));
    
    // Biriken aylık maaş
    $('#salary-monthly-accumulated').text(formatCurrency(data.salary.accumulated_monthly_salary));
    
    // Günlük detaylar
    var dailyRows = '';
    $.each(data.daily_details, function(i, day) {
        dailyRows += '<tr>';
        dailyRows += '<td>' + formatDate(day.date) + '</td>';
        dailyRows += '<td>' + day.entry + '</td>';
        dailyRows += '<td>' + day.exit + '</td>';
        dailyRows += '<td>' + formatTime(day.normal_hours, day.normal_minutes) + '</td>';
        dailyRows += '<td>' + formatTime(day.overtime_hours, day.overtime_minutes) + '</td>';
        dailyRows += '<td>' + formatTime(day.work_hours, day.work_minutes) + '</td>';
        dailyRows += '</tr>';
    });
    $('#salary-daily-details tbody').html(dailyRows);
    
    $('#salary-result').show();
}
// Toplu maaş hesaplama
$('#batch-salary-form').submit(function(e) {
    e.preventDefault();
    var startDate = $('#batch-salary-start-date').val();
    var endDate = $('#batch-salary-end-date').val();
    var department = $('#batch-salary-department').val();
    $.ajax({
        url: 'calculate_all_salaries.php',
        data: {
            start_date: startDate,
            end_date: endDate,
            department: department
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                displayBatchSalaryResult(response);
            } else {
                showAlert(response.message, 'danger');
            }
        },
        error: function() {
            showAlert('Toplu maaş hesaplanırken bir hata oluştu!', 'danger');
        }
    });
});
// Toplu maaş sonucunu görüntüleme
function displayBatchSalaryResult(data) {
    // Özet bilgileri
    $('#batch-total-employees').text(data.employees);
    $('#batch-total-regular').text(formatCurrency(data.totals.regular_salary));
    $('#batch-total-salary').text(formatCurrency(data.totals.total_salary));
    // Tablo detayları
    var tableRows = '';
    $.each(data.results, function(i, employee) {
        tableRows += '<tr>';
        tableRows += '<td>' + employee.employee.name + '</td>';
        tableRows += '<td>' + (employee.employee.department || '-') + '</td>';
        tableRows += '<td>' + employee.period.total_days + '</td>';
        var workTime = formatTime(employee.work_time.total_hours, employee.work_time.total_minutes);
        var overtimeTime = formatTime(employee.work_time.overtime_hours, employee.work_time.overtime_minutes);
        tableRows += '<td>' + workTime + '</td>';
        tableRows += '<td>' + overtimeTime + '</td>';
        tableRows += '<td>' + formatCurrency(employee.salary.regular_salary) + '</td>';
        tableRows += '<td>' + formatCurrency(employee.salary.overtime_salary) + '</td>';
        tableRows += '<td>' + formatCurrency(employee.salary.total_salary) + '</td>';
        tableRows += '<td><button class="btn btn-sm btn-info view-salary" data-user-id="' + employee.employee.id + '">Detay</button></td>';
        tableRows += '</tr>';
    });
    $('#batch-salary-table tbody').html(tableRows);
    $('#batch-salary-result').show();
}
// Maaş ayarları için personel seçme
$('#settings-user-id').change(function() {
    var userId = $(this).val();
    if (!userId) {
        $('#salary-settings').hide();
        return;
    }
    $.ajax({
        url: 'get_salary_settings.php',
        data: {
            user_id: userId
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                $('#settings-user-id-hidden').val(response.user_id);
                $('#settings-base-salary').val(response.base_salary);
                $('#settings-hourly-rate').val(response.hourly_rate);
                $('#settings-overtime-rate').val(response.overtime_rate);
                $('#settings-daily-work-hours').val(response.daily_work_hours);
                $('#settings-monthly-work-days').val(response.monthly_work_days);
                $('#salary-settings').show();
            } else {
                showAlert(response.message, 'danger');
            }
        },
        error: function() {
            showAlert('Maaş ayarları alınırken bir hata oluştu!', 'danger');
        }
    });
});
// Maaş ayarlarını kaydetme
$('#salary-settings-form').submit(function(e) {
    e.preventDefault();
    $.ajax({
        type: 'POST',
        url: 'save_salary_settings.php',
        data: $(this).serialize(),
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                showAlert(response.message, 'success');
            } else {
                showAlert(response.message, 'danger');
            }
        },
        error: function() {
            showAlert('Maaş ayarları kaydedilirken bir hata oluştu!', 'danger');
        }
    });
});
// Tarih formatı
function formatDate(dateString) {
    var date = new Date(dateString);
    return date.toLocaleDateString('tr-TR');
}
// Saat formatı
function formatTime(hours, minutes) {
    return hours + ' saat ' + minutes + ' dakika';
}
// Para formatı
function formatCurrency(amount) {
    return parseFloat(amount).toLocaleString('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' ₺';
}
// Toplu hesaplama sonucundan personel detayına gitme
// Toplu maaş hesaplamadaki detay düğmesi için
$(document).on('click', '.view-salary-details', function() {
    var userId = $(this).data('user-id');
    var startDate = $('#batch-salary-start-date').val();
    var endDate = $('#batch-salary-end-date').val();
    
    // Kullanıcı seçimini güncelleyelim
    $('#salary-user-id').val(userId);
    
    // Tarih aralığını güncelleyelim
    $('#salary-start-date').val(startDate);
    $('#salary-end-date').val(endDate);
    
    // Maaş hesaplama formunu manuel olarak tetikleyelim
    $('#salary-calculation-form').submit();
    
    // Maaş sekmesine geçiş yapalım
    $('#salary-tab').tab('show');
});

// Ayrıca toplu maaş tablosundaki HTML'i güncelleyelim
function displayBatchSalaryResult(data) {
    // Özet bilgileri
    $('#batch-total-employees').text(data.employees);
    $('#batch-total-regular').text(formatCurrency(data.totals.regular_salary));
    $('#batch-total-salary').text(formatCurrency(data.totals.total_salary));
    
    // Tablo detayları
    var tableRows = '';
    $.each(data.results, function(i, employee) {
        tableRows += '<tr>';
        tableRows += '<td>' + employee.employee.name + '</td>';
        tableRows += '<td>' + (employee.employee.department || '-') + '</td>';
        tableRows += '<td>' + employee.period.total_days + '</td>';
        var workTime = formatTime(employee.work_time.total_hours, employee.work_time.total_minutes);
        var overtimeTime = formatTime(employee.work_time.overtime_hours, employee.work_time.overtime_minutes);
        tableRows += '<td>' + workTime + '</td>';
        tableRows += '<td>' + overtimeTime + '</td>';
        tableRows += '<td>' + formatCurrency(employee.salary.regular_salary) + '</td>';
        tableRows += '<td>' + formatCurrency(employee.salary.overtime_salary) + '</td>';
        tableRows += '<td>' + formatCurrency(employee.salary.total_salary) + '</td>';
        tableRows += '<td><button class="btn btn-sm btn-info view-salary-details" data-user-id="' + employee.employee.id + '">Detay</button></td>';
        tableRows += '</tr>';
    });
    
    $('#batch-salary-table tbody').html(tableRows);
    $('#batch-salary-result').show();
}
// Maaş hesaplama sonucunu PDF olarak indirme
$('#export-salary-pdf').click(function() {
    var userId = $('#salary-user-id').val();
    var startDate = $('#salary-start-date').val();
    var endDate = $('#salary-end-date').val();
    window.open('export_salary_pdf.php?user_id=' + userId + '&start_date=' + startDate + '&end_date=' + endDate, '_blank');
});
// Maaş hesaplama sonucunu Excel olarak indirme
$('#export-salary-excel').click(function() {
    var userId = $('#salary-user-id').val();
    var startDate = $('#salary-start-date').val();
    var endDate = $('#salary-end-date').val();
    window.open('export_salary_excel.php?user_id=' + userId + '&start_date=' + startDate + '&end_date=' + endDate, '_blank');
});
// Toplu maaş sonucunu PDF olarak indirme
$('#export-batch-pdf').click(function() {
    var startDate = $('#batch-salary-start-date').val();
    var endDate = $('#batch-salary-end-date').val();
    var department = $('#batch-salary-department').val();
    window.open('export_batch_salary_pdf.php?start_date=' + startDate + '&end_date=' + endDate + '&department=' + encodeURIComponent(department), '_blank');
});
// Toplu maaş sonucunu Excel olarak indirme
$('#export-batch-excel').click(function() {
    var startDate = $('#batch-salary-start-date').val();
    var endDate = $('#batch-salary-end-date').val();
    var department = $('#batch-salary-department').val();
    window.open('export_batch_salary_excel.php?start_date=' + startDate + '&end_date=' + endDate + '&department=' + encodeURIComponent(department), '_blank');
});
        // Fotoğraf önizleme - Yeni kart
        $('#photo').change(function() {
            if (this.files && this.files[0]) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    $('#new-user-photo').attr('src', e.target.result);
                }
                reader.readAsDataURL(this.files[0]);
                // Dosya adını göster
                var fileName = $(this).val().split('\\').pop();
                $(this).next('.custom-file-label').html(fileName);
            }
        });
        // Fotoğraf önizleme - Düzenleme
        $('#edit_photo').change(function() {
            if (this.files && this.files[0]) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    $('#edit-user-photo').attr('src', e.target.result);
                }
                reader.readAsDataURL(this.files[0]);
                // Dosya adını göster
                var fileName = $(this).val().split('\\').pop();
                $(this).next('.custom-file-label').html(fileName);
            }
        });
        // Kullanıcı detaylarını görüntüleme (Event Delegation)
        $(document).on('click', '.view-details', function() {
            var userId = $(this).data('user-id');
            $.ajax({
                url: 'get_user_details.php',
                type: 'GET',
                data: { user_id: userId },
                success: function(response) {
                    $('#userDetails').html(response);
                    $('#userDetailsModal').modal('show');
                    // Düzenleme butonu için kullanıcı ID'sini sakla
                    $('#edit-user-from-details').data('user-id', userId);
                },
                error: function() {
                    showAlert('Kullanıcı bilgileri alınırken bir hata oluştu!', 'danger');
                }
            });
        });
        // Detay modalından düzenleme moduna geçiş
        $('#edit-user-from-details').click(function() {
            var userId = $(this).data('user-id');
            $('#userDetailsModal').modal('hide');
            loadUserForEdit(userId);
        });
        // Kullanıcı düzenleme butonuna tıklama
        $(document).on('click', '.edit-user', function() {
            var userId = $(this).data('user-id');
            loadUserForEdit(userId);
        });
        // Kullanıcı verilerini düzenleme formuna yükle
        function loadUserForEdit(userId) {
            $.ajax({
                url: 'get_user_data.php',
                type: 'GET',
                data: { user_id: userId },
                dataType: 'json',
                success: function(user) {
                    $('#edit_user_id').val(user.user_id);
$('#edit_card_number').val(user.card_number);
$('#edit_name').val(user.name);
$('#edit_surname').val(user.surname);
$('#edit_department').val(user.department);
$('#edit_position').val(user.position);
$('#edit_phone').val(user.phone);
$('#edit_email').val(user.email);
$('#edit_hire_date').val(user.hire_date);
$('#edit_birth_date').val(user.birth_date);
$('#edit_address').val(user.address);
$('#edit_privilege').val(user.privilege);
$('#edit_enabled').prop('checked', user.enabled === 'true');
// Maaş bilgilerini doldur
$('#edit_base_salary').val(user.base_salary);
$('#edit_hourly_rate').val(user.hourly_rate);
$('#edit_overtime_rate').val(user.overtime_rate || 1.5);
$('#edit_daily_work_hours').val(user.daily_work_hours || 8.0);
$('#edit_monthly_work_days').val(user.monthly_work_days || 22);
                    // Fotoğrafı göster
                    if (user.photo_path) {
                        $('#edit-user-photo').attr('src', user.photo_path);
                    } else {
                        $('#edit-user-photo').attr('src', 'uploads/default-user.png');
                    }
                    // Modalı göster
                    $('#editUserModal').modal('show');
                },
                error: function() {
                    showAlert('Kullanıcı verileri alınırken bir hata oluştu!', 'danger');
                }
            });
        }
        // Kullanıcı düzenlemesini kaydet
        $('#save-edit-user').click(function() {
            var formData = new FormData($('#edit-user-form')[0]);
            $.ajax({
                type: 'POST',
                url: 'update_user.php',
                data: formData,
                contentType: false,
                processData: false,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        showAlert(response.message);
                        $('#editUserModal').modal('hide');
                        // Kartlar tablosunu güncelle
                        updateCardsTable();
                        // Maaş listesini güncelle
                        updateSalaryPersonnelDropdown();
                    } else {
                        showAlert(response.message, 'danger');
                    }
                },
                error: function() {
                    showAlert('Kullanıcı güncellenirken bir hata oluştu!', 'danger');
                }
            });
        });
        // Kullanıcı silme butonuna tıklama (Event Delegation)
        $(document).on('click', '.delete-user', function() {
            var userId = $(this).data('user-id');
            if (confirm('Bu kullanıcıyı silmek istediğinizden emin misiniz?')) {
                $.ajax({
                    type: 'POST',
                    url: 'delete_user.php',
                    data: {
                        user_id: userId,
                        delete_type: 'db_only'
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            showAlert(response.message);
                            // Kartlar tablosunu güncelle
                            updateCardsTable();
                            // Kullanıcı listesini güncelle
                            refreshUserDropdown();
                            // Maaş listesini güncelle
                            updateSalaryPersonnelDropdown();
                        } else {
                            showAlert(response.message, 'danger');
                        }
                    },
                    error: function() {
                        showAlert('Silme işlemi sırasında bir hata oluştu!', 'danger');
                    }
                });
            }
        });
        // Tek kullanıcı silme formu gönderimi
        $('#single-delete-form').submit(function(e) {
            e.preventDefault();
            if ($('#user_id_to_delete').val() === '') {
                $('#delete-message-area').html('<div class="alert alert-warning">Lütfen silinecek kullanıcıyı seçin.</div>');
                return;
            }
            if (confirm('Bu kullanıcıyı silmek istediğinizden emin misiniz?')) {
                $.ajax({
                    type: 'POST',
                    url: 'delete_user.php',
                    data: $(this).serialize(),
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            $('#delete-message-area').html('<div class="alert alert-success">' + response.message + '</div>');
                            // Kullanıcı listesini güncelle
                            refreshUserDropdown();
                            // Kartlar tablosunu güncelle
                            updateCardsTable();
                            // Maaş listesini güncelle
                            updateSalaryPersonnelDropdown();
                        } else {
                            $('#delete-message-area').html('<div class="alert alert-danger">' + response.message + '</div>');
                        }
                    },
                    error: function() {
                        $('#delete-message-area').html('<div class="alert alert-danger">Silme işlemi sırasında bir hata oluştu!</div>');
                    }
                });
            }
        });
        // Tüm kullanıcıları silme formu gönderimi
        $('#all-delete-form').submit(function(e) {
            e.preventDefault();
            if ($('#confirm_text').val() !== 'TÜM KULLANICILARI SİL') {
                $('#delete-message-area').html('<div class="alert alert-warning">Onay metni doğru değil. Silme işlemi iptal edildi.</div>');
                return;
            }
            if (confirm('TÜM KULLANICILARI SİLMEK İSTEDİĞİNİZDEN EMİN MİSİNİZ? BU İŞLEM GERİ ALINAMAZ!')) {
                $.ajax({
                    type: 'POST',
                    url: 'delete_all_users.php',
                    data: $(this).serialize(),
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            $('#delete-message-area').html('<div class="alert alert-success">' + response.message + '</div>');
                            // Form alanını temizle
                            $('#confirm_text').val('');
                            // Kullanıcı listesini güncelle
                            refreshUserDropdown();
                            // Kartlar tablosunu güncelle
                            updateCardsTable();
                            // Maaş listesini güncelle
                            updateSalaryPersonnelDropdown();
                        } else {
                            $('#delete-message-area').html('<div class="alert alert-danger">' + response.message + '</div>');
                        }
                    },
                    error: function() {
                        $('#delete-message-area').html('<div class="alert alert-danger">Silme işlemi sırasında bir hata oluştu!</div>');
                    }
                });
            }
        });
        // Rapor formatı seçicisi
        $('.export-format').click(function(e) {
            e.preventDefault();
            var format = $(this).data('format');
            var search = $('#search-attendance').val();
            var date = $('#date-filter').val();
            var type = $('#type-filter').val();
            window.location.href = 'export_attendance.php?format=' + format +
                                  '&search=' + encodeURIComponent(search) +
                                  '&date=' + encodeURIComponent(date) +
                                  '&type=' + encodeURIComponent(type);
        });
        // Veritabanı yedekleme
        $('#backup-btn').click(function() {
            $.ajax({
                url: 'backup_database.php',
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        showAlert('Veritabanı başarıyla yedeklendi: ' + response.filename);
                    } else {
                        showAlert('Veritabanı yedeklenirken bir hata oluştu: ' + response.message, 'danger');
                    }
                },
                error: function() {
                    showAlert('Veritabanı yedeklenirken bir hata oluştu!', 'danger');
                }
            });
        });
        // Mevcut script kısmının sonuna ekleyin

// Maaş sekmesi açıldığında verileri yükle
$('#salary-tab').on('shown.bs.tab', function() {
    loadSalaryStats();
    loadSalarySettingsSummary();
    loadSalaryOverview();
});

// Hızlı maaş hesaplama
$('#quick-salary-calc').submit(function(e) {
    e.preventDefault();
    var userId = $('#quick_user_select').val();
    var month = $('#quick_month').val();
    
    if (!userId) {
        showAlert('Lütfen bir personel seçin!', 'warning');
        return;
    }
    
    var startDate = month + '-01';
    var endDate = moment(month).endOf('month').format('YYYY-MM-DD');
    
    $('#quick-salary-result').html('<div class="spinner-border spinner-border-sm text-info"></div>').show();
    
    $.ajax({
        url: 'salary_calculator.php?action=calculate',
        data: {
            user_id: userId,
            start_date: startDate,
            end_date: endDate
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                var cssClass = response.salary.meets_minimum_requirement ? 'success' : 'warning';
                var html = '<div class="alert alert-' + cssClass + ' p-2">';
                html += '<small><strong>Net Maaş:</strong> ' + numberFormat(response.salary.net_salary) + ' TL<br>';
                html += '<strong>Devam:</strong> ' + response.attendance.total_attended_days + '/' + response.period.required_work_days + ' gün<br>';
                html += '<strong>Durum:</strong> ' + (response.salary.meets_minimum_requirement ? 'Şartı Karşılıyor' : 'Şartı Karşılamıyor') + '</small>';
                html += '</div>';
                $('#quick-salary-result').html(html);
            } else {
                $('#quick-salary-result').html('<div class="alert alert-danger p-2"><small>' + response.message + '</small></div>');
            }
        },
        error: function() {
            $('#quick-salary-result').html('<div class="alert alert-danger p-2"><small>Hesaplama hatası!</small></div>');
        }
    });
});

// Maaş istatistiklerini yükle
function loadSalaryStats() {
    $.ajax({
        url: 'get_salary_stats.php',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                $('#salary-stats').html(response.html);
            } else {
                $('#salary-stats').html('<div class="alert alert-danger">İstatistikler yüklenemedi!</div>');
            }
        },
        error: function() {
            $('#salary-stats').html('<div class="alert alert-danger">İstatistikler yüklenirken hata oluştu!</div>');
        }
    });
}

// Sistem ayarları özetini yükle
function loadSalarySettingsSummary() {
    $.ajax({
        url: 'get_salary_settings_summary.php',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                $('#salary-settings-summary').html(response.html);
            } else {
                $('#salary-settings-summary').html('<div class="text-muted"><small>Ayarlar yüklenemedi</small></div>');
            }
        },
        error: function() {
            $('#salary-settings-summary').html('<div class="text-muted"><small>Yükleme hatası</small></div>');
        }
    });
}

// Maaş genel bakışını yükle
function loadSalaryOverview() {
    $.ajax({
        url: 'get_salary_overview.php',
        data: {
            month: $('#salary-filter-month').val(),
            department: $('#salary-filter-department').val()
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                $('#salary-overview-table').html(response.html);
            } else {
                $('#salary-overview-table').html('<div class="alert alert-danger">Veriler yüklenemedi!</div>');
            }
        },
        error: function() {
            $('#salary-overview-table').html('<div class="alert alert-danger">Veriler yüklenirken hata oluştu!</div>');
        }
    });
}

// Maaş genel bakışını filtrele
function filterSalaryOverview() {
    loadSalaryOverview();
}

// Maaş genel bakışını yenile
function refreshSalaryOverview() {
    loadSalaryOverview();
}

// Sayı formatlama fonksiyonu (eğer yoksa ekleyin)
function numberFormat(number) {
    return new Intl.NumberFormat('tr-TR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(number);
}
        // Sistem ayarlarını kaydet
        $('#system-settings-form').submit(function(e) {
            e.preventDefault();
            $.ajax({
                type: 'POST',
                url: 'save_settings.php',
                data: $(this).serialize(),
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        showAlert('Sistem ayarları başarıyla kaydedildi.');
                    } else {
                        showAlert('Sistem ayarları kaydedilirken bir hata oluştu: ' + response.message, 'danger');
                    }
                },
                error: function() {
                    showAlert('Sistem ayarları kaydedilirken bir hata oluştu!', 'danger');
                }
            });
        });
        // Tema değiştirme fonksiyonları
        function loadTheme() {
            var theme = localStorage.getItem('pdks-theme') || 'light';
            applyTheme(theme);
        }
        function applyTheme(theme) {
            document.documentElement.setAttribute('data-theme', theme);
            localStorage.setItem('pdks-theme', theme);
            // Dropdown menüde seçili temayı işaretle
            $('.change-theme').removeClass('active');
            $('.change-theme[data-theme="' + theme + '"]').addClass('active');
        }
        // Tema değiştirme butonlarına tıklayınca
        $('.change-theme').click(function(e) {
            e.preventDefault();
            var theme = $(this).data('theme');
            applyTheme(theme);
        });
        // Tema seçicisini aç/kapat
        $('#theme-toggle').click(function() {
            $('#theme-options').fadeToggle();
        });
        // Tema seçimi
        $('.theme-option').click(function() {
            var theme = $(this).data('theme');
            applyTheme(theme);
            $('#theme-options').fadeOut();
        });
        // Dışarı tıklayınca tema seçiciyi kapat
        $(document).click(function(e) {
            if (!$(e.target).closest('#theme-toggle, #theme-options').length) {
                $('#theme-options').fadeOut();
            }
        });
        // Raporlar sekmesine hızlı geçiş
        $('#quick-reports').click(function(e) {
            e.preventDefault();
            $('#reports-tab').tab('show');
        });
        
        // Maaş sekmesi tıklandığında personel listesini güncelle
        $('#salary-tab').on('click', function() {
            updateSalaryPersonnelDropdown();
        });

        // İlk yüklemede gösterge panelini güncelle ve temayı yükle
        updateDashboard();
        loadTheme();
    });
    </script>
</body>
</html>
