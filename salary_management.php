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
    
    // Kullanıcıları al
    $stmt = $conn->query("SELECT user_id, name, surname, department, fixed_salary FROM cards WHERE enabled = 'true' ORDER BY name, surname");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Sistem ayarlarını al
    $stmt = $conn->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'salary_%'");
    $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
} catch(PDOException $e) {
    $error_message = 'Veritabanı hatası: ' . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PDKS - Maaş Yönetimi</title>
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
        .salary-result {
            padding: 15px;
            border-radius: 5px;
            margin-top: 15px;
        }
        .salary-success {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
        }
        .salary-warning {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
        }
        .salary-danger {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <h2><i class="fas fa-money-bill-wave mr-2"></i> Maaş Yönetimi</h2>
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

        <ul class="nav nav-tabs" id="salaryTab" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" id="calculator-tab" data-toggle="tab" href="#calculator" role="tab">
                    <i class="fas fa-calculator mr-1"></i> Maaş Hesaplama
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="settings-tab" data-toggle="tab" href="#settings" role="tab">
                    <i class="fas fa-cog mr-1"></i> Sistem Ayarları
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="bulk-calculation-tab" data-toggle="tab" href="#bulk-calculation" role="tab">
                    <i class="fas fa-users mr-1"></i> Toplu Hesaplama
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="employee-settings-tab" data-toggle="tab" href="#employee-settings" role="tab">
                    <i class="fas fa-user-cog mr-1"></i> Personel Maaş Ayarları
                </a>
            </li>
        </ul>

        <div class="tab-content" id="salaryTabContent">
            <!-- Maaş Hesaplama -->
            <div class="tab-pane fade show active" id="calculator" role="tabpanel">
                <div class="card mt-3">
                    <div class="card-header bg-success text-white">
                        <i class="fas fa-calculator mr-1"></i> Maaş Hesaplama
                    </div>
                    <div class="card-body">
                        <form id="salary-calculation-form">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="calc_user_id">Personel</label>
                                        <select class="form-control" id="calc_user_id" name="user_id" required>
                                            <option value="">Personel Seçin</option>
                                            <?php foreach ($users as $user): ?>
                                                <option value="<?php echo $user['user_id']; ?>">
                                                    <?php echo $user['name'] . ' ' . $user['surname'] . ' - ' . $user['department']; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="calc_start_date">Başlangıç Tarihi</label>
                                        <input type="date" class="form-control" id="calc_start_date" name="start_date" value="<?php echo date('Y-m-01'); ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="calc_end_date">Bitiş Tarihi</label>
                                        <input type="date" class="form-control" id="calc_end_date" name="end_date" value="<?php echo date('Y-m-t'); ?>" required>
                                    </div>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-success btn-lg">
                                <i class="fas fa-calculator mr-1"></i> Maaş Hesapla
                            </button>
                        </form>
                        
                        <div id="salary-result" style="display: none;">
                            <!-- Hesaplama sonucu buraya gelecek -->
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sistem Ayarları -->
            <div class="tab-pane fade" id="settings" role="tabpanel">
                <div class="card mt-3">
                    <div class="card-header bg-warning text-white">
                        <i class="fas fa-cog mr-1"></i> Maaş Sistemi Ayarları
                    </div>
                    <div class="card-body">
                        <form id="salary-settings-form">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="minimum_type">Minimum Çalışma Türü</label>
                                        <select class="form-control" id="minimum_type" name="salary_minimum_type">
                                            <option value="percentage" <?php echo ($settings['salary_minimum_type'] ?? 'percentage') == 'percentage' ? 'selected' : ''; ?>>Yüzde Olarak</option>
                                            <option value="days" <?php echo ($settings['salary_minimum_type'] ?? 'percentage') == 'days' ? 'selected' : ''; ?>>Gün Olarak</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6" id="percentage-setting" style="display: <?php echo ($settings['salary_minimum_type'] ?? 'percentage') == 'percentage' ? 'block' : 'none'; ?>">
                                    <div class="form-group">
                                        <label for="minimum_work_rate">Minimum Çalışma Oranı (%)</label>
                                        <input type="number" class="form-control" id="minimum_work_rate" name="salary_minimum_work_rate" value="<?php echo $settings['salary_minimum_work_rate'] ?? 90; ?>" min="1" max="100">
                                    </div>
                                </div>
                                <div class="col-md-6" id="days-setting" style="display: <?php echo ($settings['salary_minimum_type'] ?? 'percentage') == 'days' ? 'block' : 'none'; ?>">
                                    <div class="form-group">
                                        <label for="minimum_work_days">Minimum Çalışma Gün Sayısı</label>
                                        <input type="number" class="form-control" id="minimum_work_days" name="salary_minimum_work_days" value="<?php echo $settings['salary_minimum_work_days'] ?? 20; ?>" min="1" max="31">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="exclude_weekends" name="salary_exclude_weekends" <?php echo ($settings['salary_exclude_weekends'] ?? 'true') == 'true' ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="exclude_weekends">
                                            Hafta sonlarını hesaba katma
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="exclude_holidays" name="salary_exclude_holidays" <?php echo ($settings['salary_exclude_holidays'] ?? 'true') == 'true' ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="exclude_holidays">
                                            Resmi tatilleri hesaba katma
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="alert alert-info mt-3">
                                <h6><i class="fas fa-info-circle mr-1"></i> Ayar Açıklamaları:</h6>
                                <ul class="mb-0">
                                    <li><strong>Minimum Çalışma:</strong> Personelin tam maaş alabilmesi için gereken minimum çalışma şartı</li>
                                    <li><strong>Hafta Sonları:</strong> İş günü hesaplamasında Cumartesi-Pazar günlerini hariç tutar</li>
                                    <li><strong>Resmi Tatiller:</strong> Ulusal/dini bayramları iş günü hesaplamasından çıkarır</li>
                                    <li><strong>İzinli Günler:</strong> Onaylanmış izinler otomatik olarak çalışılan gün sayısına eklenir</li>
                                </ul>
                            </div>
                            
                            <button type="submit" class="btn btn-warning">
                                <i class="fas fa-save mr-1"></i> Ayarları Kaydet
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Toplu Hesaplama -->
            <div class="tab-pane fade" id="bulk-calculation" role="tabpanel">
                <div class="card mt-3">
                    <div class="card-header bg-info text-white">
                        <i class="fas fa-users mr-1"></i> Toplu Maaş Hesaplama
                    </div>
                    <div class="card-body">
                        <form id="bulk-calculation-form">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="bulk_start_date">Başlangıç Tarihi</label>
                                        <input type="date" class="form-control" id="bulk_start_date" name="start_date" value="<?php echo date('Y-m-01'); ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="bulk_end_date">Bitiş Tarihi</label>
                                        <input type="date" class="form-control" id="bulk_end_date" name="end_date" value="<?php echo date('Y-m-t'); ?>" required>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="bulk_department">Departman Filtresi</label>
                                <select class="form-control" id="bulk_department" name="department">
                                    <option value="">Tüm Departmanlar</option>
                                    <?php
                                    $departments = array_unique(array_column($users, 'department'));
                                    foreach ($departments as $dept):
                                    ?>
                                        <option value="<?php echo $dept; ?>"><?php echo $dept; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-info btn-lg">
                                <i class="fas fa-calculator mr-1"></i> Toplu Hesapla
                            </button>
                            <button type="button" class="btn btn-success" id="export-bulk-excel">
                                <i class="fas fa-file-excel mr-1"></i> Excel'e Aktar
                            </button>
                        </form>
                        
                        <div id="bulk-result" style="display: none;">
                            <!-- Toplu hesaplama sonucu buraya gelecek -->
                        </div>
                    </div>
                </div>
            </div>

            <!-- Personel Maaş Ayarları -->
            <div class="tab-pane fade" id="employee-settings" role="tabpanel">
                <div class="card mt-3">
                    <div class="card-header bg-secondary text-white">
                        <i class="fas fa-user-cog mr-1"></i> Personel Maaş Ayarları
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Personel</th>
                                        <th>Departman</th>
                                        <th>Sabit Maaş</th>
                                        <th>İşlemler</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $user): ?>
                                        <tr>
                                            <td><?php echo $user['name'] . ' ' . $user['surname']; ?></td>
                                            <td><?php echo $user['department']; ?></td>
                                            <td>
                                                <span class="badge badge-success">
                                                    <?php echo number_format($user['fixed_salary'] ?: 35000, 0, ',', '.'); ?> TL
                                                </span>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-warning edit-employee-salary" 
                                                        data-user-id="<?php echo $user['user_id']; ?>"
                                                        data-current-salary="<?php echo $user['fixed_salary'] ?: 35000; ?>"
                                                        data-name="<?php echo $user['name'] . ' ' . $user['surname']; ?>">
                                                    <i class="fas fa-edit"></i> Düzenle
                                                </button>
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

    <!-- Maaş Düzenleme Modal -->
    <div class="modal fade" id="editSalaryModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-warning text-white">
                    <h5 class="modal-title">Maaş Düzenle</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <form id="edit-salary-form">
                    <div class="modal-body">
                        <input type="hidden" id="edit_user_id" name="user_id">
                        <div class="form-group">
                            <label>Personel</label>
                            <input type="text" class="form-control" id="edit_employee_name" readonly>
                        </div>
                        <div class="form-group">
                            <label for="edit_fixed_salary">Sabit Maaş (TL)</label>
                            <input type="number" class="form-control" id="edit_fixed_salary" name="fixed_salary" min="0" step="0.01" required>
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

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        $(document).ready(function() {
            // Minimum tip değiştiğinde göster/gizle
            $('#minimum_type').change(function() {
                if ($(this).val() === 'percentage') {
                    $('#percentage-setting').show();
                    $('#days-setting').hide();
                } else {
                    $('#percentage-setting').hide();
                    $('#days-setting').show();
                }
            });

            // Maaş hesaplama
            $('#salary-calculation-form').submit(function(e) {
                e.preventDefault();
                var formData = $(this).serialize();
                
                $('#salary-result').html('<div class="text-center"><div class="spinner-border text-success" role="status"></div></div>').show();
                
                $.ajax({
                    url: 'salary_calculator.php?action=calculate',
                    data: formData,
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            displaySalaryResult(response);
                        } else {
                            $('#salary-result').html('<div class="alert alert-danger">' + response.message + '</div>');
                        }
                    },
                    error: function() {
                        $('#salary-result').html('<div class="alert alert-danger">Hesaplama sırasında bir hata oluştu!</div>');
                    }
                });
            });

            // Sistem ayarları kaydetme
            $('#salary-settings-form').submit(function(e) {
                e.preventDefault();
                var formData = $(this).serialize();
                
                $.ajax({
                    type: 'POST',
                    url: 'save_salary_system_settings.php',
                    data: formData,
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            alert('Ayarlar başarıyla kaydedildi!');
                        } else {
                            alert('Hata: ' + response.message);
                        }
                    },
                    error: function() {
                        alert('Kaydetme sırasında bir hata oluştu!');
                    }
                });
            });

            // Toplu hesaplama
            $('#bulk-calculation-form').submit(function(e) {
                e.preventDefault();
                var formData = $(this).serialize();
                
                $('#bulk-result').html('<div class="text-center"><div class="spinner-border text-info" role="status"></div></div>').show();
                
                $.ajax({
                    url: 'bulk_salary_calculation.php',
                    data: formData,
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            displayBulkResult(response);
                        } else {
                            $('#bulk-result').html('<div class="alert alert-danger">' + response.message + '</div>');
                        }
                    },
                    error: function() {
                        $('#bulk-result').html('<div class="alert alert-danger">Hesaplama sırasında bir hata oluştu!</div>');
                    }
                });
            });

            // Maaş düzenleme modal
            $('.edit-employee-salary').click(function() {
                var userId = $(this).data('user-id');
                var currentSalary = $(this).data('current-salary');
                var name = $(this).data('name');
                
                $('#edit_user_id').val(userId);
                $('#edit_employee_name').val(name);
                $('#edit_fixed_salary').val(currentSalary);
                $('#editSalaryModal').modal('show');
            });

            // Maaş güncelleme
            $('#edit-salary-form').submit(function(e) {
                e.preventDefault();
                var formData = $(this).serialize();
                
                $.ajax({
                    type: 'POST',
                    url: 'update_employee_salary.php',
                    data: formData,
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            $('#editSalaryModal').modal('hide');
                            alert('Maaş başarıyla güncellendi!');
                            location.reload();
                        } else {
                            alert('Hata: ' + response.message);
                        }
                    },
                    error: function() {
                        alert('Güncelleme sırasında bir hata oluştu!');
                    }
                });
            });
        });

        // Maaş hesaplama sonucunu göster
        function displaySalaryResult(response) {
            var data = response;
            var cssClass = data.salary.meets_minimum_requirement ? 'salary-success' : 'salary-danger';
            var statusIcon = data.salary.meets_minimum_requirement ? 'fas fa-check-circle' : 'fas fa-exclamation-triangle';
            var statusText = data.salary.meets_minimum_requirement ? 'Minimum şartı karşılıyor' : 'Minimum şartı karşılamıyor';
            
            var html = '<div class="salary-result ' + cssClass + '">';
            html += '<h5><i class="' + statusIcon + ' mr-2"></i>' + data.employee.name + ' - Maaş Hesaplaması</h5>';
            html += '<div class="row">';
            html += '<div class="col-md-6">';
            html += '<table class="table table-sm">';
            html += '<tr><th>Sabit Maaş:</th><td>' + numberFormat(data.salary.fixed_salary) + ' TL</td></tr>';
            html += '<tr><th>Çalışılan Gün:</th><td>' + data.attendance.worked_days + ' gün</td></tr>';
            html += '<tr><th>İzinli Gün:</th><td>' + data.attendance.approved_leave_days + ' gün</td></tr>';
            html += '<tr><th>Toplam Devam:</th><td>' + data.attendance.total_attended_days + ' gün</td></tr>';
            html += '<tr><th>Gerekli Minimum:</th><td>' + data.period.required_work_days + ' gün</td></tr>';
            html += '</table>';
            html += '</div>';
            html += '<div class="col-md-6">';
            html += '<table class="table table-sm">';
            html += '<tr><th>Devam Oranı:</th><td>' + data.attendance.attendance_rate + '%</td></tr>';
            html += '<tr><th>Eksik Gün:</th><td>' + data.attendance.missing_days + ' gün</td></tr>';
            html += '<tr><th>Kesinti Tutarı:</th><td>' + numberFormat(data.salary.deduction_amount) + ' TL</td></tr>';
            html += '<tr class="table-success"><th>Net Maaş:</th><td><strong>' + numberFormat(data.salary.net_salary) + ' TL</strong></td></tr>';
            html += '<tr><th>Durum:</th><td><span class="badge badge-' + (data.salary.meets_minimum_requirement ? 'success' : 'danger') + '">' + statusText + '</span></td></tr>';
            html += '</table>';
            html += '</div>';
            html += '</div>';
            html += '</div>';
            
            $('#salary-result').html(html);
        }

        // Toplu hesaplama sonucunu göster
        function displayBulkResult(response) {
            var html = '<div class="mt-4">';
            html += '<h5>Toplu Hesaplama Sonuçları</h5>';
            html += '<div class="table-responsive">';
            html += '<table class="table table-striped table-bordered">';
            html += '<thead class="thead-dark">';
            html += '<tr><th>Personel</th><th>Departman</th><th>Çalışılan</th><th>İzinli</th><th>Toplam</th><th>Gerekli</th><th>Durum</th><th>Net Maaş</th></tr>';
            html += '</thead><tbody>';
            
            response.results.forEach(function(result) {
                var badgeClass = result.salary.meets_minimum_requirement ? 'badge-success' : 'badge-danger';
                var statusText = result.salary.meets_minimum_requirement ? 'Tamam' : 'Eksik';
                
                html += '<tr>';
                html += '<td>' + result.employee.name + '</td>';
                html += '<td>' + result.employee.department + '</td>';
                html += '<td>' + result.attendance.worked_days + '</td>';
                html += '<td>' + result.attendance.approved_leave_days + '</td>';
                html += '<td>' + result.attendance.total_attended_days + '</td>';
                html += '<td>' + result.period.required_work_days + '</td>';
                html += '<td><span class="badge ' + badgeClass + '">' + statusText + '</span></td>';
                html += '<td><strong>' + numberFormat(result.salary.net_salary) + ' TL</strong></td>';
                html += '</tr>';
            });
            
            html += '</tbody></table>';
            html += '</div>';
            html += '</div>';
            
            $('#bulk-result').html(html);
        }

        // Sayı formatlama
        function numberFormat(number) {
            return new Intl.NumberFormat('tr-TR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }).format(number);
        }
    </script>
</body>
</html>