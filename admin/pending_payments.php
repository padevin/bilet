<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

try {
    $pdo = new PDO(
        "mysql:host=localhost;dbname=u377540006_sbilet_db",
        "u377540006_sbilet",
        "$0XrC9!u~"
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Bekleyen ödemeleri getir
    $stmt = $pdo->prepare("
        SELECT 
            p.*,
            ts.name as customer_name,
            ts.email,
            ts.phone,
            t.ticket_number,
            d.name as draw_name,
            d.draw_date
        FROM payments p
        JOIN ticket_sales ts ON p.ticket_sale_id = ts.id
        JOIN tickets t ON ts.ticket_id = t.id
        JOIN draws d ON t.draw_id = d.id
        WHERE p.status = 'pending'
        AND p.expires_at > NOW()
        ORDER BY p.created_at DESC
    ");
    $stmt->execute();
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error = "Veritabanı hatası: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bekleyen Ödemeler - Admin Panel</title>
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
            max-width: 1200px;
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

        .payments-container {
            background: white;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .payment-item {
            padding: 20px;
            border-bottom: 1px solid var(--border);
            transition: background 0.2s ease;
        }

        .payment-item:last-child {
            border-bottom: none;
        }

        .payment-item:hover {
            background: #f8f9fa;
        }

        .payment-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .payment-code {
            font-family: monospace;
            font-size: 1.2em;
            font-weight: 600;
            color: var(--primary);
        }

        .payment-amount {
            font-weight: 600;
            color: var(--success);
        }

        .payment-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }

        .info-group {
            display: flex;
            flex-direction: column;
        }

        .info-label {
            font-size: 0.9em;
            color: #666;
            margin-bottom: 5px;
        }

        .info-value {
            font-weight: 500;
        }

        .payment-actions {
            display: flex;
            gap: 10px;
        }

        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.2s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9em;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-success {
            background: var(--success);
            color: white;
        }

        .btn-danger {
            background: var(--danger);
            color: white;
        }

        .btn:hover {
            transform: translateY(-2px);
            opacity: 0.9;
        }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #666;
        }

        .empty-state i {
            font-size: 3em;
            color: var(--border);
            margin-bottom: 20px;
        }

        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }

            .header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }

            .payment-header {
                flex-direction: column;
                gap: 10px;
                text-align: center;
            }

            .payment-info {
                grid-template-columns: 1fr;
            }

            .payment-actions {
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
            <h1>Bekleyen Ödemeler</h1>
            <a href="dashboard.php" class="btn btn-primary">
                <i class="fas fa-arrow-left"></i> Panele Dön
            </a>
        </div>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="payments-container">
            <?php if (empty($payments)): ?>
                <div class="empty-state">
                    <i class="fas fa-clock"></i>
                    <h3>Bekleyen Ödeme Yok</h3>
                    <p>Şu anda onay bekleyen ödeme bulunmuyor.</p>
                </div>
            <?php else: ?>
                <?php foreach ($payments as $payment): ?>
                    <div class="payment-item">
                        <div class="payment-header">
                            <div class="payment-code">
                                <?php echo htmlspecialchars($payment['payment_code']); ?>
                            </div>
                            <div class="payment-amount">
                                <?php echo number_format($payment['amount'], 2); ?> TL
                            </div>
                        </div>

                        <div class="payment-info">
                            <div class="info-group">
                                <span class="info-label">Müşteri</span>
                                <span class="info-value"><?php echo htmlspecialchars($payment['customer_name']); ?></span>
                            </div>
                            <div class="info-group">
                                <span class="info-label">E-posta</span>
                                <span class="info-value"><?php echo htmlspecialchars($payment['email']); ?></span>
                            </div>
                            <div class="info-group">
                                <span class="info-label">Telefon</span>
                                <span class="info-value"><?php echo htmlspecialchars($payment['phone']); ?></span>
                            </div>
                            <div class="info-group">
                                <span class="info-label">Çekiliş</span>
                                <span class="info-value"><?php echo htmlspecialchars($payment['draw_name']); ?></span>
                            </div>
                            <div class="info-group">
                                <span class="info-label">Bilet No</span>
                                <span class="info-value"><?php echo htmlspecialchars($payment['ticket_number']); ?></span>
                            </div>
                            <div class="info-group">
                                <span class="info-label">Son Ödeme</span>
                                <span class="info-value"><?php echo date('d.m.Y H:i', strtotime($payment['expires_at'])); ?></span>
                            </div>
                        </div>

                        <div class="payment-actions">
                            <button class="btn btn-success" onclick="approvePayment(<?php echo $payment['id']; ?>)">
                                <i class="fas fa-check"></i> Onayla
                            </button>
                            <button class="btn btn-danger" onclick="rejectPayment(<?php echo $payment['id']; ?>)">
                                <i class="fas fa-times"></i> Reddet
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
    function approvePayment(paymentId) {
        if (!confirm('Bu ödemeyi onaylamak istediğinize emin misiniz?')) {
            return;
        }

        fetch('approve_payment.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ payment_id: paymentId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Ödeme başarıyla onaylandı!');
                location.reload();
            } else {
                alert(data.message || 'Bir hata oluştu.');
            }
        })
        .catch(error => {
            console.error('Hata:', error);
            alert('Bir hata oluştu.');
        });
    }

    function rejectPayment(paymentId) {
        if (!confirm('Bu ödemeyi reddetmek istediğinize emin misiniz?')) {
            return;
        }

        fetch('reject_payment.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ payment_id: paymentId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Ödeme reddedildi!');
                location.reload();
            } else {
                alert(data.message || 'Bir hata oluştu.');
            }
        })
        .catch(error => {
            console.error('Hata:', error);
            alert('Bir hata oluştu.');
        });
    }
    </script>
</body>
</html> 