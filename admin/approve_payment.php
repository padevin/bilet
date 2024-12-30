<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Hata log dosyasına yazma
ini_set('log_errors', 1);
ini_set('error_log', '../error.log');

require '../vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Yetkisiz erişim']);
    exit;
}

header('Content-Type: application/json');

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['payment_id'])) {
        throw new Exception('Ödeme ID\'si gerekli');
    }

    // Gelen veriyi logla
    error_log("Payment ID: " . $input['payment_id']);

    // .env dosyasını oku
    if (!file_exists('../.env')) {
        throw new Exception('.env dosyası bulunamadı');
    }
    
    $env = parse_ini_file('../.env');
    if ($env === false) {
        throw new Exception('.env dosyası okunamadı');
    }

    // Veritabanı bağlantısı
    try {
        $pdo = new PDO(
            "mysql:host=localhost;dbname=u377540006_sbilet_db",
            "u377540006_sbilet",
            "$0XrC9!u~"
        );
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        error_log("Veritabanı bağlantı hatası: " . $e->getMessage());
        throw new Exception('Veritabanına bağlanılamadı');
    }

    $payment_id = intval($input['payment_id']);

    // Ödeme ve bilet bilgilerini getir
    $stmt = $pdo->prepare("
        SELECT 
            p.*,
            t.id as ticket_id,
            t.ticket_number,
            d.name as draw_name,
            d.description as draw_description,
            d.draw_date,
            ts.name as customer_name,
            ts.email,
            ts.phone
        FROM payments p
        JOIN ticket_sales ts ON p.ticket_sale_id = ts.id
        JOIN tickets t ON ts.ticket_id = t.id
        JOIN draws d ON t.draw_id = d.id
        WHERE p.id = ? AND p.status = 'pending'
        AND p.expires_at > NOW()
    ");
    $stmt->execute([$payment_id]);
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$payment) {
        error_log("Ödeme bulunamadı: Payment ID = " . $payment_id);
        throw new Exception('Geçerli bir ödeme bulunamadı');
    }

    // Ödeme bilgilerini logla
    error_log("Payment data: " . print_r($payment, true));

    $pdo->beginTransaction();

    try {
        // Ödemeyi onayla
        $stmt = $pdo->prepare("
            UPDATE payments 
            SET status = 'completed', completed_at = NOW() 
            WHERE id = ?
        ");
        $stmt->execute([$payment_id]);

        // Bileti satıldı olarak işaretle
        $stmt = $pdo->prepare("UPDATE tickets SET status = 'sold' WHERE id = ?");
        $stmt->execute([$payment['ticket_id']]);

        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Veritabanı güncelleme hatası: " . $e->getMessage());
        throw new Exception('Ödeme onaylanırken bir hata oluştu');
    }

    // E-posta gönder
    $mail = new PHPMailer(true);
    try {
        // SMTP ayarları
        $mail->SMTPDebug = 2; // Hata ayıklama için
        $mail->Debugoutput = function($str, $level) {
            error_log("PHPMailer: $str");
        };

        $mail->isSMTP();
        $mail->Host = $env['SMTP_HOST'];
        $mail->SMTPAuth = true;
        $mail->Username = $env['SMTP_USER'];
        $mail->Password = $env['SMTP_PASS'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = $env['SMTP_PORT'];
        $mail->CharSet = 'UTF-8';

        // Alıcı ayarları
        $mail->setFrom($env['SMTP_USER'], $env['APP_NAME']);
        $mail->addAddress($payment['email'], $payment['customer_name']);

        // E-posta içeriği
        $mail->isHTML(true);
        $mail->Subject = 'Bilet Satın Alma İşleminiz Onaylandı';
        
        // HTML formatında e-posta içeriği
        $mailContent = "
            <h2>Sayın {$payment['customer_name']},</h2>
            <p>Bilet satın alma işleminiz başarıyla onaylanmıştır.</p>
            
            <h3>Bilet Detayları:</h3>
            <ul>
                <li><strong>Çekiliş:</strong> {$payment['draw_name']}</li>
                <li><strong>Bilet Numarası:</strong> {$payment['ticket_number']}</li>
                <li><strong>Çekiliş Tarihi:</strong> " . date('d.m.Y H:i', strtotime($payment['draw_date'])) . "</li>
                <li><strong>Tutar:</strong> " . number_format($payment['amount'], 2) . " TL</li>
            </ul>
            
            <p><strong>Çekiliş Açıklaması:</strong><br>{$payment['draw_description']}</p>
            
            <p>Çekilişe katıldığınız için teşekkür ederiz.</p>
            
            <small>Bu e-posta otomatik olarak gönderilmiştir. Lütfen yanıtlamayınız.</small>
        ";
        
        $mail->Body = $mailContent;
        $mail->AltBody = strip_tags(str_replace(['<br>', '</li>'], "\n", $mailContent));

        $mail->send();
        error_log("E-posta başarıyla gönderildi: {$payment['email']}");
    } catch (Exception $e) {
        error_log("E-posta gönderimi başarısız: " . $mail->ErrorInfo);
        // E-posta gönderimi başarısız olsa bile işlemi iptal etmiyoruz
    }

    echo json_encode([
        'success' => true,
        'message' => 'Ödeme başarıyla onaylandı ve bilet bilgileri e-posta ile gönderildi'
    ]);

} catch (Exception $e) {
    error_log("Genel hata: " . $e->getMessage());
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Hata: ' . $e->getMessage(),
        'details' => $e->getTraceAsString()
    ]);
} 