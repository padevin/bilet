<?php
include 'includes/db.php';
include 'includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['confirm_payment'])) {
    $ticket_id = $_POST['ticket_id'];
    if (confirmPayment($conn, $ticket_id)) {
        echo "Payment confirmed.";
    } else {
        echo "Error confirming payment.";
    }
}
?>