<?php
// Veritabanı bağlantısı
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "db_kartlar";

// Filtreleme parametreleri
$search = isset($_GET['search']) ? $_GET['search'] : '';
$department = isset($_GET['department']) ? $_GET['department'] : '';
$absence_type = isset($_GET['absence_type']) ? $_GET['absence_type'] : '';
$date_filter = isset($_GET['date_filter']) ? $_GET['date_filter'] : '';
$status_filter = isset($_GET['status_filter']) ? $_GET['status_filter'] : '';

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // SQL sorgusu
    $sql = "
        SELECT a.*, at.name as absence_type_name, at.color, 
               c.name, c.surname, c.department, c.position,
               creator.name as created_by_name
        FROM absences a
        JOIN absence_types at ON a.absence_type_id = at.id
        JOIN cards c ON a.user_id = c.user_id
        LEFT JOIN cards creator ON a.created_by = creator.user_id
        WHERE 1=1
    ";
    
    $params = [];
    
    if (!empty($search)) {
        $sql .= " AND (c.name LIKE :search OR c.surname LIKE :search)";
        $params[':search'] = "%$search%";
    }
    
    if (!empty($department)) {
        $sql .= " AND c.department = :department";
        $params[':department'] = $department;
    }
    
    if (!empty($absence_type)) {
        $sql .= " AND a.absence_type_id = :absence_type";
        $params[':absence_type'] = $absence_type;
    }
    
    if (!empty($date_filter)) {
        $sql .= " AND (DATE(a.start_date) <= :date_filter AND DATE(a.end_date) >= :date_filter)";
        $params[':date_filter'] = $date_filter;
    }
    
    if (!empty($status_filter)) {
        if ($status_filter == 'justified') {
            $sql .= " AND a.is_justified = 1";
        } elseif ($status_filter == 'unjustified') {
            $sql .= " AND a.is_justified = 0";
        }
    }
    
    $sql .= " ORDER BY a.created_at DESC LIMIT 100";
    
    $stmt = $conn->prepare($sql);
    
    // Parametreleri bağla
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    $stmt->execute();
    $absences = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Filtreleme formu
    echo '<div class="mb-4 p-3" style="background-color: #f8f9fa; border-radius: 5px;">
            <h6 class="mb-3"><i class="fas fa-filter mr-1"></i> Filtreleme</h6>
            <div class="row">
                <div class="col-md-3 mb-2">
                    <input type="text" class="form-control form-control-sm" id="filter-search" 
                           placeholder="Personel ara..." value="'.$search.'">
                </div>
                <div class="col-md-2 mb-2">
                    <select class="form-control form-control-sm" id="filter-department">
                        <option value="">Tüm Departmanlar</option>';
    
    // Departmanları al
    $deptStmt = $conn->query("SELECT DISTINCT department FROM cards WHERE department != '' ORDER BY department");
    while ($dept = $deptStmt->fetch(PDO::FETCH_ASSOC)) {
        $selected = ($dept['department'] == $department) ? 'selected' : '';
        echo '<option value="'.$dept['department'].'" '.$selected.'>'.$dept['department'].'</option>';
    }
    
    echo '          </select>
                </div>
                <div class="col-md-2 mb-2">
                    <select class="form-control form-control-sm" id="filter-absence-type">
                        <option value="">Tüm Türler</option>';
    
    // Devamsızlık türlerini al
    $typeStmt = $conn->query("SELECT * FROM absence_types ORDER BY name");
    while ($type = $typeStmt->fetch(PDO::FETCH_ASSOC)) {
        $selected = ($type['id'] == $absence_type) ? 'selected' : '';
        echo '<option value="'.$type['id'].'" '.$selected.'>'.$type['name'].'</option>';
    }
    
    echo '          </select>
                </div>
                <div class="col-md-2 mb-2">
                    <input type="date" class="form-control form-control-sm" id="filter-date" 
                           value="'.$date_filter.'">
                </div>
                <div class="col-md-2 mb-2">
                    <select class="form-control form-control-sm" id="filter-status">
                        <option value="">Tüm Durumlar</option>
                        <option value="justified" '.($status_filter == 'justified' ? 'selected' : '').'>Mazeretli</option>
                        <option value="unjustified" '.($status_filter == 'unjustified' ? 'selected' : '').'>Mazeretsiz</option>
                    </select>
                </div>
                <div class="col-md-1 mb-2">
                    <button class="btn btn-primary btn-sm btn-block" onclick="filterAbsences()">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </div>
            <div class="row mt-2">
                <div class="col-md-12">
                    <button class="btn btn-secondary btn-sm" onclick="clearFilters()">
                        <i class="fas fa-eraser mr-1"></i> Filtreleri Temizle
                    </button>
                </div>
            </div>
          </div>';
    
    if (count($absences) > 0) {
        echo '<div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="thead-dark">
                        <tr>
                            <th width="15%">Personel</th>
                            <th width="12%">Departman</th>
                            <th width="15%">Devamsızlık Türü</th>
                            <th width="10%">Başlangıç</th>
                            <th width="10%">Bitiş</th>
                            <th width="5%">Gün</th>
                            <th width="10%">Durum</th>
                            <th width="10%">Kaydeden</th>
                            <th width="13%">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>';
        
        foreach ($absences as $absence) {
            echo '<tr id="absence-row-'.$absence['id'].'">';
            echo '<td><strong>'.$absence['name'].' '.$absence['surname'].'</strong></td>';
            echo '<td>'.$absence['department'].'</td>';
            echo '<td>';
            echo '<span class="badge" style="background-color: '.$absence['color'].'; color: white;">';
            echo $absence['absence_type_name'];
            echo '</span>';
            if ($absence['auto_generated']) {
                echo '<br><small class="text-muted"><i class="fas fa-robot mr-1"></i>Otomatik</small>';
            }
            echo '</td>';
            echo '<td>'.date('d.m.Y', strtotime($absence['start_date'])).'</td>';
            echo '<td>'.date('d.m.Y', strtotime($absence['end_date'])).'</td>';
            echo '<td><strong>'.$absence['total_days'].'</strong></td>';
            
            if ($absence['is_justified']) {
                echo '<td><span class="absence-status status-justified">Mazeretli</span></td>';
            } else {
                echo '<td><span class="absence-status status-unjustified">Mazeretsiz</span></td>';
            }
            
            echo '<td>'.($absence['created_by_name'] ?: 'Sistem').'</td>';
            echo '<td>';
            echo '<div class="btn-group" role="group">';
            echo '<button class="btn btn-sm btn-info view-absence" data-id="'.$absence['id'].'" title="Detayları Görüntüle">';
            echo '<i class="fas fa-eye"></i>';
            echo '</button>';
            echo '<button class="btn btn-sm btn-warning edit-absence" data-id="'.$absence['id'].'" title="Düzenle">';
            echo '<i class="fas fa-edit"></i>';
            echo '</button>';
            echo '<button class="btn btn-sm btn-danger delete-absence" data-id="'.$absence['id'].'" title="Sil">';
            echo '<i class="fas fa-trash"></i>';
            echo '</button>';
            echo '</div>';
            echo '</td>';
            echo '</tr>';
        }
        
        echo '</tbody></table></div>';
        
        echo '<div class="mt-3 text-muted">
                <small><i class="fas fa-info-circle mr-1"></i> Toplam '.count($absences).' kayıt gösteriliyor (Son 100 kayıt)</small>
              </div>';
    } else {
        echo '<div class="alert alert-info">
                <i class="fas fa-info-circle mr-1"></i> Filtrelere uygun devamsızlık kaydı bulunamadı.
              </div>';
    }
    
} catch(PDOException $e) {
    echo '<div class="alert alert-danger">Veritabanı hatası: ' . $e->getMessage() . '</div>';
}
?>