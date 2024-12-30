<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

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
        (SELECT COUNT(*) FROM tickets t WHERE t.draw_id = d.id) as total_tickets
        FROM draws d 
        WHERE d.status = 'active' 
        ORDER BY d.draw_date ASC");
    $draws = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error = "Veritabanı hatası: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bilet Satış Sistemi</title>
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
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 40px;
            padding: 30px;
            background: white;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
        }

        .header h1 {
            font-size: 2.5em;
            color: var(--primary);
            margin-bottom: 10px;
        }

        .header p {
            color: #666;
            font-size: 1.1em;
        }

        .draws-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }

        .draw-card {
            background: white;
            border-radius: var(--radius);
            padding: 25px;
            box-shadow: var(--shadow);
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(0,0,0,0.05);
        }

        .draw-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }

        .draw-card h3 {
            color: var(--dark);
            margin-bottom: 15px;
            font-size: 1.4em;
            font-weight: 600;
        }

        .draw-info {
            margin: 20px 0;
            font-size: 1em;
        }

        .draw-info span {
            display: flex;
            align-items: center;
            margin: 10px 0;
            color: #555;
        }

        .draw-info i {
            margin-right: 10px;
            color: var(--primary);
            width: 20px;
        }

        .ticket-count {
            position: absolute;
            top: 15px;
            right: 15px;
            background: var(--primary);
            color: white;
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 0.9em;
            font-weight: 500;
            box-shadow: 0 2px 10px rgba(52, 152, 219, 0.3);
        }

        .tickets-container {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 90%;
            max-width: 800px;
            max-height: 80vh;
            background: white;
            padding: 25px;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            z-index: 1000;
            overflow-y: auto;
        }

        .tickets-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            backdrop-filter: blur(5px);
            z-index: 999;
        }

        .tickets-container h3 {
            color: var(--dark);
            margin-bottom: 20px;
            font-size: 1.3em;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .tickets-container .close-tickets {
            cursor: pointer;
            font-size: 24px;
            color: #666;
            transition: color 0.2s ease;
        }

        .tickets-container .close-tickets:hover {
            color: var(--danger);
        }

        .tickets-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(110px, 1fr));
            gap: 10px;
            margin-top: 20px;
        }

        .tickets-search {
            width: 100%;
            padding: 10px;
            border: 1px solid var(--border);
            border-radius: 8px;
            margin-bottom: 15px;
            font-size: 0.9em;
        }

        .tickets-status {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
            font-size: 0.9em;
        }

        .status-item {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .status-item span {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
        }

        .status-item.available span {
            background: var(--success);
        }

        .status-item.sold span {
            background: var(--danger);
        }

        .ticket {
            padding: 12px;
            border-radius: 8px;
            text-align: center;
            font-family: 'Courier New', monospace;
            font-weight: 600;
            font-size: 0.9em;
            transition: all 0.2s ease;
        }

        .ticket.available {
            background: var(--success);
            color: white;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(46, 204, 113, 0.3);
        }

        .ticket.available:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(46, 204, 113, 0.4);
        }

        .ticket.sold {
            background: var(--danger);
            color: white;
            opacity: 0.8;
            cursor: not-allowed;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            backdrop-filter: blur(5px);
        }

        .modal-content {
            position: relative;
            background: white;
            width: 90%;
            max-width: 500px;
            margin: 50px auto;
            padding: 30px;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
        }

        .modal-content h3 {
            color: var(--dark);
            margin-bottom: 25px;
            font-size: 1.4em;
            text-align: center;
        }

        .close-modal {
            position: absolute;
            top: 15px;
            right: 15px;
            cursor: pointer;
            font-size: 24px;
            color: #666;
            transition: color 0.2s ease;
        }

        .close-modal:hover {
            color: var(--danger);
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

        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 1em;
            transition: border-color 0.2s ease;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--primary);
        }

        .select-btn {
            width: 100%;
            padding: 12px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1em;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .select-btn:hover {
            background: var(--secondary);
            transform: translateY(-2px);
        }

        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }

            .header {
                padding: 20px;
            }

            .header h1 {
                font-size: 2em;
            }

            .draws-container {
                grid-template-columns: 1fr;
            }

            .tickets-grid {
                grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
            }

            .modal-content {
                margin: 20px auto;
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Bilet Satış Sistemi</h1>
            <p>Aktif Çekilişler</p>
        </div>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="draws-container">
            <?php foreach ($draws as $draw): ?>
                <div class="draw-card" onclick="showTickets(<?php echo $draw['id']; ?>)">
                    <div class="ticket-count">
                        <?php echo $draw['available_tickets']; ?> / <?php echo $draw['total_tickets']; ?> Bilet
                    </div>
                    <h3><?php echo htmlspecialchars($draw['name']); ?></h3>
                    <div class="draw-info">
                        <span><i class="fas fa-tag"></i> <?php echo number_format($draw['price'], 2); ?> TL</span>
                        <span><i class="fas fa-calendar"></i> <?php echo date('d.m.Y', strtotime($draw['draw_date'])); ?></span>
                        <span><i class="fas fa-info-circle"></i> <?php echo htmlspecialchars($draw['description']); ?></span>
                    </div>
                </div>
                
                <div id="tickets-overlay-<?php echo $draw['id']; ?>" class="tickets-overlay"></div>
                <div id="tickets-container-<?php echo $draw['id']; ?>" class="tickets-container">
                    <h3>
                        <div><i class="fas fa-ticket-alt"></i> <?php echo htmlspecialchars($draw['name']); ?> - Biletler</div>
                        <div class="close-tickets" onclick="closeTickets(<?php echo $draw['id']; ?>)">&times;</div>
                    </h3>
                    
                    <input type="text" class="tickets-search" placeholder="Bilet numarası ara..." oninput="filterTickets(this.value, <?php echo $draw['id']; ?>)">
                    
                    <div class="tickets-status">
                        <div class="status-item available">
                            <span></span> Müsait
                        </div>
                        <div class="status-item sold">
                            <span></span> Satıldı
                        </div>
                    </div>

                    <div class="tickets-grid" id="tickets-grid-<?php echo $draw['id']; ?>">
                        <!-- Biletler AJAX ile yüklenecek -->
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Bilet Satın Alma Modal -->
    <div id="ticketModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeModal()">&times;</span>
            <h3>Bilet Satın Al</h3>
            <form id="ticketForm" onsubmit="purchaseTicket(event)">
                <input type="hidden" id="draw_id" name="draw_id">
                <input type="hidden" id="ticket_id" name="ticket_id">
                
                <div class="form-group">
                    <label for="name">Ad Soyad</label>
                    <input type="text" id="name" name="name" required placeholder="Adınız ve soyadınız">
                </div>
                
                <div class="form-group">
                    <label for="email">E-posta</label>
                    <input type="email" id="email" name="email" required placeholder="ornek@email.com">
                </div>
                
                <div class="form-group">
                    <label for="phone">Telefon</label>
                    <input type="tel" id="phone" name="phone" required placeholder="05XX XXX XX XX">
                </div>
                
                <button type="submit" class="select-btn">Satın Al</button>
            </form>
        </div>
    </div>

    <script>
    let currentTickets = {};

    function showTickets(drawId) {
        document.getElementById(`tickets-overlay-${drawId}`).style.display = 'block';
        document.querySelectorAll('.tickets-container').forEach(container => {
            container.style.display = 'none';
        });
        
        const container = document.getElementById(`tickets-container-${drawId}`);
        container.style.display = 'block';
        
        fetch(`get_tickets.php?draw_id=${drawId}`)
            .then(response => response.json())
            .then(data => {
                currentTickets[drawId] = data;
                displayTickets(data, drawId);
            })
            .catch(error => console.error('Hata:', error));
    }

    function displayTickets(tickets, drawId) {
        const grid = document.getElementById(`tickets-grid-${drawId}`);
        grid.innerHTML = '';
        
        tickets.forEach(ticket => {
            const div = document.createElement('div');
            div.className = `ticket ${ticket.status}`;
            div.innerHTML = ticket.ticket_number;
            
            if (ticket.status === 'available') {
                div.onclick = () => showPurchaseModal(drawId, ticket.id);
            }
            
            grid.appendChild(div);
        });
    }

    function filterTickets(search, drawId) {
        if (!currentTickets[drawId]?.length) return;
        
        const filtered = currentTickets[drawId].filter(ticket => 
            ticket.ticket_number.toLowerCase().includes(search.toLowerCase())
        );
        
        displayTickets(filtered, drawId);
    }

    function closeTickets(drawId) {
        document.getElementById(`tickets-overlay-${drawId}`).style.display = 'none';
        document.getElementById(`tickets-container-${drawId}`).style.display = 'none';
    }

    // Overlay'e tıklandığında kapat
    document.querySelectorAll('.tickets-overlay').forEach(overlay => {
        overlay.onclick = function(event) {
            if (event.target === this) {
                const drawId = this.id.split('-')[2];
                closeTickets(drawId);
            }
        };
    });

    function showPurchaseModal(drawId, ticketId) {
        document.getElementById('draw_id').value = drawId;
        document.getElementById('ticket_id').value = ticketId;
        document.getElementById('ticketModal').style.display = 'block';
    }

    function closeModal() {
        document.getElementById('ticketModal').style.display = 'none';
        document.getElementById('ticketForm').reset();
    }

    // Modal dışına tıklandığında kapat
    window.onclick = function(event) {
        const modal = document.getElementById('ticketModal');
        if (event.target == modal) {
            closeModal();
        }
    }

    function purchaseTicket(event) {
        event.preventDefault();
        
        const formData = new FormData(document.getElementById('ticketForm'));
        
        fetch('process_ticket.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                if (data.redirect) {
                    window.location.href = data.redirect;
                }
            } else {
                alert(data.message || 'Bir hata oluştu');
            }
        })
        .catch(error => {
            console.error('Hata:', error);
            alert('Bir hata oluştu');
        });
    }
    </script>
</body>
</html>