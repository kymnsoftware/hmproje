<?php
session_start();
// Zaten giriş yapmışsa yönlendir
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['privilege'] >= 1) {
        header('Location: index.php');
    } else {
        header('Location: leave_request.php');
    }
    exit;
}

// Veritabanı bağlantısı
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "db_kartlar";

// Hata mesajı değişkeni
$error_message = '';

// Form gönderilmiş mi kontrol et
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Kullanıcı adı ve şifreyi al
        $login_name = $_POST['login_name'] ?? '';
        $password = $_POST['password'] ?? '';
        
        // Kullanıcıyı veritabanında ara
        $stmt = $conn->prepare("SELECT * FROM cards WHERE name = :login_name AND card_number = :password AND enabled = 'true'");
        $stmt->bindParam(':login_name', $login_name);
        $stmt->bindParam(':password', $password);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Oturum değişkenlerini ayarla
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['user_name'] = $user['name'] . ' ' . $user['surname'];
            $_SESSION['privilege'] = $user['privilege'];
            $_SESSION['card_number'] = $user['card_number'];
            $_SESSION['department'] = $user['department'];
            $_SESSION['position'] = $user['position'];
            $_SESSION['photo_path'] = $user['photo_path'] ?? 'uploads/default-user.png';
            
            // Yetki seviyesine göre yönlendir
            if ($user['privilege'] >= 1) {
                header('Location: index.php'); // Yönetici için ana sayfa
            } else {
                header('Location: leave_request.php'); // Normal kullanıcı için izin talep sayfası
            }
            exit;
        } else {
            $error_message = 'Geçersiz kullanıcı adı veya şifre!';
        }
    } catch(PDOException $e) {
        $error_message = 'Veritabanı hatası: ' . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PDKS - Giriş</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@5.15.4/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
        }
        .login-container {
            width: 100%;
            max-width: 400px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            padding: 30px;
        }
        .login-logo {
            text-align: center;
            margin-bottom: 30px;
        }
        .login-logo i {
            font-size: 60px;
            color: #3498db;
        }
        .login-title {
            text-align: center;
            margin-bottom: 30px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-control {
            height: 50px;
            border-radius: 5px;
        }
        .btn-login {
            height: 50px;
            border-radius: 5px;
            font-weight: bold;
            background-color: #3498db;
            border-color: #3498db;
        }
        .btn-login:hover {
            background-color: #2980b9;
            border-color: #2980b9;
        }
        .login-footer {
            text-align: center;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-logo">
            <i class="fas fa-id-card"></i>
        </div>
        <div class="login-title">
            <h2>PDKS</h2>
            <p class="text-muted">Personel Devam Kontrol Sistemi</p>
        </div>
        
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger" role="alert">
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>
        
        <form method="post" action="">
            <div class="form-group">
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                    </div>
                    <input type="text" class="form-control" name="login_name" placeholder="Kullanıcı Adı" required>
                </div>
            </div>
            <div class="form-group">
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                    </div>
                    <input type="password" class="form-control" name="password" placeholder="Şifre (Kart Numarası)" required>
                </div>
            </div>
            <button type="submit" class="btn btn-primary btn-block btn-login">Giriş Yap</button>
        </form>
        
        <div class="login-footer">
            <p class="text-muted">&copy; <?php echo date('Y'); ?> PDKS - Tüm Hakları Saklıdır</p>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>