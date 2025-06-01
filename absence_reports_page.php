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

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Devamsızlık türlerini al
    $stmt = $conn->query("SELECT * FROM absence_types ORDER BY name");
    $absence_types = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Departmanları al
    $stmt = $conn->query("SELECT DISTINCT department FROM cards WHERE department != '' ORDER BY department");
    $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Kullanıcıları al
    $stmt = $conn->query("SELECT user_id, name, surname, department FROM cards WHERE enabled = 'true' ORDER BY name, surname");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    die("Veritabanı hatası: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PDKS - Devamsızlık Raporları</title>
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
            transition: transform 0.2s;
        }
        .report-card:hover {
            transform: translateY(-5px);
        }
        .format-buttons {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }
        .preview-container {
            max-height: 400px;
            overflow-y: auto;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 15px;
            background-color: #f8f9fa;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <h2><i class="fas fa-chart-bar mr-2"></i> Devamsızlık Raporları</h2>
            </div>
            <div class="user-info">
                <img src="<?php echo $_SESSION['photo_path']; ?>" class="user-photo" alt="Profil">
                <div>
                    <h5><?php echo $_SESSION['user_name']; ?></h5>
                    <p class="text-muted mb-0"><?php echo $_SESSION['department'] . ' - ' . $_SESSION['position']; ?></p>
                </div>
            </div>
            <div>
                <a href="attendance_tracking.php" class="btn btn-outline-warning mr-2">
                    <i class="fas fa-user-times mr-1"></i> Devamsızlık Takibi
                </a>
                <a href="index.php" class="btn btn-outline-primary mr-2">
                    <i class="fas fa-home mr-1"></i> Ana Sayfa
                </a>
                <a href="logout.php" class="btn btn-outline-secondary">
                    <i class="fas fa-sign-out-alt mr-1"></i> Çıkış Yap
                </a>
            </div>
        </div>

        <div class="row">
            <!-- Rapor Türleri -->
            <div class="col-md-4">
                <div class="card report-card">
                    <div class="card-header bg-primary text-white">
                        <i class="fas fa-calendar-day mr-1"></i> Günlük Devamsızlık Raporu
                    </div>
                    <div class="card-body">
                        <p>Seçilen güne ait tüm devamsızlık kayıtlarını görüntüleyin.</p>
                        <form id="daily-report-form">
                            <div class="form-group">
                                <label for="daily_date">Tarih</label>
                                <input type="date" class="form-control" id="daily_date" name="date" value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="daily_department">Departman</label>
                                <select class="form-control" id="daily_department" name="department">
                                    <option value="">Tüm Departmanlar</option>
                                    <?php foreach ($departments as $dept): ?>
                                        <option value="<?php echo $dept['department']; ?>"><?php echo $dept['department']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="daily_status">Durum</label>
                                <select class="form-control" id="daily_status" name="status">
                                    <option value="">Tümü</option>
                                    <option value="justified">Mazeretli</option>
                                    <option value="unjustified">Mazeretsiz</option>
                                </select>
                            </div>
                            <div class="format-buttons">
                                <button type="button" class="btn btn-success btn-sm generate-report" data-report="daily" data-format="excel">
                                    <i class="fas fa-file-excel mr-1"></i> Excel
                                </button>
                                <button type="button" class="btn btn-danger btn-sm generate-report" data-report="daily" data-format="pdf">
                                    <i class="fas fa-file-pdf mr-1"></i> PDF
                                </button>
                                <button type="button" class="btn btn-info btn-sm generate-report" data-report="daily" data-format="preview">
                                    <i class="fas fa-eye mr-1"></i> Önizleme
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card report-card">
                    <div class="card-header bg-success text-white">
                        <i class="fas fa-calendar-alt mr-1"></i> Aylık Devamsızlık Raporu
                    </div>
                    <div class="card-body">
                        <p>Seçilen aya ait devamsızlık istatistiklerini görüntüleyin.</p>
                        <form id="monthly-report-form">
                            <div class="form-group">
                                <label for="monthly_date">Ay</label>
                                <input type="month" class="form-control" id="monthly_date" name="month" value="<?php echo date('Y-m'); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="monthly_department">Departman</label>
                                <select class="form-control" id="monthly_department" name="department">
                                    <option value="">Tüm Departmanlar</option>
                                    <?php foreach ($departments as $dept): ?>
                                        <option value="<?php echo $dept['department']; ?>"><?php echo $dept['department']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="monthly_type">Devamsızlık Türü</label>
                                <select class="form-control" id="monthly_type" name="absence_type">
                                    <option value="">Tüm Türler</option>
                                    <?php foreach ($absence_types as $type): ?>
                                        <option value="<?php echo $type['id']; ?>"><?php echo $type['name']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="format-buttons">
                                <button type="button" class="btn btn-success btn-sm generate-report" data-report="monthly" data-format="excel">
                                    <i class="fas fa-file-excel mr-1"></i> Excel
                                </button>
                                <button type="button" class="btn btn-danger btn-sm generate-report" data-report="monthly" data-format="pdf">
                                    <i class="fas fa-file-pdf mr-1"></i> PDF
                                </button>
                                <button type="button" class="btn btn-info btn-sm generate-report" data-report="monthly" data-format="preview">
                                    <i class="fas fa-eye mr-1"></i> Önizleme
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card report-card">
                    <div class="card-header bg-info text-white">
                        <i class="fas fa-user mr-1"></i> Personel Bazlı Rapor
                    </div>
                    <div class="card-body">
                        <p>Seçilen personelin devamsızlık geçmişini görüntüleyin.</p>
                        <form id="employee-report-form">
                            <div class="form-group">
                                <label for="employee_user_id">Personel</label>
                                <select class="form-control" id="employee_user_id" name="user_id" required>
                                    <option value="">Personel Seçin</option>
                                    <?php foreach ($users as $user): ?>
                                        <option value="<?php echo $user['user_id']; ?>">
                                            <?php echo $user['name'] . ' ' . $user['surname'] . ' - ' . $user['department']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="employee_start_date">Başlangıç Tarihi</label>
                                <input type="date" class="form-control" id="employee_start_date" name="start_date" value="<?php echo date('Y-m-01'); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="employee_end_date">Bitiş Tarihi</label>
                                <input type="date" class="form-control" id="employee_end_date" name="end_date" value="<?php echo date('Y-m-t'); ?>" required>
                            </div>
                            <div class="format-buttons">
                                <button type="button" class="btn btn-success btn-sm generate-report" data-report="employee" data-format="excel">
                                    <i class="fas fa-file-excel mr-1"></i> Excel
                                </button>
                                <button type="button" class="btn btn-danger btn-sm generate-report" data-report="employee" data-format="pdf">
                                    <i class="fas fa-file-pdf mr-1"></i> PDF
                                </button>
                                <button type="button" class="btn btn-info btn-sm generate-report" data-report="employee" data-format="preview">
                                    <i class="fas fa-eye mr-1"></i> Önizleme
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-md-6">
                <div class="card report-card">
                    <div class="card-header bg-warning text-white">
                        <i class="fas fa-building mr-1"></i> Departman Bazlı Rapor
                    </div>
                    <div class="card-body">
                        <p>Departmanlara göre devamsızlık istatistiklerini görüntüleyin.</p>
                        <form id="department-report-form">
                            <div class="form-group">
                                <label for="dept_start_date">Başlangıç Tarihi</label>
                                <input type="date" class="form-control" id="dept_start_date" name="start_date" value="<?php echo date('Y-m-01'); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="dept_end_date">Bitiş Tarihi</label>
                                <input type="date" class="form-control" id="dept_end_date" name="end_date" value="<?php echo date('Y-m-t'); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="dept_department">Departman</label>
                                <select class="form-control" id="dept_department" name="department">
                                    <option value="">Tüm Departmanlar</option>
                                    <?php foreach ($departments as $dept): ?>
                                        <option value="<?php echo $dept['department']; ?>"><?php echo $dept['department']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="format-buttons">
                                <button type="button" class="btn btn-success btn-sm generate-report" data-report="department" data-format="excel">
                                    <i class="fas fa-file-excel mr-1"></i> Excel
                                </button>
                                <button type="button" class="btn btn-danger btn-sm generate-report" data-report="department" data-format="pdf">
                                    <i class="fas fa-file-pdf mr-1"></i> PDF
                                </button>
                                <button type="button" class="btn btn-info btn-sm generate-report" data-report="department" data-format="preview">
                                    <i class="fas fa-eye mr-1"></i> Önizleme
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card report-card">
                    <div class="card-header bg-secondary text-white">
                        <i class="fas fa-chart-pie mr-1"></i> Özet İstatistik Raporu
                    </div>
                    <div class="card-body">
                        <p>Genel devamsızlık istatistiklerini ve trendlerini görüntüleyin.</p>
                        <form id="summary-report-form">
                            <div class="form-group">
                                <label for="summary_start_date">Başlangıç Tarihi</label>
                                <input type="date" class="form-control" id="summary_start_date" name="start_date" value="<?php echo date('Y-m-01'); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="summary_end_date">Bitiş Tarihi</label>
                                <input type="date" class="form-control" id="summary_end_date" name="end_date" value="<?php echo date('Y-m-t'); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="summary_group_by">Gruplama</label>
                                <select class="form-control" id="summary_group_by" name="group_by">
                                    <option value="department">Departman Bazlı</option>
                                    <option value="absence_type">Devamsızlık Türü Bazlı</option>
                                    <option value="monthly">Aylık Bazlı</option>
                                </select>
                            </div>
                            <div class="format-buttons">
                                <button type="button" class="btn btn-success btn-sm generate-report" data-report="summary" data-format="excel">
                                    <i class="fas fa-file-excel mr-1"></i> Excel
                                </button>
                                <button type="button" class="btn btn-danger btn-sm generate-report" data-report="summary" data-format="pdf">
                                    <i class="fas fa-file-pdf mr-1"></i> PDF
                                </button>
                                <button type="button" class="btn btn-info btn-sm generate-report" data-report="summary" data-format="preview">
                                    <i class="fas fa-eye mr-1"></i> Önizleme
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Rapor Önizleme Alanı -->
        <div class="row mt-4" id="preview-section" style="display: none;">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-eye mr-1"></i> Rapor Önizlemesi</h5>
                        <button type="button" class="close text-white" onclick="$('#preview-section').hide()">
                            <span>&times;</span>
                        </button>
                    </div>
                    <div class="card-body">
                        <div id="report-preview" class="preview-container">
                            <!-- Önizleme içeriği buraya gelecek -->
                        </div>
                        <div class="mt-3">
                            <button type="button" class="btn btn-success" id="download-excel">
                                <i class="fas fa-file-excel mr-1"></i> Excel İndir
                            </button>
                            <button type="button" class="btn btn-danger" id="download-pdf">
                                <i class="fas fa-file-pdf mr-1"></i> PDF İndir
                            </button>
                        </div>
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
            var currentReportParams = {};
            
            // Rapor oluşturma
            $('.generate-report').click(function() {
                var reportType = $(this).data('report');
                var format = $(this).data('format');
                var formId = reportType + '-report-form';
                var formData = $('#' + formId).serialize();
                
                // Parametreleri sakla
                currentReportParams = {
                    type: reportType,
                    params: formData
                };
                
                if (format === 'preview') {
                    // Önizleme göster
                    generatePreview(reportType, formData);
                } else {
                    // Dosya indir
                    downloadReport(reportType, format, formData);
                }
            });
            
            // Önizleme indirme butonları
            $('#download-excel').click(function() {
                downloadReport(currentReportParams.type, 'excel', currentReportParams.params);
            });
            
            $('#download-pdf').click(function() {
                downloadReport(currentReportParams.type, 'pdf', currentReportParams.params);
            });
            
            // Önizleme oluştur
            function generatePreview(reportType, formData) {
                $('#report-preview').html('<div class="text-center"><div class="spinner-border text-info" role="status"><span class="sr-only">Yükleniyor...</span></div></div>');
                $('#preview-section').show();
                
                $.ajax({
                    url: 'generate_absence_report.php',
                    data: formData + '&type=' + reportType + '&format=html',
                    success: function(data) {
                        $('#report-preview').html(data);
                    },
                    error: function() {
                        $('#report-preview').html('<div class="alert alert-danger">Rapor oluşturulurken bir hata oluştu!</div>');
                    }
                });
            }
            
            // Rapor indir
            function downloadReport(reportType, format, formData) {
                var url = 'generate_absence_report.php?' + formData + '&type=' + reportType + '&format=' + format;
                window.open(url, '_blank');
            }
            
            // Tarih aralığı kontrolleri
            $('#employee_start_date, #dept_start_date, #summary_start_date').change(function() {
                var endDateId = $(this).attr('id').replace('start_date', 'end_date');
                $('#' + endDateId).attr('min', $(this).val());
            });
        });
    </script>
</body>
</html>