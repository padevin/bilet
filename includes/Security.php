<?php
class Security {
    public static function generateCSRFToken() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function validateCSRFToken($token) {
        if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
            throw new Exception('CSRF token doğrulaması başarısız.');
        }
        return true;
    }

    public static function checkRateLimit($ip, $limit = 100, $minutes = 60) {
        $db = Database::getInstance();
        
        // Eski kayıtları temizle
        $stmt = $db->prepare("DELETE FROM rate_limits WHERE created_at < DATE_SUB(NOW(), INTERVAL ? MINUTE)");
        $db->bind(1, $minutes);
        $db->execute();
        
        // Mevcut istek sayısını kontrol et
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM rate_limits WHERE ip = ? AND created_at > DATE_SUB(NOW(), INTERVAL ? MINUTE)");
        $db->bind(1, $ip);
        $db->bind(2, $minutes);
        $result = $db->single();
        
        if ($result['count'] >= $limit) {
            throw new Exception('Çok fazla istek gönderdiniz. Lütfen daha sonra tekrar deneyin.');
        }
        
        // Yeni isteği kaydet
        $stmt = $db->prepare("INSERT INTO rate_limits (ip, created_at) VALUES (?, NOW())");
        $db->bind(1, $ip);
        $db->execute();
        
        return true;
    }

    public static function sanitizeInput($data) {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = self::sanitizeInput($value);
            }
        } else {
            $data = trim($data);
            $data = stripslashes($data);
            $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
        }
        return $data;
    }

    public static function validateIBAN($iban) {
        $iban = strtoupper(str_replace(' ', '', $iban));
        if (!preg_match('/^TR\d{24}$/', $iban)) {
            return false;
        }
        return true;
    }

    public static function validatePhone($phone) {
        return preg_match('/^[0-9]{10}$/', $phone);
    }
} 