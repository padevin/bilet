<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_GET['code'])) {
    header('Location: index.php');
    exit;
}

try {
    $pdo = new PDO(
        "mysql:host=localhost;dbname=u377540006_sbilet_db",
        "u377540006_sbilet",
        "$0XrC9!u~"
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Ödeme ve bilet bilgilerini getir
    $stmt = $pdo->prepare("
        SELECT 
            p.*,
            ts.name as customer_name,
            ts.email,
            ts.phone,
            t.ticket_number,
            d.name as draw_name,
            d.draw_date,
            s.value as setting_value,
            s2.value as payment_name
        FROM payments p
        JOIN ticket_sales ts ON p.ticket_sale_id = ts.id
        JOIN tickets t ON ts.ticket_id = t.id
        JOIN draws d ON t.draw_id = d.id
        JOIN settings s ON s.key = 'payment_iban'
        JOIN settings s2 ON s2.key = 'payment_name'
        WHERE p.payment_code = ?
    ");
    $stmt->execute([$_GET['code']]);
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$payment) {
        throw new Exception('Ödeme bulunamadı');
    }

    // Ödeme süresi dolmuş mu kontrol et
    if ($payment['status'] === 'expired' || strtotime($payment['expires_at']) < time()) {
        if ($payment['status'] !== 'expired') {
            // Ödeme süresini güncelle
            $stmt = $pdo->prepare("UPDATE payments SET status = 'expired' WHERE id = ?");
            $stmt->execute([$payment['id']]);
        }
        throw new Exception('Ödeme süresi dolmuş');
    }

    // Ödeme tamamlanmış mı kontrol et
    if ($payment['status'] === 'completed') {
        throw new Exception('Bu ödeme zaten tamamlanmış');
    }

    // Kalan süreyi hesapla
    $remaining_time = strtotime($payment['expires_at']) - time();
    $remaining_minutes = floor($remaining_time / 60);
    $remaining_seconds = $remaining_time % 60;

} catch (Exception $e) {
    $error = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ödeme Sayfası - Bilet Satış Sistemi</title>
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

        .payment-container {
            background: white;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            padding: 30px;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .header h1 {
            color: var(--primary);
            font-size: 2em;
            margin-bottom: 10px;
        }

        .timer {
            font-size: 2em;
            text-align: center;
            margin: 20px 0;
            color: var(--danger);
            font-weight: 600;
        }

        .info-section {
            background: var(--light);
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .info-section h3 {
            color: var(--dark);
            margin-bottom: 15px;
            font-size: 1.2em;
        }

        .info-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--border);
        }

        .info-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }

        .info-label {
            font-weight: 500;
            color: var(--dark);
        }

        .info-value {
            color: #666;
        }

        .payment-info {
            background: #fff3cd;
            border: 1px solid #ffeeba;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }

        .payment-code {
            background: var(--dark);
            color: white;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            font-size: 1.2em;
            font-family: monospace;
            margin: 20px 0;
            user-select: all;
        }

        .copy-btn {
            background: var(--primary);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9em;
            display: block;
            margin: 10px auto;
            transition: background 0.3s ease;
        }

        .copy-btn:hover {
            background: var(--secondary);
        }

        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            color: white;
            text-align: center;
        }

        .alert-danger {
            background: var(--danger);
        }

        @media (max-width: 768px) {
            .container {
                padding: 10px;
                margin: 20px auto;
            }

            .payment-container {
                padding: 20px;
            }

            .header h1 {
                font-size: 1.5em;
            }

            .timer {
                font-size: 1.5em;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php else: ?>
            <div class="payment-container">
                <div class="header">
                    <h1>Ödeme Sayfası</h1>
                    <p>Lütfen ödemenizi aşağıdaki bilgileri kullanarak yapın</p>
                </div>

                <div class="timer" id="timer">
                    <?php echo sprintf('%02d:%02d', $remaining_minutes, $remaining_seconds); ?>
                </div>

                <div class="info-section">
                    <h3>Bilet Bilgileri</h3>
                    <div class="info-item">
                        <span class="info-label">Çekiliş:</span>
                        <span class="info-value"><?php echo htmlspecialchars($payment['draw_name']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Bilet No:</span>
                        <span class="info-value"><?php echo htmlspecialchars($payment['ticket_number']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Tutar:</span>
                        <span class="info-value"><?php echo number_format($payment['amount'], 2); ?> TL</span>
                    </div>
                </div>

                <div class="payment-info">
                    <h3>Ödeme Bilgileri</h3>
                    <div class="info-item">
                        <span class="info-label">Alıcı:</span>
                        <span class="info-value"><?php echo htmlspecialchars($payment['payment_name']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">IBAN:</span>
                        <span class="info-value"><?php echo htmlspecialchars($payment['setting_value']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Açıklama:</span>
                        <div class="payment-code"><?php echo htmlspecialchars($payment['payment_code']); ?></div>
                        <button class="copy-btn" onclick="copyPaymentCode()">
                            <i class="fas fa-copy"></i> Kopyala
                        </button>
                    </div>
                </div>

                <div class="info-section">
                    <h3>Önemli Bilgiler</h3>
                    <ul>
                        <li>Ödeme yaparken açıklama kısmına mutlaka yukarıdaki kodu yazın.</li>
                        <li>Ödemenizi <?php echo $remaining_minutes; ?> dakika <?php echo $remaining_seconds; ?> saniye içinde yapmalısınız.</li>
                        <li>Süre dolmadan ödemenizi tamamlamazsanız biletiniz iptal edilecektir.</li>
                        <li>Ödemeniz onaylandıktan sonra biletiniz aktif olacaktır.</li>
                    </ul>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
    // Geri sayım sayacı
    let timeLeft = <?php echo $remaining_time; ?>;
    const timerElement = document.getElementById('timer');

    const timer = setInterval(() => {
        timeLeft--;
        
        if (timeLeft <= 0) {
            clearInterval(timer);
            location.reload();
            return;
        }

        const minutes = Math.floor(timeLeft / 60);
        const seconds = timeLeft % 60;
        timerElement.textContent = `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
    }, 1000);

    // Ödeme kodunu kopyalama
    function copyPaymentCode() {
        const code = '<?php echo $payment['payment_code'] ?? ''; ?>';
        navigator.clipboard.writeText(code).then(() => {
            const btn = document.querySelector('.copy-btn');
            btn.innerHTML = '<i class="fas fa-check"></i> Kopyalandı';
            setTimeout(() => {
                btn.innerHTML = '<i class="fas fa-copy"></i> Kopyala';
            }, 2000);
        });
    }
    </script>
</body>
</html> 