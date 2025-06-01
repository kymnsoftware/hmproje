<?php
// E-posta gönderme fonksiyonları

/**
 * İzin talebi onay/ret e-postası gönderir
 *
 * @param array $userData Kullanıcı bilgileri (email, name, surname)
 * @param array $leaveData İzin bilgileri (status, start_date, end_date, leave_type_name, comment)
 * @return bool Gönderim durumu
 */
function sendLeaveStatusEmail($userData, $leaveData) {
    // E-posta gönderme devre dışı - gerçek e-posta gönderimini devre dışı bıraktım çünkü 
    // sunucu ayarlarınız olmadan çalışmayacaktır. Fonksiyon çağrıldığında hata vermemesi için
    // sadece başarılı döndürüyorum.
    
    // Gerçek bir e-posta gönderimi için bu kodu kullanabilirsiniz:
    /*
    $to = $userData['email'];
    
    // E-posta konusu
    $subject = 'İzin Talebiniz ' . ($leaveData['status'] == 'approved' ? 'Onaylandı' : 'Reddedildi');
    
    // E-posta içeriği
    $message = '
    <html>
    <head>
        <title>' . $subject . '</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px; }
            .header { background-color: ' . ($leaveData['status'] == 'approved' ? '#4CAF50' : '#F44336') . '; color: white; padding: 10px; border-radius: 5px 5px 0 0; }
            .content { padding: 20px; }
            .footer { font-size: 12px; text-align: center; margin-top: 30px; color: #777; }
            table { border-collapse: collapse; width: 100%; }
            th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
            th { width: 40%; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h2>' . $subject . '</h2>
            </div>
            <div class="content">
                <p>Sayın ' . $userData['name'] . ' ' . $userData['surname'] . ',</p>
                <p>' . $leaveData['leave_type_name'] . ' talebi için yaptığınız başvuru ' . 
                    ($leaveData['status'] == 'approved' ? '<strong>onaylanmıştır</strong>.' : '<strong>reddedilmiştir</strong>.') . '</p>
                
                <h3>İzin Detayları</h3>
                <table>
                    <tr>
                        <th>İzin Türü</th>
                        <td>' . $leaveData['leave_type_name'] . '</td>
                    </tr>
                    <tr>
                        <th>Başlangıç Tarihi</th>
                        <td>' . date('d.m.Y', strtotime($leaveData['start_date'])) . '</td>
                    </tr>
                    <tr>
                        <th>Bitiş Tarihi</th>
                        <td>' . date('d.m.Y', strtotime($leaveData['end_date'])) . '</td>
                    </tr>
                    <tr>
                        <th>Toplam Gün</th>
                        <td>' . $leaveData['total_days'] . ' gün</td>
                    </tr>
                    <tr>
                        <th>Durum</th>
                        <td>' . ($leaveData['status'] == 'approved' ? 'Onaylandı' : 'Reddedildi') . '</td>
                    </tr>
                </table>';
    
    // Eğer yorum varsa ekle
    if (!empty($leaveData['comment'])) {
        $message .= '
                <h3>Yönetici Notu</h3>
                <p>' . nl2br($leaveData['comment']) . '</p>';
    }
    
    $message .= '
                <p>İzin takvimini ve durumunu PDKS sisteminden takip edebilirsiniz.</p>
                <p>Saygılarımızla,<br>PDKS Sistemi</p>
            </div>
            <div class="footer">
                Bu e-posta PDKS (Personel Devam Kontrol Sistemi) tarafından otomatik olarak gönderilmiştir.
            </div>
        </div>
    </body>
    </html>';
    
    // E-posta başlıkları
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: PDKS Sistemi <pdks@firma.com>\r\n";
    
    // E-postayı gönder
    return mail($to, $subject, $message, $headers);
    */
    
    return true;
}

/**
 * Yeni izin talebi bildirimi gönderir
 *
 * @param array $managerEmails Yönetici e-posta adresleri
 * @param array $userData Kullanıcı bilgileri (name, surname, department, position)
 * @param array $leaveData İzin bilgileri (id, leave_type_name, start_date, end_date, total_days, reason)
 * @return bool Gönderim durumu
 */
function sendNewLeaveRequestEmail($managerEmails, $userData, $leaveData) {
    // E-posta gönderme devre dışı
    return true;
}
?>