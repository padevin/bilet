<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Oturum kontrolü
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

if (!isset($_GET['id'])) {
    header('Location: dashboard.php');
    exit;
}

try {
    $pdo = new PDO(
        "mysql:host=localhost;dbname=u377540006_sbilet_db", 
        "u377540006_sbilet", 
        "$0XrC9!u~"
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $draw_id = intval($_GET['id']);
    
    // Mevcut durumu al
    $stmt = $pdo->prepare("SELECT status FROM draws WHERE id = ?");
    $stmt->execute([$draw_id]);
    $current_status = $stmt->fetchColumn();
    
    // Yeni durumu belirle
    $new_status = match($current_status) {
        'active' => 'completed',
        'completed' => 'cancelled',
        'cancelled' => 'active',
        default => 'active'
    };
    
    // Durumu güncelle
    $stmt = $pdo->prepare("UPDATE draws SET status = ? WHERE id = ?");
    $stmt->execute([$new_status, $draw_id]);
    
    $_SESSION['success'] = 'Çekiliş durumu güncellendi';
    
} catch (Exception $e) {
    $_SESSION['error'] = 'Hata: ' . $e->getMessage();
}

header('Location: dashboard.php'); 