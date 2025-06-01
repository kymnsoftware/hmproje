<?php
// Dönüş değerlerini JSON olarak ayarla
header('Content-Type: application/json');

// Veritabanı bağlantı bilgileri
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "db_kartlar";

// Yedekleme klasörü
$backupDir = 'backups/';

// Klasör yoksa oluştur
if (!file_exists($backupDir)) {
    if (!mkdir($backupDir, 0777, true)) {
        echo json_encode(['success' => false, 'message' => 'Yedekleme klasörü oluşturulamadı!']);
        exit;
    }
}

// Benzersiz dosya adı oluştur
$filename = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
$filepath = $backupDir . $filename;

try {
    // PDO kullanarak veritabanına bağlan
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // PHP'den doğrudan veritabanı yedeği oluşturma
    $tables = [];
    $result = $conn->query("SHOW TABLES");
    while ($row = $result->fetch(PDO::FETCH_NUM)) {
        $tables[] = $row[0];
    }
    
    $return = "-- PDKS Veritabanı Yedeklemesi\n";
    $return .= "-- Oluşturulma Tarihi: " . date("Y-m-d H:i:s") . "\n";
    $return .= "-- ------------------------------------------------------\n\n";
    
    // Tabloları sırayla işle
    foreach ($tables as $table) {
        $result = $conn->query("SELECT * FROM $table");
        $numFields = $result->columnCount();
        
        $return .= "DROP TABLE IF EXISTS `$table`;\n";
        
        $createTableResult = $conn->query("SHOW CREATE TABLE $table");
        $row2 = $createTableResult->fetch(PDO::FETCH_NUM);
        $return .= $row2[1] . ";\n\n";
        
        // Tablo verilerini ekle
        $rows = $result->fetchAll(PDO::FETCH_NUM);
        if (count($rows) > 0) {
            $return .= "INSERT INTO `$table` VALUES ";
            $counter = 0;
            
            foreach ($rows as $row) {
                if ($counter > 0) {
                    $return .= ",\n";
                }
                
                $return .= "(";
                for ($j = 0; $j < $numFields; $j++) {
                    if (isset($row[$j])) {
                        $row[$j] = addslashes($row[$j]);
                        // Özel karakterleri düzelt
                        $row[$j] = str_replace("\n", "\\n", $row[$j]);
                        $return .= '"' . $row[$j] . '"';
                    } else {
                        $return .= 'NULL';
                    }
                    
                    if ($j < ($numFields - 1)) {
                        $return .= ',';
                    }
                }
                $return .= ")";
                $counter++;
            }
            $return .= ";\n\n";
        }
    }
    
    // Dosyaya yaz
    file_put_contents($filepath, $return);
    
    echo json_encode(['success' => true, 'filename' => $filename]);
    
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Veritabanı hatası: ' . $e->getMessage()]);
}
?>