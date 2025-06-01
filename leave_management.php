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
    
    // İzin türlerini al
    $stmt = $conn->query("SELECT * FROM leave_types ORDER BY name");
    $leave_types = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // İzin talebini onayla veya reddet
    // İzin talebini onayla veya reddet
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_request'])) {
    $request_id = $_POST['request_id'] ?? '';
    $status = $_POST['status'] ?? '';
    $comment = $_POST['comment'] ?? '';
    
    if (empty($request_id) || empty($status)) {
        $error_message = 'Geçersiz istek!';
    } else {
        // Önce isteği getir
        $stmt = $conn->prepare("SELECT * FROM leave_requests WHERE id = :id");
        $stmt->bindParam(':id', $request_id);
        $stmt->execute();
        $request = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // İzin türünü al
        $stmt = $conn->prepare("SELECT name FROM leave_types WHERE id = :id");
        $stmt->bindParam(':id', $request['leave_type_id']);
        $stmt->execute();
        $leaveType = $stmt->fetch(PDO::FETCH_ASSOC);

        // Departman yöneticisi ekleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_manager'])) {
    $manager_department = $_POST['manager_department'] ?? '';
    $manager_id = $_POST['manager_id'] ?? '';
    
    if (empty($manager_department) || empty($manager_id)) {
        $error_message = 'Lütfen tüm gerekli alanları doldurun!';
    } else {
        // Zaten var mı kontrol et
        $stmt = $conn->prepare("SELECT id FROM department_managers WHERE department = :department AND manager_id = :manager_id");
        $stmt->bindParam(':department', $manager_department);
        $stmt->bindParam(':manager_id', $manager_id);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $error_message = 'Bu departman için zaten bu yönetici atanmış!';
        } else {
            // Ekle
            $stmt = $conn->prepare("INSERT INTO department_managers (department, manager_id) VALUES (:department, :manager_id)");
            $stmt->bindParam(':department', $manager_department);
            $stmt->bindParam(':manager_id', $manager_id);
            $stmt->execute();
            
            $success_message = 'Departman yöneticisi başarıyla eklendi.';
        }
    }
}

// Departman yöneticisi silme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_manager'])) {
    $manager_id_delete = $_POST['manager_id_delete'] ?? '';
    
    if (empty($manager_id_delete)) {
        $error_message = 'Geçersiz istek!';
    } else {
        $stmt = $conn->prepare("DELETE FROM department_managers WHERE id = :id");
        $stmt->bindParam(':id', $manager_id_delete);
        $stmt->execute();
        
        $success_message = 'Departman yöneticisi başarıyla silindi.';
    }
}

