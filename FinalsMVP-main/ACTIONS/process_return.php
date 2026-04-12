<?php
include '../INCLUDES/database.php';
session_start();

// Security check
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'Staff' && $_SESSION['role'] !== 'Admin')) {
    header("Location: ../PAGES/login.php");
    exit();
}

// Get the ID of the staff member accepting the return!
$staff_id = $_SESSION['user_id'];

if (isset($_GET['tid']) && isset($_GET['status'])) {
    $transaction_id = intval($_GET['tid']);
    $return_status = $_GET['status']; // Will be 'Returned', 'Defective', or 'Lost'

    $mysql->begin_transaction();
    try {
        $stmt = $mysql->prepare("SELECT item_id FROM transactions WHERE transaction_id = ? AND transaction_status = 'Active'");
        $stmt->bind_param("i", $transaction_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            $item_id = $row['item_id'];

            // FIXED: Save the specific return_status ('Returned', 'Defective', 'Lost') into the database
            $update_trans = $mysql->prepare("UPDATE transactions SET transaction_status = 'Completed', return_condition = ?, received_by = ? WHERE transaction_id = ?");
            $update_trans->bind_param("sii", $return_status, $staff_id, $transaction_id);
            $update_trans->execute();

            $new_item_status = 'Available'; 
            $redirect_status = 'returned';  

            if ($return_status === 'Defective') {
                $new_item_status = 'Defective';
                $redirect_status = 'defective';
            } elseif ($return_status === 'Lost') {
                $new_item_status = 'Lost';
                $redirect_status = 'lost';
            }

            $update_item = $mysql->prepare("UPDATE items SET status = ? WHERE item_id = ?");
            $update_item->bind_param("si", $new_item_status, $item_id);
            $update_item->execute();

            $mysql->commit();
            header("Location: ../PAGES/staffDashboard.php?status=$redirect_status");
        } else {
            throw new Exception("Transaction not found or already completed.");
        }
    } catch (Exception $e) {
        $mysql->rollback();
        header("Location: ../PAGES/staffDashboard.php?status=error");
    }
} else {
    header("Location: ../PAGES/staffDashboard.php");
}
exit();
?>