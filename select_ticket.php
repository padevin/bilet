<?php
include 'includes/db.php';
include 'includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $ticket_id = $_POST['ticket_id'];
    $name = $_POST['name'];
    $phone = $_POST['phone'];
    $iban = $_POST['iban'];

    if (selectTicket($conn, $ticket_id, $name, $phone, $iban)) {
        $payment_info = true;
        $_SESSION['ticket_id'] = $ticket_id;
        $_SESSION['payment_start_time'] = time();
    } else {
        $error = "Error selecting ticket.";
    }
} else {
    $ticket_id = $_GET['ticket_id'];
}

if (isset($_SESSION['payment_start_time']) && (time() - $_SESSION['payment_start_time']) > 600) {
    $ticket_id = $_SESSION['ticket_id'];
    $conn->query("UPDATE tickets SET is_selected=0, user_id=NULL WHERE id=$ticket_id");
    unset($_SESSION['ticket_id']);
    unset($_SESSION['payment_start_time']);
    $error = "Payment time expired. The ticket has been released.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Select Ticket</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
    </style>
    <script>
        function startTimer(duration, display) {
            var timer = duration, minutes, seconds;
            setInterval(function () {
                minutes = parseInt(timer / 60, 10);
                seconds = parseInt(timer % 60, 10);

                minutes = minutes < 10 ? "0" + minutes : minutes;
                seconds = seconds < 10 ? "0" + seconds : seconds;

                display.textContent = minutes + ":" + seconds;

                if (--timer < 0) {
                    timer = 0;
                    alert("Payment time expired. The ticket has been released.");
                    window.location.reload();
                }
            }, 1000);
        }

        window.onload = function () {
            var tenMinutes = 60 * 10,
                display = document.querySelector('#time');
            startTimer(tenMinutes, display);
        };
    </script>
</head>
<body>
    <div class="container">
        <h1 class="text-center">Select Ticket</h1>
        <?php if (isset($error)): ?>
            <p class="alert alert-danger text-center"><?= $error ?></p>
        <?php endif; ?>
        <?php if (isset($payment_info)): ?>
            <h2 class="text-center">Ödeme Bilgileri</h2>
            <p>NAME: Yusuf Arslan</p>
            <p>IBAN: TR6674278547257237</p>
            <?php
            $stmt = $conn->prepare("SELECT numbers FROM tickets WHERE id=?");
            $stmt->bind_param("i", $ticket_id);
            $stmt->execute();
            $stmt->bind_result($ticket_numbers);
            $stmt->fetch();
            $stmt->close();
            ?>
            <p>Bilet Numaraların: <?= $ticket_numbers ?></p>
            <p>Ödeme yapmak için <span id="time">10:00</span> dakikan mevcuttur.</p>
        <?php else: ?>
            <form method="POST" action="select_ticket.php" class="mt-4">
                <input type="hidden" name="ticket_id" value="<?= $ticket_id ?>">
                <div class="form-group">
                    <label for="name">Name:</label>
                    <input type="text" id="name" name="name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="phone">Phone:</label>
                    <input type="text" id="phone" name="phone" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="iban">IBAN:</label>
                    <input type="text" id="iban" name="iban" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-primary">Confirm</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>