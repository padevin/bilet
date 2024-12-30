<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Varsayılan olarak boş dizi tanımla
$draws = [];
$error = null;

try {
    $pdo = new PDO(
        "mysql:host=localhost;dbname=u377540006_sbilet_db",
        "u377540006_sbilet",
        "$0XrC9!u~"
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Aktif çekilişleri getir
    $stmt = $pdo->query("SELECT d.*, 
        (SELECT COUNT(*) FROM tickets t WHERE t.draw_id = d.id AND t.status = 'available') as available_tickets,
        (SELECT COUNT(*) FROM tickets t WHERE t.draw_id = d.id AND t.status = 'sold') as sold_tickets,
        (SELECT COUNT(*) FROM tickets t WHERE t.draw_id = d.id) as total_tickets,
        (SELECT COUNT(*) FROM tickets t WHERE t.draw_id = d.id AND t.status = 'sold') * d.price as total_revenue
        FROM draws d 
        ORDER BY d.draw_date DESC");
    $draws = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error = "Veritabanı hatası: " . $e->getMessage();
}

// Toplam değerleri hesapla
$total_sold = 0;
$total_revenue = 0;

if (!empty($draws)) {
    foreach ($draws as $draw) {
        $total_sold += intval($draw['sold_tickets']);
        $total_revenue += floatval($draw['total_revenue'] ?? 0);
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Bilet Satış Sistemi</title>
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
            max-width: 1400px;
            margin: 0 auto;
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

        .header-buttons {
            display: flex;
            gap: 15px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.2s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: var(--primary);
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

        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            text-align: center;
        }

        .stat-card h3 {
            color: var(--dark);
            font-size: 1.1em;
            margin-bottom: 10px;
        }

        .stat-card .value {
            font-size: 2em;
            font-weight: 600;
            color: var(--primary);
        }

        .draws-container {
            background: white;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .draws-header {
            padding: 20px;
            background: var(--primary);
            color: white;
            font-weight: 500;
        }

        .draw-item {
            padding: 20px;
            border-bottom: 1px solid var(--border);
            transition: background 0.2s ease;
        }

        .draw-item:hover {
            background: #f8f9fa;
        }

        .draw-item:last-child {
            border-bottom: none;
        }

        .draw-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .draw-title {
            font-size: 1.2em;
            font-weight: 600;
            color: var(--dark);
        }

        .draw-status {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.9em;
            font-weight: 500;
        }

        .status-active {
            background: var(--success);
            color: white;
        }

        .status-completed {
            background: var(--border);
            color: var(--dark);
        }

        .draw-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }

        .info-item {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #666;
        }

        .info-item i {
            color: var(--primary);
            width: 20px;
        }

        .draw-actions {
            display: flex;
            gap: 10px;
        }

        .btn-sm {
            padding: 8px 15px;
            font-size: 0.9em;
        }

        .tickets-list {
            display: none;
            margin-top: 15px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .ticket-item {
            display: grid;
            grid-template-columns: 1fr 2fr 2fr 1fr 1fr;
            padding: 10px;
            border-bottom: 1px solid var(--border);
            font-size: 0.9em;
        }

        .ticket-item:last-child {
            border-bottom: none;
        }

        .ticket-header {
            font-weight: 600;
            color: var(--dark);
            background: white;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 10px;
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }

            .draw-header {
                flex-direction: column;
                gap: 10px;
                text-align: center;
            }

            .draw-info {
                grid-template-columns: 1fr;
            }

            .draw-actions {
                justify-content: center;
            }

            .ticket-item {
                grid-template-columns: 1fr;
                gap: 5px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Admin Panel</h1>
            <div class="header-buttons">
                <a href="create_draw.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Yeni Çekiliş
                </a>
                <a href="pending_payments.php" class="btn btn-warning">
                    <i class="fas fa-clock"></i> Bekleyen Ödemeler
                </a>
                <a href="logout.php" class="btn btn-danger">
                    <i class="fas fa-sign-out-alt"></i> Çıkış
                </a>
            </div>
        </div>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="stats-container">
            <div class="stat-card">
                <h3>Toplam Çekiliş</h3>
                <div class="value"><?php echo count($draws); ?></div>
            </div>
            <div class="stat-card">
                <h3>Toplam Satılan Bilet</h3>
                <div class="value"><?php echo $total_sold; ?></div>
            </div>
            <div class="stat-card">
                <h3>Toplam Gelir</h3>
                <div class="value"><?php echo number_format($total_revenue, 2); ?> TL</div>
            </div>
        </div>

        <div class="draws-container">
            <div class="draws-header">
                <h2>Çekilişler</h2>
            </div>

            <?php foreach ($draws as $draw): ?>
                <div class="draw-item">
                    <div class="draw-header">
                        <div class="draw-title"><?php echo htmlspecialchars($draw['name']); ?></div>
                        <div class="draw-status <?php echo $draw['status'] === 'active' ? 'status-active' : 'status-completed'; ?>">
                            <?php echo $draw['status'] === 'active' ? 'Aktif' : 'Tamamlandı'; ?>
                        </div>
                    </div>

                    <div class="draw-info">
                        <div class="info-item">
                            <i class="fas fa-tag"></i>
                            <?php echo number_format($draw['price'], 2); ?> TL
                        </div>
                        <div class="info-item">
                            <i class="fas fa-calendar"></i>
                            <?php echo date('d.m.Y', strtotime($draw['draw_date'])); ?>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-ticket-alt"></i>
                            <?php echo $draw['sold_tickets']; ?> / <?php echo $draw['total_tickets']; ?> Bilet Satıldı
                        </div>
                        <div class="info-item">
                            <i class="fas fa-money-bill-wave"></i>
                            <?php echo number_format($draw['total_revenue'] ?? 0, 2); ?> TL Gelir
                        </div>
                    </div>

                    <div class="draw-actions">
                        <button class="btn btn-primary btn-sm" onclick="toggleTickets(<?php echo $draw['id']; ?>)">
                            <i class="fas fa-list"></i> Biletleri Göster
                        </button>
                        <?php if ($draw['status'] === 'active'): ?>
                            <button class="btn btn-danger btn-sm" onclick="completeDraw(<?php echo $draw['id']; ?>)">
                                <i class="fas fa-check"></i> Çekilişi Tamamla
                            </button>
                        <?php endif; ?>
                    </div>

                    <div id="tickets-<?php echo $draw['id']; ?>" class="tickets-list">
                        <div class="ticket-item ticket-header">
                            <div>Bilet No</div>
                            <div>Müşteri</div>
                            <div>İletişim</div>
                            <div>Tarih</div>
                            <div>Durum</div>
                        </div>
                        <!-- Biletler AJAX ile yüklenecek -->
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script>
    function toggleTickets(drawId) {
        const ticketsList = document.getElementById(`tickets-${drawId}`);
        
        if (ticketsList.style.display === 'block') {
            ticketsList.style.display = 'none';
            return;
        }

        ticketsList.style.display = 'block';
        
        // Biletleri getir
        fetch(`get_draw_tickets.php?draw_id=${drawId}`)
            .then(response => response.json())
            .then(data => {
                const ticketsList = document.getElementById(`tickets-${drawId}`);
                let html = `
                    <div class="ticket-item ticket-header">
                        <div>Bilet No</div>
                        <div>Müşteri</div>
                        <div>İletişim</div>
                        <div>Tarih</div>
                        <div>Durum</div>
                    </div>
                `;

                data.forEach(ticket => {
                    html += `
                        <div class="ticket-item">
                            <div>${ticket.ticket_number}</div>
                            <div>${ticket.name || '-'}</div>
                            <div>
                                ${ticket.email ? `<div>${ticket.email}</div>` : ''}
                                ${ticket.phone ? `<div>${ticket.phone}</div>` : ''}
                            </div>
                            <div>${ticket.purchase_date ? new Date(ticket.purchase_date).toLocaleDateString('tr-TR') : '-'}</div>
                            <div>${ticket.status === 'sold' ? '<span style="color: var(--danger)">Satıldı</span>' : '<span style="color: var(--success)">Müsait</span>'}</div>
                        </div>
                    `;
                });

                ticketsList.innerHTML = html;
            })
            .catch(error => {
                console.error('Hata:', error);
                alert('Biletler yüklenirken bir hata oluştu.');
            });
    }

    function completeDraw(drawId) {
        if (!confirm('Çekilişi tamamlamak istediğinize emin misiniz?')) {
            return;
        }

        fetch('complete_draw.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ draw_id: drawId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Çekiliş başarıyla tamamlandı!');
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