// İzin onaylama yetkisi değiştirme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_approval'])) {
    $manager_id_toggle = $_POST['manager_id_toggle'] ?? '';
    $can_approve = $_POST['can_approve'] ?? '0';
    
    if (empty($manager_id_toggle)) {
        $error_message = 'Geçersiz istek!';
    } else {
        $stmt = $conn->prepare("UPDATE department_managers SET can_approve_leave = :can_approve WHERE id = :id");
        $stmt->bindParam(':id', $manager_id_toggle);
        $stmt->bindParam(':can_approve', $can_approve);
        $stmt->execute();
        
        $success_message = 'İzin onaylama yetkisi başarıyla güncellendi.';
    }
}
        
        // Kullanıcı bilgilerini al
        $stmt = $conn->prepare("SELECT name, surname, email, department, position FROM cards WHERE user_id = :user_id");
        $stmt->bindParam(':user_id', $request['user_id']);
        $stmt->execute();
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // İzin talebini güncelle
        $stmt = $conn->prepare("UPDATE leave_requests SET status = :status, comment = :comment, updated_at = NOW() WHERE id = :id");
        $stmt->bindParam(':id', $request_id);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':comment', $comment);
        $stmt->execute();
        
        // Eğer onaylandıysa, kullanıcının izin bakiyesini güncelle
        if ($status === 'approved') {
            $user_id = $request['user_id'];
            $leave_type_id = $request['leave_type_id'];
            $total_days = $request['total_days'];
            $year = date('Y');
            
            // Kullanıcının bu yıl için izin bakiyesi var mı kontrol et
            $stmt = $conn->prepare("SELECT * FROM leave_balances WHERE user_id = :user_id AND leave_type_id = :leave_type_id AND year = :year");
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':leave_type_id', $leave_type_id);
            $stmt->bindParam(':year', $year);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                // Bakiye varsa güncelle
                $stmt = $conn->prepare("UPDATE leave_balances SET used_days = used_days + :total_days WHERE user_id = :user_id AND leave_type_id = :leave_type_id AND year = :year");
                $stmt->bindParam(':user_id', $user_id);
                $stmt->bindParam(':leave_type_id', $leave_type_id);
                $stmt->bindParam(':year', $year);
                $stmt->bindParam(':total_days', $total_days);
                $stmt->execute();
            } else {
                // Bakiye yoksa oluştur
                $default_days = 0; // Varsayılan olarak 0 gün
                
                // İzin türüne göre varsayılan günler belirle
                switch ($leave_type_id) {
                    case 1: // Yıllık izin
                        $default_days = 14; // Örnek değer
                        break;
                    // Diğer türler için değerler
                }
                
                $stmt = $conn->prepare("INSERT INTO leave_balances (user_id, leave_type_id, year, total_days, used_days) VALUES (:user_id, :leave_type_id, :year, :total_days, :used_days)");
                $stmt->bindParam(':user_id', $user_id);
                $stmt->bindParam(':leave_type_id', $leave_type_id);
                $stmt->bindParam(':year', $year);
                $stmt->bindParam(':total_days', $default_days);
                $stmt->bindParam(':used_days', $total_days);
                $stmt->execute();
            }
        }
        
        // E-posta gönderme işlemi
        require_once('mailer.php');
        
        // E-posta için izin verilerini hazırla
        $leaveData = [
            'status' => $status,
            'start_date' => $request['start_date'],
            'end_date' => $request['end_date'],
            'total_days' => $request['total_days'],
            'leave_type_name' => $leaveType['name'],
            'comment' => $comment
        ];
        
        // E-posta gönder
        if (!empty($userData['email'])) {
            sendLeaveStatusEmail($userData, $leaveData);
        }
        
        $success_message = 'İzin talebi başarıyla güncellendi.';
    }
}
    
    // İzin bakiyesi tanımla/güncelle
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_balance'])) {
        $balance_user_id = $_POST['balance_user_id'] ?? '';
        $balance_leave_type_id = $_POST['balance_leave_type_id'] ?? '';
        $balance_year = $_POST['balance_year'] ?? date('Y');
        $balance_total_days = $_POST['balance_total_days'] ?? 0;
        
        if (empty($balance_user_id) || empty($balance_leave_type_id) || empty($balance_year)) {
            $error_message = 'Lütfen tüm gerekli alanları doldurun!';
        } else {
            // Kullanıcının bu yıl için izin bakiyesi var mı kontrol et
            $stmt = $conn->prepare("SELECT * FROM leave_balances WHERE user_id = :user_id AND leave_type_id = :leave_type_id AND year = :year");
            $stmt->bindParam(':user_id', $balance_user_id);
            $stmt->bindParam(':leave_type_id', $balance_leave_type_id);
            $stmt->bindParam(':year', $balance_year);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                // Bakiye varsa güncelle
                $stmt = $conn->prepare("UPDATE leave_balances SET total_days = :total_days WHERE user_id = :user_id AND leave_type_id = :leave_type_id AND year = :year");
            } else {
                // Bakiye yoksa oluştur
                $stmt = $conn->prepare("INSERT INTO leave_balances (user_id, leave_type_id, year, total_days, used_days) VALUES (:user_id, :leave_type_id, :year, :total_days, 0)");
            }
            
            $stmt->bindParam(':user_id', $balance_user_id);
            $stmt->bindParam(':leave_type_id', $balance_leave_type_id);
            $stmt->bindParam(':year', $balance_year);
            $stmt->bindParam(':total_days', $balance_total_days);
            $stmt->execute();
            
            $success_message = 'İzin bakiyesi başarıyla güncellendi.';
        }
    }
    
    // Tüm kullanıcıları al
    $stmt = $conn->query("SELECT user_id, name, surname FROM cards WHERE enabled = 'true' ORDER BY name, surname");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Bekleyen izin taleplerini al
