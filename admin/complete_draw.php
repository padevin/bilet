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
    
    if (!isset($input['draw_id'])) {
        throw new Exception('Çekiliş ID\'si gerekli');
    }

    $pdo = new PDO(
        "mysql:host=localhost;dbname=u377540006_sbilet_db",
        "u377540006_sbilet",
        "$0XrC9!u~"
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $draw_id = intval($input['draw_id']);

    // Çekilişin durumunu kontrol et
    $stmt = $pdo->prepare("SELECT status FROM draws WHERE id = ?");
    $stmt->execute([$draw_id]);
    $draw = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$draw) {
        throw new Exception('Çekiliş bulunamadı');
    }

    if ($draw['status'] !== 'active') {
        throw new Exception('Bu çekiliş zaten tamamlanmış');
    }

    // Çekilişi tamamla
    $stmt = $pdo->prepare("UPDATE draws SET status = 'completed' WHERE id = ?");
    $stmt->execute([$draw_id]);

    echo json_encode([
        'success' => true,
        'message' => 'Çekiliş başarıyla tamamlandı'
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 