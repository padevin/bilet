<?php
$password = 'admin123';
$hash = password_hash($password, PASSWORD_DEFAULT);
echo "Şifre: " . $password . "\n";
echo "Hash: " . $hash . "\n";

// Test edelim
if (password_verify($password, $hash)) {
    echo "Şifre doğrulama başarılı!\n";
} else {
    echo "Şifre doğrulama başarısız!\n";
}

// Veritabanına eklemek için SQL
echo "\nSQL sorgusu:\n";
echo "INSERT INTO admins (username, password) VALUES ('admin', '" . $hash . "');";
?> 