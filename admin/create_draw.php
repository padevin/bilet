<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo = new PDO(
            "mysql:host=localhost;dbname=u377540006_sbilet_db",
            "u377540006_sbilet",
            "$0XrC9!u~"
        );
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Form verilerini al
        $name = $_POST['name'] ?? '';
        $description = $_POST['description'] ?? '';
        $price = floatval($_POST['price'] ?? 0);
        $draw_date = $_POST['draw_date'] ?? '';
        $total_tickets = intval($_POST['total_tickets'] ?? 250);
        
        if (!$name || !$description || $price <= 0 || !$draw_date || $total_tickets <= 0) {
            throw new Exception('Tüm alanları doldurun');
        }
        
        // Çekilişi oluştur
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("INSERT INTO draws (name, description, price, draw_date, status) VALUES (?, ?, ?, ?, 'active')");
        if (!$stmt->execute([$name, $description, $price, $draw_date])) {
            throw new Exception('Çekiliş oluşturulamadı: ' . implode(', ', $stmt->errorInfo()));
        }
        
        $draw_id = $pdo->lastInsertId();
        
        // Biletleri oluştur
        $stmt = $pdo->prepare("INSERT INTO tickets (draw_id, ticket_number, status) VALUES (?, ?, 'available')");
        
        // Kullanılmış numaraları takip etmek için dizi
        $used_numbers = [];
        
        for ($i = 1; $i <= $total_tickets; $i++) {
            do {
                // 4 adet rastgele sayı oluştur (000-999 arası)
                $numbers = [];
                for ($j = 0; $j < 4; $j++) {
                    $numbers[] = str_pad(rand(0, 999), 3, '0', STR_PAD_LEFT);
                }
                $ticket_number = implode('-', $numbers);
                
            } while (in_array($ticket_number, $used_numbers));
            
            $used_numbers[] = $ticket_number;
            
            if (!$stmt->execute([$draw_id, $ticket_number])) {
                throw new Exception('Bilet oluşturulamadı: ' . implode(', ', $stmt->errorInfo()));
            }
        }
        
        $pdo->commit();
        $success = 'Çekiliş başarıyla oluşturuldu';
        
    } catch (Exception $e) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $error = 'Hata: ' . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yeni Çekiliş - Admin Panel</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #3498db;
            --secondary: #2980b9;
            --success: #2ecc71;
            --danger: #e74c3c;
            --warning: #f1c40f;
            --dark: #2c3e50;
            --light: #ecf0f1;
            --border: #bdc3c7;
            --shadow: 0 2px 15px rgba(0,0,0,0.1);
            --radius: 12px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8f9fa;
            color: var(--dark);
            line-height: 1.6;
        }

        .container {
            max-width: 800px;
            margin: 40px auto;
            padding: 20px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding: 20px;
            background: white;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
        }

        .header h1 {
            color: var(--primary);
            font-size: 1.8em;
        }

        .form-container {
            background: white;
            padding: 30px;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--dark);
            font-weight: 500;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 1em;
            transition: border-color 0.2s ease;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--primary);
        }

        .form-group textarea {
            height: 120px;
            resize: vertical;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.2s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 1em;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-secondary {
            background: var(--border);
            color: var(--dark);
        }

        .btn:hover {
            transform: translateY(-2px);
            opacity: 0.9;
        }

        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            color: white;
        }

        .alert-success {
            background: var(--success);
        }

        .alert-danger {
            background: var(--danger);
        }

        .buttons {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }

        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }

            .header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }

            .buttons {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Yeni Çekiliş Oluştur</h1>
            <a href="dashboard.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Geri Dön
            </a>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <div class="form-container">
            <form method="POST">
                <div class="form-group">
                    <label for="name">Çekiliş Adı</label>
                    <input type="text" id="name" name="name" required>
                </div>

                <div class="form-group">
                    <label for="description">Açıklama</label>
                    <textarea id="description" name="description" required></textarea>
                </div>

                <div class="form-group">
                    <label for="price">Bilet Fiyatı (TL)</label>
                    <input type="number" id="price" name="price" step="0.01" min="0" required>
                </div>

                <div class="form-group">
                    <label for="draw_date">Çekiliş Tarihi</label>
                    <input type="date" id="draw_date" name="draw_date" required>
                </div>

                <div class="form-group">
                    <label for="total_tickets">Toplam Bilet Sayısı</label>
                    <input type="number" id="total_tickets" name="total_tickets" value="250" min="1" max="1000" required>
                </div>

                <div class="buttons">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Çekiliş Oluştur
                    </button>
                    <a href="dashboard.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> İptal
                    </a>
                </div>
            </form>
        </div>
    </div>
</body>
</html> 