<?php
include 'includes/db.php';
include 'includes/functions.php';

generateTickets($conn);
echo "Tickets generated successfully.";
?>