<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Geçersiz istek metodu');
    }

    $pdo = new PDO(
        "mysql:host=localhost;dbname=u377540006_sbilet_db",
        "u377540006_sbilet",
        "$0XrC9!u~"
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $draw_id = $_POST['draw_id'] ?? null;
    $ticket_id = $_POST['ticket_id'] ?? null;
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';

    if (!$draw_id || !$ticket_id || !$name || !$email || !$phone) {
        throw new Exception('Tüm alanları doldurun');
    }

    // Bilet ve çekiliş bilgilerini kontrol et
    $check = $pdo->prepare("
        SELECT t.status, d.price, d.status as draw_status 
        FROM tickets t 
        JOIN draws d ON t.draw_id = d.id 
        WHERE t.id = ? AND t.draw_id = ?
    ");
    $check->execute([$ticket_id, $draw_id]);
    $ticket = $check->fetch(PDO::FETCH_ASSOC);

    if (!$ticket) {
        throw new Exception('Bilet bulunamadı');
    }

    if ($ticket['status'] !== 'available') {
        throw new Exception('Seçilen bilet artık müsait değil');
    }

    if ($ticket['draw_status'] !== 'active') {
        throw new Exception('Bu çekiliş artık aktif değil');
    }

    $pdo->beginTransaction();

    // Bilet satışını kaydet
    $stmt = $pdo->prepare("
        INSERT INTO ticket_sales (
            draw_id, 
            ticket_id, 
            name, 
            email, 
            phone,
            purchase_date
        ) VALUES (?, ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([
        $draw_id, 
        $ticket_id, 
        $name, 
        $email, 
        $phone
    ]);
    
    $ticket_sale_id = $pdo->lastInsertId();

    // Ödeme kaydı oluştur
    $payment_code = strtoupper(substr(md5(uniqid()), 0, 8));
    $expires_at = date('Y-m-d H:i:s', strtotime('+10 minutes'));
    
    $stmt = $pdo->prepare("
        INSERT INTO payments (
            ticket_sale_id,
            payment_code,
            amount,
            expires_at
        ) VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([
        $ticket_sale_id,
        $payment_code,
        $ticket['price'],
        $expires_at
    ]);

    // Bilet durumunu güncelle
    $stmt = $pdo->prepare("UPDATE tickets SET status = 'pending' WHERE id = ?");
    $stmt->execute([$ticket_id]);

    $pdo->commit();

    echo json_encode([
        'success' => true, 
        'message' => 'Bilet rezerve edildi. Ödeme sayfasına yönlendiriliyorsunuz...',
        'redirect' => 'payment.php?code=' . $payment_code
    ]);

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} 