$sql = "
    SELECT lr.*, lt.name as leave_type_name, lt.color, c.name, c.surname, c.department, c.position
    FROM leave_requests lr
    JOIN leave_types lt ON lr.leave_type_id = lt.id
    JOIN cards c ON lr.user_id = c.user_id
    WHERE lr.status = 'pending'
    ORDER BY lr.created_at DESC
";

$stmt = $conn->prepare($sql);
$stmt->execute();
$pending_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

    
    // Onaylanan/reddedilen izin taleplerini al
    $stmt = $conn->prepare("
        SELECT lr.*, lt.name as leave_type_name, lt.color, c.name, c.surname, c.department, c.position
        FROM leave_requests lr
        JOIN leave_types lt ON lr.leave_type_id = lt.id
        JOIN cards c ON lr.user_id = c.user_id
        WHERE lr.status != 'pending'
        ORDER BY lr.updated_at DESC
        LIMIT 50
    ");
    $stmt->execute();
    $processed_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // İzin bakiyelerini al
    $stmt = $conn->prepare("
        SELECT lb.*, lt.name as leave_type_name, c.name, c.surname
        FROM leave_balances lb
        JOIN leave_types lt ON lb.leave_type_id = lt.id
        JOIN cards c ON lb.user_id = c.user_id
        ORDER BY lb.year DESC, c.name, c.surname
    ");
    $stmt->execute();
    $all_balances = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    $error_message = 'Veritabanı hatası: ' . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PDKS - İzin Yönetim Paneli</title>
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
        .request-card {
            border-left: 5px solid #3498db;
            border-radius: 5px;
            margin-bottom: 15px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .nav-tabs .nav-link {
            border-top-left-radius: 10px;
            border-top-right-radius: 10px;
        }
        .action-buttons .btn {
            margin-right: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <h2><i class="fas fa-user-cog mr-2"></i> İzin Yönetim Paneli</h2>
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
        
        <ul class="nav nav-tabs" id="leaveTab" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" id="pending-tab" data-toggle="tab" href="#pending" role="tab">
                    <i class="fas fa-hourglass-half mr-1"></i> Bekleyen İzinler
                    <?php if (count($pending_requests) > 0): ?>
                        <span class="badge badge-warning"><?php echo count($pending_requests); ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="processed-tab" data-toggle="tab" href="#processed" role="tab">
                    <i class="fas fa-history mr-1"></i> İşlenmiş İzinler
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="balances-tab" data-toggle="tab" href="#balances" role="tab">
                    <i class="fas fa-calculator mr-1"></i> İzin Bakiyeleri
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="types-tab" data-toggle="tab" href="#types" role="tab">
                    <i class="fas fa-list-alt mr-1"></i> İzin Türleri
                </a>
            </li>           <li class="nav-item">
    <a class="nav-link" id="managers-tab" data-toggle="tab" href="#managers" role="tab">
        <i class="fas fa-users-cog mr-1"></i> Departman Yöneticileri
    </a>
</li>
        </ul>
        
        <div class="tab-content" id="leaveTabContent">
            <!-- Bekleyen İzinler -->
            <div class="tab-pane fade show active" id="pending" role="tabpanel">
                <div class="card mt-3">
                    <div class="card-header bg-warning text-white">
                        <i class="fas fa-hourglass-half mr-1"></i> Bekleyen İzin Talepleri
                    </div>
                    <div class="card-body">
                        <?php if (count($pending_requests) > 0): ?>
                            <?php foreach ($pending_requests as $request): ?>
                                <div class="request-card p-3">
                                    <div class="row">
                                        <div class="col-md-8">
                                            <h5>
                                                <?php echo $request['name'] . ' ' . $request['surname']; ?>
                                                <span class="badge" style="background-color: <?php echo $request['color']; ?>; color: white;">
                                                    <?php echo $request['leave_type_name']; ?>
                                                </span>
                                            </h5>
                                            <div class="text-muted mb-2">
                                                <?php echo $request['department'] . ' - ' . $request['position']; ?>
                                            </div>
                                            <div>
                                                <strong>Tarih:</strong> 
                                                <?php echo date('d.m.Y', strtotime($request['start_date'])); ?> - 
                                                <?php echo date('d.m.Y', strtotime($request['end_date'])); ?>
                                                (<?php echo $request['total_days']; ?> gün)
                                            </div>
                                            <div class="mt-2">
                                                <strong>Açıklama:</strong> 
                                                <?php echo !empty($request['reason']) ? $request['reason'] : 'Belirtilmemiş'; ?>
                                            </div>
                                            <div class="mt-2">
                                                <strong>Talep Tarihi:</strong> 
                                                <?php echo date('d.m.Y H:i', strtotime($request['created_at'])); ?>
                                            </div>
                                        </div>
                                        <div class="col-md-4 text-right">
                                            <div class="action-buttons">
                                                <button type="button" class="btn btn-success approve-btn" 
                                                        data-id="<?php echo $request['id']; ?>"
                                                        data-name="<?php echo $request['name'] . ' ' . $request['surname']; ?>"
                                                        data-type="<?php echo $request['leave_type_name']; ?>"
                                                        data-start="<?php echo date('d.m.Y', strtotime($request['start_date'])); ?>"
                                                        data-end="<?php echo date('d.m.Y', strtotime($request['end_date'])); ?>"
                                                        data-days="<?php echo $request['total_days']; ?>">
                                                    <i class="fas fa-check mr-1"></i> Onayla
                                                </button>
                                                <button type="button" class="btn btn-danger reject-btn"
                                                        data-id="<?php echo $request['id']; ?>"
                                                        data-name="<?php echo $request['name'] . ' ' . $request['surname']; ?>"
                                                        data-type="<?php echo $request['leave_type_name']; ?>"
                                                        data-start="<?php echo date('d.m.Y', strtotime($request['start_date'])); ?>"
                                                        data-end="<?php echo date('d.m.Y', strtotime($request['end_date'])); ?>"
                                                        data-days="<?php echo $request['total_days']; ?>">
                                                    <i class="fas fa-times mr-1"></i> Reddet
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle mr-1"></i> Bekleyen izin talebi bulunmamaktadır.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <!-- Departman Yöneticileri -->
<div class="tab-pane fade" id="managers" role="tabpanel">
    <div class="card mt-3">
        <div class="card-header bg-secondary text-white">
            <i class="fas fa-users-cog mr-1"></i> Departman Yöneticileri
        </div>
        <div class="card-body">
            <form method="post" action="" id="add-manager-form">
                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label for="manager_department">Departman</label>
                        <select class="form-control" id="manager_department" name="manager_department" required>
                            <option value="">Seçiniz</option>
                            <?php
                            $stmt = $conn->query("SELECT DISTINCT department FROM cards WHERE department != '' ORDER BY department");
                            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                echo '<option value="'.$row['department'].'">'.$row['department'].'</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="manager_id">Yönetici</label>
                        <select class="form-control" id="manager_id" name="manager_id" required>
                            <option value="">Seçiniz</option>
                            <?php
                            $stmt = $conn->query("SELECT user_id, name, surname FROM cards WHERE privilege >= 1 ORDER BY name, surname");
                            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                echo '<option value="'.$row['user_id'].'">'.$row['name'].' '.$row['surname'].'</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group col-md-2">
                        <label>&nbsp;</label>
                        <button type="submit" name="add_manager" class="btn btn-success btn-block">
                            <i class="fas fa-plus-circle mr-1"></i> Ekle
                        </button>
                    </div>
                </div>
            </form>
            
            <hr>
            
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Departman</th>
                            <th>Yönetici</th>
                            <th>İzin Onaylama Yetkisi</th>
                            <th>İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $stmt = $conn->query("
                            SELECT dm.*, c.name, c.surname
                            FROM department_managers dm
                            JOIN cards c ON dm.manager_id = c.user_id
                            ORDER BY dm.department, c.name
                        ");
                        $managers = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        if (count($managers) > 0) {
                            foreach ($managers as $manager) {
                                echo '<tr>';
                                echo '<td>'.$manager['department'].'</td>';
                                echo '<td>'.$manager['name'].' '.$manager['surname'].'</td>';
                                echo '<td>'.($manager['can_approve_leave'] ? '<span class="badge badge-success">Var</span>' : '<span class="badge badge-secondary">Yok</span>').'</td>';
                                echo '<td>
                                        <form method="post" action="" class="d-inline">
                                            <input type="hidden" name="manager_id_delete" value="'.$manager['id'].'">
                                            <button type="submit" name="delete_manager" class="btn btn-sm btn-danger" onclick="return confirm(\'Bu yöneticiyi silmek istediğinizden emin misiniz?\')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                        <form method="post" action="" class="d-inline">
                                            <input type="hidden" name="manager_id_toggle" value="'.$manager['id'].'">
                                            <input type="hidden" name="can_approve" value="'.($manager['can_approve_leave'] ? '0' : '1').'">
                                            <button type="submit" name="toggle_approval" class="btn btn-sm '.($manager['can_approve_leave'] ? 'btn-warning' : 'btn-success').'">
                                                <i class="fas '.($manager['can_approve_leave'] ? 'fa-times' : 'fa-check').'"></i>
                                            </button>
                                        </form>
                                    </td>';
                                echo '</tr>';
                            }
                        } else {
                            echo '<tr><td colspan="4" class="text-center">Henüz departman yöneticisi atanmamış.</td></tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
            
            <!-- İşlenmiş İzinler -->
<div class="tab-pane fade" id="processed" role="tabpanel">
    <div class="card mt-3">
        <div class="card-header bg-info text-white">
            <i class="fas fa-filter mr-1"></i> Filtreleme
        </div>
        <div class="card-body">
            <form id="filter-form" class="mb-3">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="filter-name">Personel Adı</label>
                            <input type="text" class="form-control" id="filter-name" placeholder="İsim ara...">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="filter-department">Departman</label>
                            <select class="form-control" id="filter-department">
                                <option value="">Tümü</option>
                                <?php
                                // Departmanları getir
                                $stmt = $conn->query("SELECT DISTINCT department FROM cards WHERE department != '' ORDER BY department");
                                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                    echo '<option value="'.$row['department'].'">'.$row['department'].'</option>';
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="filter-leave-type">İzin Türü</label>
                            <select class="form-control" id="filter-leave-type">
                                <option value="">Tümü</option>
                                <?php foreach ($leave_types as $type): ?>
                                    <option value="<?php echo $type['id']; ?>"><?php echo $type['name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="filter-status">Durum</label>
                            <select class="form-control" id="filter-status">
                                <option value="">Tümü</option>
                                <option value="approved">Onaylanan</option>
                                <option value="rejected">Reddedilen</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="filter-start-date">Başlangıç Tarihi</label>
                            <input type="date" class="form-control" id="filter-start-date">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="filter-end-date">Bitiş Tarihi</label>
                            <input type="date" class="form-control" id="filter-end-date">
                        </div>
                    </div>
                    <div class="col-md-6 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary mr-2">
                            <i class="fas fa-search mr-1"></i> Filtrele
                        </button>
                        <button type="button" id="clear-filter" class="btn btn-secondary">
                            <i class="fas fa-eraser mr-1"></i> Temizle
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <div class="card mt-3">
        <div class="card-header bg-info text-white">
            <i class="fas fa-history mr-1"></i> İşlenmiş İzin Talepleri
        </div>
        <div class="card-body">
            <div id="processed-requests-container">
                <!-- İçerik AJAX ile doldurulacak -->
                <div class="text-center">
                    <div class="spinner-border text-info" role="status">
                        <span class="sr-only">Yükleniyor...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
            
            <!-- İzin Bakiyeleri -->
            <div class="tab-pane fade" id="balances" role="tabpanel">
                <div class="card mt-3">
                    <div class="card-header bg-success text-white">
                        <i class="fas fa-plus-circle mr-1"></i> İzin Bakiyesi Tanımla/Güncelle
                    </div>
                    <div class="card-body">
                        <form method="post" action="">
                            <div class="form-row">
                                <div class="form-group col-md-4">
                                    <label for="balance_user_id">Personel</label>
                                    <select class="form-control" id="balance_user_id" name="balance_user_id" required>
                                        <option value="">Seçiniz</option>
                                        <?php foreach ($users as $user): ?>
                                            <option value="<?php echo $user['user_id']; ?>"><?php echo $user['name'] . ' ' . $user['surname']; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group col-md-3">
                                    <label for="balance_leave_type_id">İzin Türü</label>
                                    <select class="form-control" id="balance_leave_type_id" name="balance_leave_type_id" required>
                                        <option value="">Seçiniz</option>
                                        <?php foreach ($leave_types as $type): ?>
                                            <option value="<?php echo $type['id']; ?>"><?php echo $type['name']; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="balance_year">Yıl</label>
                                    <select class="form-control" id="balance_year" name="balance_year" required>
                                        <?php 
                                        $current_year = (int)date('Y');
                                        for ($i = $current_year - 2; $i <= $current_year + 1; $i++) {
                                            $selected = ($i == $current_year) ? 'selected' : '';
                                            echo "<option value=\"$i\" $selected>$i</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="balance_total_days">Toplam Gün</label>
                                    <input type="number" class="form-control" id="balance_total_days" name="balance_total_days" min="0" step="0.5" required>
                                </div>
                                <div class="form-group col-md-1">
                                    <label>&nbsp;</label>
                                    <button type="submit" name="update_balance" class="btn btn-success btn-block">
                                        <i class="fas fa-save"></i>
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                
                <div class="card mt-3">
                    <div class="card-header bg-success text-white">
                        <i class="fas fa-calculator mr-1"></i> İzin Bakiyeleri
                    </div>
                    <div class="card-body">
                        <?php if (count($all_balances) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Personel</th>
                                            <th>İzin Türü</th>
                                            <th>Yıl</th>
                                            <th>Toplam Gün</th>
                                            <th>Kullanılan</th>
                                            <th>Kalan</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($all_balances as $balance): ?>
                                            <tr>
                                                <td><?php echo $balance['name'] . ' ' . $balance['surname']; ?></td>
                                                <td><?php echo $balance['leave_type_name']; ?></td>
                                                <td><?php echo $balance['year']; ?></td>
                                                <td><?php echo $balance['total_days']; ?></td>
                                                <td><?php echo $balance['used_days']; ?></td>
                                                <td><strong><?php echo $balance['remaining_days']; ?></strong></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle mr-1"></i> Tanımlanmış izin bakiyesi bulunmamaktadır.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- İzin Türleri -->
            <div class="tab-pane fade" id="types" role="tabpanel">
                <div class="card mt-3">
                    <div class="card-header bg-primary text-white">
                        <i class="fas fa-list-alt mr-1"></i> İzin Türleri
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>İzin Türü</th>
                                        <th>Açıklama</th>
                                        <th>Maksimum Gün</th>
                                        <th>Renk</th>
                                        <th>Durum</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($leave_types as $type): ?>
                                        <tr>
                                            <td><?php echo $type['name']; ?></td>
                                            <td><?php echo $type['description']; ?></td>
                                            <td><?php echo $type['max_days'] ? $type['max_days'] . ' gün' : 'Limitsiz'; ?></td>
                                            <td>
                                                <div style="width: 20px; height: 20px; background-color: <?php echo $type['color']; ?>; border-radius: 50%;"></div>
                                            </td>
                                            <td>
                                                <?php echo $type['is_active'] ? '<span class="badge badge-success">Aktif</span>' : '<span class="badge badge-danger">Pasif</span>'; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Onaylama Modal -->
    <div class="modal fade" id="approveModal" tabindex="-1" role="dialog" aria-labelledby="approveModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="approveModalLabel">İzin Talebini Onayla</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Kapat">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form method="post" action="">
                    <div class="modal-body">
                        <input type="hidden" name="request_id" id="approve_request_id">
                        <input type="hidden" name="status" value="approved">
                        
                        <div class="request-details mb-3"></div>
                        
                        <div class="form-group">
                            <label for="approve_comment">Not (Opsiyonel)</label>
                            <textarea class="form-control" id="approve_comment" name="comment" rows="3"></textarea>
                        </div>
                        
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle mr-1"></i> Bu işlem personelin izin bakiyesinden düşülecektir.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">İptal</button>
                        <button type="submit" name="update_request" class="btn btn-success">Onayla</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Reddetme Modal -->
    <div class="modal fade" id="rejectModal" tabindex="-1" role="dialog" aria-labelledby="rejectModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="rejectModalLabel">İzin Talebini Reddet</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Kapat">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form method="post" action="">
                    <div class="modal-body">
                        <input type="hidden" name="request_id" id="reject_request_id">
                        <input type="hidden" name="status" value="rejected">
                        
                        <div class="request-details mb-3"></div>
                        
                        <div class="form-group">
                            <label for="reject_comment">Ret Nedeni</label>
                            <textarea class="form-control" id="reject_comment" name="comment" rows="3" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">İptal</button>
                        <button type="submit" name="update_request" class="btn btn-danger">Reddet</button>
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
            // Onaylama butonuna tıklandığında
            $('.approve-btn').click(function() {
                var id = $(this).data('id');
                var name = $(this).data('name');
                var type = $(this).data('type');
                var start = $(this).data('start');
                var end = $(this).data('end');
                var days = $(this).data('days');
                
                // Modal'a verileri doldur
                $('#approve_request_id').val(id);
                var details = `
                    <p><strong>Personel:</strong> ${name}</p>
                    <p><strong>İzin Türü:</strong> ${type}</p>
                    <p><strong>Tarih:</strong> ${start} - ${end} (${days} gün)</p>
                `;
                $('#approveModal .request-details').html(details);
                
                // Modal'ı göster
                $('#approveModal').modal('show');
            });
            
            // Reddetme butonuna tıklandığında
            $('.reject-btn').click(function() {
                var id = $(this).data('id');
                var name = $(this).data('name');
                var type = $(this).data('type');
                var start = $(this).data('start');
                var end = $(this).data('end');
                var days = $(this).data('days');
                
                // Modal'a verileri doldur
                $('#reject_request_id').val(id);
                var details = `
                    <p><strong>Personel:</strong> ${name}</p>
                    <p><strong>İzin Türü:</strong> ${type}</p>
                    <p><strong>Tarih:</strong> ${start} - ${end} (${days} gün)</p>
                `;
                $('#rejectModal .request-details').html(details);
                
                // Modal'ı göster
                $('#rejectModal').modal('show');
            });
            
            // URL'deki hash'e göre sekme değiştirme
            var hash = window.location.hash;
            if (hash) {
                $('.nav-tabs a[href="' + hash + '"]').tab('show');
            }
            
            // Sekme değiştiğinde URL hash'ini güncelle
            $('.nav-tabs a').on('shown.bs.tab', function(e) {
                window.location.hash = e.target.hash;
            });
        });
        // Sayfa yüklendiğinde işlenmiş izin taleplerini getir
$(document).ready(function() {
    // Varsayılan olarak ilk sekmedeyiz, bekleyen izinler yüklendi
    
    // Sekme değiştiğinde ilgili içeriği yükle
    $('.nav-tabs a').on('shown.bs.tab', function(e) {
        var target = $(e.target).attr("href");
        
        if (target === '#processed') {
            loadProcessedLeaves();
        }
    });
    
    // Filtreleme formunu gönderme
    $('#filter-form').submit(function(e) {
        e.preventDefault();
        loadProcessedLeaves();
    });
    
    // Filtreleri temizleme
    $('#clear-filter').click(function() {
        $('#filter-name').val('');
        $('#filter-department').val('');
        $('#filter-leave-type').val('');
        $('#filter-status').val('');
        $('#filter-start-date').val('');
        $('#filter-end-date').val('');
        loadProcessedLeaves();
    });
});

// İşlenmiş izin taleplerini yükle
function loadProcessedLeaves() {
    // Filtre değerlerini al
    var name = $('#filter-name').val();
    var department = $('#filter-department').val();
    var leaveType = $('#filter-leave-type').val();
    var status = $('#filter-status').val();
    var startDate = $('#filter-start-date').val();
    var endDate = $('#filter-end-date').val();
    
    // Yükleniyor göster
    $('#processed-requests-container').html('<div class="text-center"><div class="spinner-border text-info" role="status"><span class="sr-only">Yükleniyor...</span></div></div>');
    
    // AJAX isteği
    $.ajax({
        url: 'get_processed_leaves.php',
        data: {
            name: name,
            department: department,
            leave_type: leaveType,
            status: status,
            start_date: startDate,
            end_date: endDate
        },
        dataType: 'json',
        success: function(data) {
            if (data.success) {
                $('#processed-requests-container').html(data.html);
            } else {
                $('#processed-requests-container').html('<div class="alert alert-danger">' + data.message + '</div>');
            }
        },
        error: function() {
            $('#processed-requests-container').html('<div class="alert alert-danger">Veriler yüklenirken bir hata oluştu!</div>');
        }
    });
}
    </script>
</body>
</html>