<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

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

    $pdo = new PDO(
        "mysql:host=localhost;dbname=u377540006_sbilet_db",
        "u377540006_sbilet",
        "$0XrC9!u~"
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $payment_id = intval($input['payment_id']);

    // Ödeme bilgilerini getir
    $stmt = $pdo->prepare("
        SELECT p.*, t.id as ticket_id
        FROM payments p
        JOIN ticket_sales ts ON p.ticket_sale_id = ts.id
        JOIN tickets t ON ts.ticket_id = t.id
        WHERE p.id = ? AND p.status = 'pending'
    ");
    $stmt->execute([$payment_id]);
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$payment) {
        throw new Exception('Geçerli bir ödeme bulunamadı');
    }

    $pdo->beginTransaction();

    // Ödemeyi reddet
    $stmt = $pdo->prepare("UPDATE payments SET status = 'expired' WHERE id = ?");
    $stmt->execute([$payment_id]);

    // Bileti tekrar müsait yap
    $stmt = $pdo->prepare("UPDATE tickets SET status = 'available' WHERE id = ?");
    $stmt->execute([$payment['ticket_id']]);

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Ödeme reddedildi'
    ]);

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 