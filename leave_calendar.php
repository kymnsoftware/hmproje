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

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // İzin türlerini al
    $stmt = $conn->query("SELECT * FROM leave_types ORDER BY name");
    $leave_types = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Departmanları al
    $stmt = $conn->query("SELECT DISTINCT department FROM cards WHERE department != '' ORDER BY department");
    $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    die("Veritabanı hatası: " . $e->getMessage());
}

$selectedMonth = isset($_GET['month']) ? intval($_GET['month']) : intval(date('m'));
$selectedYear = isset($_GET['year']) ? intval($_GET['year']) : intval(date('Y'));
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PDKS - İzin Takvimi</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@5.15.4/css/all.min.css">
    <!-- FullCalendar CSS -->
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@5.10.1/main.min.css" rel="stylesheet">
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
        /* FullCalendar özelleştirmeleri */
        .fc-event {
            cursor: pointer;
            border-radius: 3px;
        }
        #calendar {
            background-color: white;
            padding: 15px;
            border-radius: 5px;
        }
        .filter-card {
            margin-bottom: 20px;
        }
        .leave-type-filter {
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <h2><i class="fas fa-calendar mr-2"></i> İzin Takvimi</h2>
            </div>
            <div class="user-info">
                <img src="<?php echo $_SESSION['photo_path']; ?>" class="user-photo" alt="Profil">
                <div>
                    <h5><?php echo $_SESSION['user_name']; ?></h5>
                    <p class="text-muted mb-0"><?php echo $_SESSION['department'] . ' - ' . $_SESSION['position']; ?></p>
                </div>
            </div>
            <div>
                <?php if ($_SESSION['privilege'] >= 1): ?>
                <a href="index.php" class="btn btn-outline-primary mr-2">
                    <i class="fas fa-home mr-1"></i> Ana Sayfa
                </a>
                <a href="leave_management.php" class="btn btn-outline-success mr-2">
                    <i class="fas fa-tasks mr-1"></i> İzin Yönetimi
                </a>
                <?php else: ?>
                <a href="leave_request.php" class="btn btn-outline-primary mr-2">
                    <i class="fas fa-tasks mr-1"></i> İzin Taleplerim
                </a>
                <?php endif; ?>
                <a href="logout.php" class="btn btn-outline-secondary">
                    <i class="fas fa-sign-out-alt mr-1"></i> Çıkış Yap
                </a>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-3">
                <div class="card filter-card">
                    <div class="card-header bg-primary text-white">
                        <i class="fas fa-filter mr-1"></i> Filtreler
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label for="filter-department">Departman</label>
                            <select class="form-control" id="filter-department">
                                <option value="">Tüm Departmanlar</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?php echo $dept['department']; ?>"><?php echo $dept['department']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <label>İzin Türleri</label>
                        <div class="leave-type-filter">
                            <?php foreach ($leave_types as $type): ?>
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input leave-type-checkbox" 
                                           id="type-<?php echo $type['id']; ?>" 
                                           value="<?php echo $type['id']; ?>" 
                                           data-color="<?php echo $type['color']; ?>" 
                                           checked>
                                    <label class="custom-control-label" for="type-<?php echo $type['id']; ?>" 
                                           style="color: <?php echo $type['color']; ?>">
                                        <?php echo $type['name']; ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <hr>
                        
                        <div class="form-group">
                            <label for="filter-status">Durum</label>
                            <select class="form-control" id="filter-status">
                                <option value="">Tüm Durumlar</option>
                                <option value="pending">Beklemede</option>
                                <option value="approved" selected>Onaylanmış</option>
                                <option value="rejected">Reddedilmiş</option>
                            </select>
                        </div>
                        
                        <button id="apply-filters" class="btn btn-primary btn-block">
                            <i class="fas fa-search mr-1"></i> Filtrele
                        </button>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <i class="fas fa-info-circle mr-1"></i> Bilgi
                    </div>
                    <div class="card-body">
                        <p>Bu takvimde personelin onaylanmış izin günleri görüntülenmektedir.</p>
                        <p>Detaylı bilgi için izin kaydına tıklayabilirsiniz.</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-9">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fas fa-calendar-alt mr-1"></i> İzin Takvimi
                            </div>
                            <div>
                                <button id="today-btn" class="btn btn-sm btn-light">Bugün</button>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div id="calendar"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- İzin Detay Modal -->
    <div class="modal fade" id="leaveDetailModal" tabindex="-1" role="dialog" aria-labelledby="leaveDetailModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="leaveDetailModalLabel">İzin Detayı</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Kapat">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="leave-detail-content">
                    <!-- İçerik AJAX ile doldurulacak -->
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status">
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

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <!-- FullCalendar JS -->
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.10.1/main.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.10.1/locales-all.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var calendarEl = document.getElementById('calendar');
            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                locale: 'tr',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,listMonth'
                },
                buttonText: {
                    today: 'Bugün',
                    month: 'Ay',
                    week: 'Hafta',
                    list: 'Liste'
                },
                weekNumbers: true,
                weekText: 'Hafta',
                navLinks: true,
                editable: false,
                dayMaxEvents: true,
                events: function(info, successCallback, failureCallback) {
                    // Filtre parametrelerini al
                    var department = $('#filter-department').val();
                    var status = $('#filter-status').val();
                    
                    // İzin türlerini al
                    var leaveTypes = [];
                    $('.leave-type-checkbox:checked').each(function() {
                        leaveTypes.push($(this).val());
                    });
                    
                    // AJAX ile verileri getir
                    $.ajax({
                        url: 'get_calendar_events.php',
                        data: {
                            start: info.startStr,
                            end: info.endStr,
                            department: department,
                            leave_types: leaveTypes.join(','),
                            status: status
                        },
                        success: function(result) {
                            if (result.success) {
                                successCallback(result.events);
                            } else {
                                failureCallback(result.message);
                            }
                        },
                        error: function() {
                            failureCallback('Takvim verileri yüklenirken bir hata oluştu.');
                        }
                    });
                },
                eventClick: function(info) {
                    // İzin detayını getir
                    $.ajax({
                        url: 'get_leave_detail.php',
                        data: {
                            id: info.event.id
                        },
                        success: function(result) {
                            if (result.success) {
                                $('#leave-detail-content').html(result.html);
                                $('#leaveDetailModal').modal('show');
                            } else {
                                alert('İzin detayı yüklenirken bir hata oluştu: ' + result.message);
                            }
                        },
                        error: function() {
                            alert('İzin detayı yüklenirken bir hata oluştu.');
                        }
                    });
                }
            });
            
            calendar.render();
            
            // Bugün butonu
            $('#today-btn').click(function() {
                calendar.today();
            });
            
            // Filtre uygulama
            $('#apply-filters').click(function() {
                calendar.refetchEvents();
            });
        });
    </script>
</body>
</html>