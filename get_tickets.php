<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

try {
    if (!isset($_GET['draw_id'])) {
        throw new Exception('Çekiliş ID\'si gerekli');
    }

    $pdo = new PDO(
        "mysql:host=localhost;dbname=u377540006_sbilet_db",
        "u377540006_sbilet",
        "$0XrC9!u~"
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $draw_id = intval($_GET['draw_id']);

    // Biletleri getir
    $stmt = $pdo->prepare("SELECT id, ticket_number, status FROM tickets WHERE draw_id = ? ORDER BY ticket_number ASC");
    $stmt->execute([$draw_id]);
    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($tickets);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
} 