<?php
include '../includes/db.php';
include '../includes/functions.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['confirm_payment'])) {
    $ticket_id = $_POST['ticket_id'];
    if (confirmPayment($conn, $ticket_id)) {
        echo "Payment confirmed.";
    } else {
        echo "Error confirming payment.";
    }
}

$tickets = $conn->query("SELECT * FROM tickets WHERE is_selected=1 AND is_paid=0");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Panel</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
    <div class="container">
        <h1 class="mt-5">Admin Panel</h1>
        <h2 class="mt-4">Pending Payments</h2>
        <?php if (isset($message)): ?>
            <div class="alert alert-info"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <ul class="list-group mt-3">
            <?php while($row = $tickets->fetch_assoc()): ?>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    Ticket ID: <?= htmlspecialchars($row["id"]) ?> - Numbers: <?= htmlspecialchars($row["numbers"]) ?> 
                    <form method="POST" action="index.php" class="form-inline">
                        <input type="hidden" name="ticket_id" value="<?= htmlspecialchars($row["id"]) ?>">
                        <button type="submit" name="confirm_payment" class="btn btn-primary btn-sm">Confirm Payment</button>
                    </form>
                </li>
            <?php endwhile; ?>
        </ul>
        <a href="logout.php" class="btn btn-danger mt-4">Logout</a>
    </div>
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>