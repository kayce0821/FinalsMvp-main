<?php
include '../INCLUDES/database.php';
session_start();

// Determine which dashboard to redirect to dynamically
$dashboard = ($_SESSION['role'] === 'Admin') ? 'adminDashboard.php' : 'staffDashboard.php';

if (isset($_GET['tid'])) {
    $transaction_id = $_GET['tid'];

    $get_stmt = $mysql->prepare("SELECT item_id FROM transactions WHERE transaction_id = ?");
    $get_stmt->bind_param("i", $transaction_id); 
    $get_stmt->execute();
    $result = $get_stmt->get_result();
    $row = $result->fetch_assoc();

    if ($row) {
        $item_id = $row['item_id'];

        $update_trans = $mysql->prepare("UPDATE transactions SET transaction_status = 'Completed' WHERE transaction_id = ?");
        $update_trans->bind_param("i", $transaction_id);
        $update_trans->execute();

        $update_item = $mysql->prepare("UPDATE items SET status = 'Available' WHERE item_id = ?");
        $update_item->bind_param("i", $item_id); 
        $update_item->execute();
    }
    
    // FIXED: Added ../PAGES/ so it routes correctly!
   header("Location: ../PAGES/" . $dashboard . "?status=returned");
   exit();
}
?>