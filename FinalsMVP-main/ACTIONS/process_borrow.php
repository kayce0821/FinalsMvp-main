<?php
include '../INCLUDES/database.php';
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $student_id = trim($_POST['student_id']);
    $item_id = intval($_POST['item_id']);
    $expected_return = $_POST['expected_return_time']; 
    
    // NEW: Get the ID of the staff member processing this!
    $staff_id = $_SESSION['user_id'];

    $check_stmt = $mysql->prepare("SELECT status FROM items WHERE item_id = ?");
    $check_stmt->bind_param("i", $item_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    $item = $result->fetch_assoc();

    if ($item) {
        if ($item['status'] == 'Available') {
            
            $mysql->begin_transaction();
            try {
                // FIXED: Insert the staff_id into the issued_by column
                $insert_stmt = $mysql->prepare("INSERT INTO transactions (student_id, item_id, expected_return_time, transaction_status, issued_by) VALUES (?, ?, ?, 'Active', ?)");
                $insert_stmt->bind_param("sisi", $student_id, $item_id, $expected_return, $staff_id);
                $insert_stmt->execute();

                $update_stmt = $mysql->prepare("UPDATE items SET status = 'Borrowed' WHERE item_id = ?");
                $update_stmt->bind_param("i", $item_id);
                $update_stmt->execute();

                $mysql->commit();
                header("Location: ../PAGES/staffDashboard.php?status=success");
            } catch (Exception $e) {
                $mysql->rollback();
                header("Location: ../PAGES/staffDashboard.php?status=error");
            }
        } else {
            header("Location: ../PAGES/staffDashboard.php?status=unavailable");
        }
    } else {
        header("Location: ../PAGES/staffDashboard.php?status=error");
    }
    exit();
}
?>