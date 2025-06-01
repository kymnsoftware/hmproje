<?php
session_start();
// Oturum kontrolü
if (!isset($_SESSION['user_id'])) {
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
    
    // İzin türlerini al
    $stmt = $conn->query("SELECT * FROM leave_types WHERE is_active = 1 ORDER BY name");
    $leave_types = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // İzin talebi oluşturma
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_request'])) {
    $leave_type_id = $_POST['leave_type_id'] ?? '';
    $start_date = $_POST['start_date'] ?? '';
    $end_date = $_POST['end_date'] ?? '';
    $reason = $_POST['reason'] ?? '';
    
    // Basit doğrulama
    if (empty($leave_type_id) || empty($start_date) || empty($end_date)) {
        $error_message = 'Lütfen tüm gerekli alanları doldurun!';
    } else {
        // Toplam gün sayısını hesapla
        $start = new DateTime($start_date);
        $end = new DateTime($end_date);
        $end->modify('+1 day'); // Bitiş günü dahil
        $interval = $start->diff($end);
        $total_days = $interval->days;
        
        // İzin talebini kaydet
        $stmt = $conn->prepare("INSERT INTO leave_requests (user_id, leave_type_id, start_date, end_date, total_days, reason) 
                               VALUES (:user_id, :leave_type_id, :start_date, :end_date, :total_days, :reason)");
        $stmt->bindParam(':user_id', $_SESSION['user_id']);
        $stmt->bindParam(':leave_type_id', $leave_type_id);
        $stmt->bindParam(':start_date', $start_date);
        $stmt->bindParam(':end_date', $end_date);
        $stmt->bindParam(':total_days', $total_days);
        $stmt->bindParam(':reason', $reason);
        $stmt->execute();
        
        $lastId = $conn->lastInsertId();
        
        // İzin türünü al
        $stmt = $conn->prepare("SELECT name FROM leave_types WHERE id = :id");
        $stmt->bindParam(':id', $leave_type_id);
        $stmt->execute();
        $leaveType = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Yönetici e-postalarını al (privilege >= 2 olan kullanıcılar)
        $stmt = $conn->prepare("SELECT email FROM cards WHERE privilege >= 2 AND email IS NOT NULL AND email != ''");
        $stmt->execute();
        $managerEmails = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // E-posta gönderme
        if (!empty($managerEmails)) {
            require_once('mailer.php');
            
            // E-posta için veriler
            $userData = [
                'name' => $_SESSION['user_name'],
                'surname' => '',
                'department' => $_SESSION['department'],
                'position' => $_SESSION['position']
            ];
            
            $leaveData = [
                'id' => $lastId,
                'leave_type_name' => $leaveType['name'],
                'start_date' => $start_date,
                'end_date' => $end_date,
                'total_days' => $total_days,
                'reason' => $reason
            ];
            
            sendNewLeaveRequestEmail($managerEmails, $userData, $leaveData);
        }
        
        $success_message = 'İzin talebiniz başarıyla oluşturuldu.';
    }
}
    
    // Kullanıcının izin taleplerini al
    $stmt = $conn->prepare("
        SELECT lr.*, lt.name as leave_type_name, lt.color
        FROM leave_requests lr
        JOIN leave_types lt ON lr.leave_type_id = lt.id
        WHERE lr.user_id = :user_id
        ORDER BY lr.created_at DESC
    ");
    $stmt->bindParam(':user_id', $_SESSION['user_id']);
    $stmt->execute();
    $user_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Kullanıcının izin bakiyelerini al
    $stmt = $conn->prepare("
        SELECT lb.*, lt.name as leave_type_name
        FROM leave_balances lb
        JOIN leave_types lt ON lb.leave_type_id = lt.id
        WHERE lb.user_id = :user_id AND lb.year = :year
        ORDER BY lt.name
    ");
    $current_year = date('Y');
    $stmt->bindParam(':user_id', $_SESSION['user_id']);
    $stmt->bindParam(':year', $current_year);
    $stmt->execute();
    $user_balances = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    $error_message = 'Veritabanı hatası: ' . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PDKS - İzin Talep Sistemi</title>
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
        .leave-status {
            font-weight: bold;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
        }
        .status-pending {
            background-color: #f39c12;
            color: white;
        }
        .status-approved {
            background-color: #2ecc71;
            color: white;
        }
        .status-rejected {
            background-color: #e74c3c;
            color: white;
        }
        .balance-card {
            text-align: center;
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 20px;
        }
        .balance-header {
            padding: 10px;
            color: white;
            font-weight: bold;
        }
        .balance-body {
            padding: 15px 10px;
        }
        .balance-value {
            font-size: 24px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <h2><i class="fas fa-calendar-alt mr-2"></i> İzin Talep Sistemi</h2>
            </div>
            <div class="user-info">
                <img src="<?php echo $_SESSION['photo_path']; ?>" class="user-photo" alt="Profil">
                <div>
                    <h5><?php echo $_SESSION['user_name']; ?></h5>
                    <p class="text-muted mb-0"><?php echo $_SESSION['department'] . ' - ' . $_SESSION['position']; ?></p>
                </div>
            </div>
            <div>
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
        
        <div class="row">
            <div class="col-md-8">
                <!-- İzin Talep Formu -->
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <i class="fas fa-plus-circle mr-1"></i> Yeni İzin Talebi
                    </div>
                    <div class="card-body">
                        <form method="post" action="">
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="leave_type_id">İzin Türü</label>
                                    <select class="form-control" id="leave_type_id" name="leave_type_id" required>
                                        <option value="">Seçiniz</option>
                                        <?php foreach ($leave_types as $type): ?>
                                            <option value="<?php echo $type['id']; ?>"><?php echo $type['name']; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="start_date">Başlangıç Tarihi</label>
                                    <input type="date" class="form-control" id="start_date" name="start_date" required min="<?php echo date('Y-m-d'); ?>">
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="end_date">Bitiş Tarihi</label>
                                    <input type="date" class="form-control" id="end_date" name="end_date" required min="<?php echo date('Y-m-d'); ?>">
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="reason">Açıklama / Sebep</label>
                                <textarea class="form-control" id="reason" name="reason" rows="3"></textarea>
                            </div>
                            <button type="submit" name="submit_request" class="btn btn-primary">
                                <i class="fas fa-paper-plane mr-1"></i> Talep Oluştur
                            </button>
                        </form>
                    </div>
                </div>
                
                <!-- İzin Talep Geçmişi -->
                <div class="card mt-4">
                    <div class="card-header bg-info text-white">
                        <i class="fas fa-history mr-1"></i> İzin Talebi Geçmişi
                    </div>
                    <div class="card-body">
                        <?php if (count($user_requests) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>İzin Türü</th>
                                            <th>Başlangıç</th>
                                            <th>Bitiş</th>
                                            <th>Süre</th>
                                            <th>Durum</th>
                                            <th>Oluşturulma</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($user_requests as $request): ?>
                                            <tr>
                                                <td>
                                                    <span class="badge" style="background-color: <?php echo $request['color']; ?>; color: white;">
                                                        <?php echo $request['leave_type_name']; ?>
                                                    </span>
                                                </td>
                                                <td><?php echo date('d.m.Y', strtotime($request['start_date'])); ?></td>
                                                <td><?php echo date('d.m.Y', strtotime($request['end_date'])); ?></td>
                                                <td><?php echo $request['total_days']; ?> gün</td>
                                                <td>
                                                    <?php 
                                                    if ($request['status'] == 'pending') {
                                                        echo '<span class="leave-status status-pending">Beklemede</span>';
                                                    } elseif ($request['status'] == 'approved') {
                                                        echo '<span class="leave-status status-approved">Onaylandı</span>';
                                                    } else {
                                                        echo '<span class="leave-status status-rejected">Reddedildi</span>';
                                                    }
                                                    ?>
                                                </td>
                                                <td><?php echo date('d.m.Y H:i', strtotime($request['created_at'])); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-muted">Henüz izin talebiniz bulunmamaktadır.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <!-- İzin Bakiyeleri -->
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <i class="fas fa-calculator mr-1"></i> İzin Bakiyelerim (<?php echo date('Y'); ?>)
                    </div>
                    <div class="card-body">
                        <?php if (count($user_balances) > 0): ?>
                            <div class="row">
                                <?php foreach ($user_balances as $balance): ?>
                                    <div class="col-md-12">
                                        <div class="balance-card">
                                            <div class="balance-header bg-info">
                                                <?php echo $balance['leave_type_name']; ?>
                                            </div>
                                            <div class="balance-body">
                                                <div class="balance-value"><?php echo $balance['remaining_days']; ?></div>
                                                <div class="text-muted">Kalan Gün</div>
                                                <hr>
                                                <div class="row">
                                                    <div class="col-6">
                                                        <div>Toplam: <b><?php echo $balance['total_days']; ?></b></div>
                                                    </div>
                                                    <div class="col-6">
                                                        <div>Kullanılan: <b><?php echo $balance['used_days']; ?></b></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle mr-1"></i> Henüz izin bakiyeniz tanımlanmamış.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Yardım Bilgileri -->
                <div class="card mt-3">
                    <div class="card-header bg-secondary text-white">
                        <i class="fas fa-question-circle mr-1"></i> Yardım
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled">
                            <li><i class="fas fa-info-circle text-info mr-2"></i> İzin talebi oluşturmak için formu doldurun.</li>
                            <li class="mt-2"><i class="fas fa-info-circle text-info mr-2"></i> Talebiniz yöneticiniz tarafından değerlendirilecektir.</li>
                            <li class="mt-2"><i class="fas fa-info-circle text-info mr-2"></i> İzin geçmişinizi alt bölümden takip edebilirsiniz.</li>
                            <li class="mt-2"><i class="fas fa-info-circle text-info mr-2"></i> Sorun yaşarsanız yöneticinizle iletişime geçin.</li>
                        </ul>
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
            // Bitiş tarihi kontrolü
            $('#start_date').change(function() {
                $('#end_date').attr('min', $(this).val());
            });
        });
    </script>
</body>
</html>