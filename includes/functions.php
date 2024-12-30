<?php
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Security.php';

function generateTickets($conn) {
    $stmt = $conn->prepare("INSERT INTO tickets (numbers) VALUES (?)");
    $existingTickets = []; // Daha önce üretilen bilet numaralarını tutacak bir dizi

    for ($i = 0; $i < 250; $i++) {
        do {
            $numbers = implode('-', array_map(function() {
                return str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
            }, range(1, 4)));
        } while (in_array($numbers, $existingTickets)); // Bilet numarası zaten mevcutsa, yeni bir numara üret

        $existingTickets[] = $numbers; // Yeni üretilen bilet numarasını diziye ekle
        $stmt->bind_param("s", $numbers);
        $stmt->execute();
    }

    $stmt->close();
}

function getAvailableTickets($conn) {
    $sql = "SELECT * FROM tickets WHERE is_selected=0";
    return $conn->query($sql);
}

function selectTicket($conn, $ticket_id, $name, $phone, $iban) {
    $stmt = $conn->prepare("INSERT INTO users (name, phone, iban, ticket_id) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("sssi", $name, $phone, $iban, $ticket_id);
    if ($stmt->execute()) {
        $user_id = $stmt->insert_id;
        $stmt->close();
        $update_ticket = $conn->prepare("UPDATE tickets SET is_selected=1, user_id=? WHERE id=?");
        $update_ticket->bind_param("ii", $user_id, $ticket_id);
        $update_ticket->execute();
        $update_ticket->close();
        return true;
    } else {
        $stmt->close();
        return false;
    }
}

function confirmPayment($conn, $ticket_id) {
    $update_ticket = $conn->prepare("UPDATE tickets SET is_paid=1 WHERE id=?");
    $update_ticket->bind_param("i", $ticket_id);
    $result = $update_ticket->execute();
    $update_ticket->close();
    return $result;
}

function login($conn, $username, $password) {
    $stmt = $conn->prepare("SELECT id, password FROM admins WHERE username=?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->bind_result($id, $hashed_password);
    $stmt->fetch();
    $stmt->close();

    if (password_verify($password, $hashed_password)) {
        $_SESSION['admin_id'] = $id;
        return true;
    } else {
        return false;
    }
}

function isLoggedIn() {
    return isset($_SESSION['admin_id']);
}

function logout() {
    session_destroy();
}

function sendConfirmationEmail($user_email, $ticket_info) {
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = $_ENV['SMTP_HOST'];
        $mail->SMTPAuth = true;
        $mail->Username = $_ENV['SMTP_USER'];
        $mail->Password = $_ENV['SMTP_PASS'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = $_ENV['SMTP_PORT'];

        $mail->setFrom($_ENV['SMTP_USER'], $_ENV['APP_NAME']);
        $mail->addAddress($user_email);

        $mail->isHTML(true);
        $mail->Subject = 'Bilet Satın Alma Onayı';
        $mail->Body = generateEmailTemplate($ticket_info);

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email gönderme hatası: {$e->getMessage()}");
        return false;
    }
}

function generateEmailTemplate($ticket_info) {
    ob_start();
    include __DIR__ . '/../templates/email/ticket_confirmation.php';
    return ob_get_clean();
}

function generateQRCode($ticket_id) {
    require_once __DIR__ . '/../vendor/autoload.php';
    
    $qrCode = new Endroid\QrCode\QrCode($_ENV['APP_URL'] . '/verify.php?ticket=' . $ticket_id);
    $qrCode->setSize(300);
    $qrCode->setMargin(10);
    
    $writer = new Endroid\QrCode\Writer\PngWriter();
    $result = $writer->write($qrCode);
    
    $qrPath = __DIR__ . '/../public/qrcodes/' . $ticket_id . '.png';
    $result->saveToFile($qrPath);
    
    return '/qrcodes/' . $ticket_id . '.png';
}

function generateInvoice($ticket_id) {
    require_once __DIR__ . '/../vendor/autoload.php';
    
    $db = Database::getInstance();
    $stmt = $db->prepare("SELECT * FROM tickets WHERE id = ?");
    $db->bind(1, $ticket_id);
    $ticket = $db->single();
    
    if (!$ticket) {
        return false;
    }
    
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor($_ENV['APP_NAME']);
    $pdf->SetTitle('Fatura #' . $ticket_id);
    
    $pdf->AddPage();
    
    // Fatura şablonunu yükle
    ob_start();
    include __DIR__ . '/../templates/pdf/invoice.php';
    $html = ob_get_clean();
    
    $pdf->writeHTML($html, true, false, true, false, '');
    
    $pdfPath = __DIR__ . '/../public/invoices/' . $ticket_id . '.pdf';
    $pdf->Output($pdfPath, 'F');
    
    return '/invoices/' . $ticket_id . '.pdf';
}

function getTicketStatistics($start_date = null, $end_date = null) {
    $db = Database::getInstance();
    
    $where = "";
    $params = [];
    
    if ($start_date && $end_date) {
        $where = "WHERE created_at BETWEEN ? AND ?";
        $params = [$start_date, $end_date];
    }
    
    $stats = [
        'total_sales' => 0,
        'total_revenue' => 0,
        'sales_by_type' => [],
        'sales_by_date' => []
    ];
    
    // Toplam satış ve gelir
    $stmt = $db->prepare("
        SELECT 
            COUNT(*) as total_sales,
            SUM(price) as total_revenue
        FROM tickets
        $where
    ");
    
    foreach ($params as $i => $param) {
        $db->bind($i + 1, $param);
    }
    
    $result = $db->single();
    $stats['total_sales'] = $result['total_sales'];
    $stats['total_revenue'] = $result['total_revenue'];
    
    // Bilet tipine göre satışlar
    $stmt = $db->prepare("
        SELECT 
            ticket_type,
            COUNT(*) as count,
            SUM(price) as revenue
        FROM tickets
        $where
        GROUP BY ticket_type
    ");
    
    foreach ($params as $i => $param) {
        $db->bind($i + 1, $param);
    }
    
    $stats['sales_by_type'] = $db->resultSet();
    
    // Tarihe göre satışlar
    $stmt = $db->prepare("
        SELECT 
            DATE(created_at) as date,
            COUNT(*) as count,
            SUM(price) as revenue
        FROM tickets
        $where
        GROUP BY DATE(created_at)
        ORDER BY date DESC
    ");
    
    foreach ($params as $i => $param) {
        $db->bind($i + 1, $param);
    }
    
    $stats['sales_by_date'] = $db->resultSet();
    
    return $stats;
}

function validateTicket($ticket_id, $code) {
    $db = Database::getInstance();
    $stmt = $db->prepare("SELECT * FROM tickets WHERE id = ? AND verification_code = ? AND is_used = 0");
    $db->bind(1, $ticket_id);
    $db->bind(2, $code);
    
    $ticket = $db->single();
    
    if ($ticket) {
        // Bileti kullanıldı olarak işaretle
        $stmt = $db->prepare("UPDATE tickets SET is_used = 1, used_at = NOW() WHERE id = ?");
        $db->bind(1, $ticket_id);
        $db->execute();
        
        return true;
    }
    
    return false;
}

function createBackup() {
    $date = date('Y-m-d_H-i-s');
    $backupDir = __DIR__ . '/../backups/';
    
    if (!is_dir($backupDir)) {
        mkdir($backupDir, 0755, true);
    }
    
    // Veritabanı yedeği
    $dbBackupPath = $backupDir . "db_backup_{$date}.sql";
    $command = sprintf(
        'mysqldump -h %s -u %s %s %s > %s',
        escapeshellarg($_ENV['DB_HOST']),
        escapeshellarg($_ENV['DB_USER']),
        $_ENV['DB_PASS'] ? '-p' . escapeshellarg($_ENV['DB_PASS']) : '',
        escapeshellarg($_ENV['DB_NAME']),
        escapeshellarg($dbBackupPath)
    );
    
    exec($command, $output, $return_var);
    
    if ($return_var !== 0) {
        error_log("Veritabanı yedekleme hatası");
        return false;
    }
    
    // Dosya yedeği
    $fileBackupPath = $backupDir . "files_backup_{$date}.zip";
    $zip = new ZipArchive();
    
    if ($zip->open($fileBackupPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(__DIR__ . '/../'),
            RecursiveIteratorIterator::LEAVES_ONLY
        );
        
        foreach ($files as $file) {
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen(__DIR__ . '/../') + 1);
                
                // Yedekleme klasörünü ve bazı dosyaları hariç tut
                if (strpos($relativePath, 'backups/') === 0 ||
                    strpos($relativePath, 'vendor/') === 0 ||
                    strpos($relativePath, 'node_modules/') === 0) {
                    continue;
                }
                
                $zip->addFile($filePath, $relativePath);
            }
        }
        
        $zip->close();
        return true;
    }
    
    return false;
}

function logActivity($user_id, $action, $details = null) {
    $db = Database::getInstance();
    $stmt = $db->prepare("
        INSERT INTO activity_logs (user_id, action, details, ip_address, created_at)
        VALUES (?, ?, ?, ?, NOW())
    ");
    
    $db->bind(1, $user_id);
    $db->bind(2, $action);
    $db->bind(3, $details ? json_encode($details) : null);
    $db->bind(4, $_SERVER['REMOTE_ADDR']);
    
    return $db->execute();
}

function translateText($text, $target_language) {
    $translate = new Google\Cloud\Translate\TranslateClient([
        'key' => $_ENV['GOOGLE_TRANSLATE_API_KEY']
    ]);
    
    try {
        $result = $translate->translate($text, [
            'target' => $target_language
        ]);
        
        return $result['text'];
    } catch (Exception $e) {
        error_log("Çeviri hatası: {$e->getMessage()}");
        return $text;
    }
}

function sendSMS($phone, $message) {
    // SMS API entegrasyonu burada yapılacak
    // Örnek: Netgsm, İleti Merkezi vb.
    return true;
}
?>