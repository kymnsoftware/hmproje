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

$success_message = '';
$error_message = '';

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Tatil ekleme
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_holiday'])) {
        $holiday_date = $_POST['holiday_date'] ?? '';
        $name = $_POST['name'] ?? '';
        
        if (!empty($holiday_date) && !empty($name)) {
            $stmt = $conn->prepare("INSERT INTO holidays (holiday_date, name) VALUES (:holiday_date, :name)");
            $stmt->bindParam(':holiday_date', $holiday_date);
            $stmt->bindParam(':name', $name);
            $stmt->execute();
            $success_message = 'Resmi tatil başarıyla eklendi.';
        } else {
            $error_message = 'Tarih ve tatil adı gereklidir!';
        }
    }
    
    // Tatil silme
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_holiday'])) {
        $holiday_id = $_POST['holiday_id'] ?? '';
        if (!empty($holiday_id)) {
            $stmt = $conn->prepare("DELETE FROM holidays WHERE id = :id");
            $stmt->bindParam(':id', $holiday_id);
            $stmt->execute();
            $success_message = 'Resmi tatil başarıyla silindi.';
        }
    }
    
    // Tatilleri al
    $stmt = $conn->query("SELECT * FROM holidays ORDER BY holiday_date DESC");
    $holidays = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    $error_message = 'Veritabanı hatası: ' . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PDKS - Resmi Tatil Yönetimi</title>
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
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <h2><i class="fas fa-calendar-times mr-2"></i> Resmi Tatil Yönetimi</h2>
            </div>
            <div class="user-info">
                <img src="<?php echo $_SESSION['photo_path']; ?>" class="user-photo" alt="Profil">
                <div>
                    <h5><?php echo $_SESSION['user_name']; ?></h5>
                    <p class="text-muted mb-0"><?php echo $_SESSION['department'] . ' - ' . $_SESSION['position']; ?></p>
                </div>
            </div>
            <div>
                <a href="salary_management.php" class="btn btn-outline-success mr-2">
                    <i class="fas fa-money-bill-wave mr-1"></i> Maaş Yönetimi
                </a>
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

        <div class="row">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <i class="fas fa-plus-circle mr-1"></i> Yeni Resmi Tatil Ekle
                    </div>
                    <div class="card-body">
                        <form method="post" action="">
                            <div class="form-group">
                                <label for="holiday_date">Tatil Tarihi</label>
                                <input type="date" class="form-control" id="holiday_date" name="holiday_date" required>
                            </div>
                            <div class="form-group">
                                <label for="name">Tatil Adı</label>
                                <input type="text" class="form-control" id="name" name="name" placeholder="Örn: Cumhuriyet Bayramı" required>
                            </div>
                            <button type="submit" name="add_holiday" class="btn btn-primary btn-block">
                                <i class="fas fa-save mr-1"></i> Ekle
                            </button>
                        </form>
                        
                        <hr>
                        
                        <div class="alert alert-info">
                            <h6><i class="fas fa-info-circle mr-1"></i> Bilgi:</h6>
                            <small>
                                Resmi tatiller maaş hesaplamalarında iş günü sayısından çıkarılır. 
                                Bu günler çalışma zorunluluğu olmadığı için maaş kesintisine tabi değildir.
                            </small>
                        </div>
                        
                        <button type="button" class="btn btn-success btn-sm" onclick="addCommonHolidays()">
                            <i class="fas fa-magic mr-1"></i> Yaygın Tatilleri Ekle
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <i class="fas fa-list mr-1"></i> Mevcut Resmi Tatiller
                    </div>
                    <div class="card-body">
                        <?php if (count($holidays) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Tarih</th>
                                            <th>Tatil Adı</th>
                                            <th>Gün</th>
                                            <th>İşlemler</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($holidays as $holiday): ?>
                                            <tr>
                                                <td><?php echo date('d.m.Y', strtotime($holiday['holiday_date'])); ?></td>
                                                <td><?php echo $holiday['name']; ?></td>
                                                <td>
                                                    <?php 
                                                    $days = ['Pazar', 'Pazartesi', 'Salı', 'Çarşamba', 'Perşembe', 'Cuma', 'Cumartesi'];
                                                    echo $days[date('w', strtotime($holiday['holiday_date']))];
                                                    ?>
                                                </td>
                                                <td>
                                                    <form method="post" action="" style="display: inline;">
                                                        <input type="hidden" name="holiday_id" value="<?php echo $holiday['id']; ?>">
                                                        <button type="submit" name="delete_holiday" class="btn btn-sm btn-danger" 
                                                                onclick="return confirm('Bu tatili silmek istediğinizden emin misiniz?')">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle mr-1"></i> Henüz resmi tatil tanımlanmamış.
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
        function addCommonHolidays() {
            if (confirm('Yaygın resmi tatilleri (Yılbaşı, 23 Nisan, 1 Mayıs, vb.) eklemek istediğinizden emin misiniz?')) {
                $.ajax({
                    type: 'POST',
                    url: 'add_common_holidays.php',
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            alert(response.message);
                            location.reload();
                        } else {
                            alert('Hata: ' + response.message);
                        }
                    },
                    error: function() {
                        alert('İşlem sırasında bir hata oluştu!');
                    }
                });
            }
        }
    </script>
</body>
</html>