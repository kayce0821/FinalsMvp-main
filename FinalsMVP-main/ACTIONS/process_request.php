<?php
include '../INCLUDES/database.php';
session_start();

// Security check: Only Admin and Staff can process requests
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'Staff' && $_SESSION['role'] !== 'Admin')) {
    header("Location: ../PAGES/login.php");
    exit();
}

if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $request_id = intval($_GET['id']);

    // 1. Fetch the specific request details
    $req_stmt = $mysql->prepare("SELECT student_id, item_id, request_status FROM requests WHERE request_id = ?");
    $req_stmt->bind_param("i", $request_id);
    $req_stmt->execute();
    $request = $req_stmt->get_result()->fetch_assoc();

    // Ensure the request exists and is still pending
    if ($request && $request['request_status'] === 'Pending') {
        $student_id = $request['student_id'];
        $item_id = $request['item_id'];

        if ($action === 'approve') {
            // Check if the item is still actually available
            $item_stmt = $mysql->prepare("SELECT status FROM items WHERE item_id = ?");
            $item_stmt->bind_param("s", $item_id);
            $item_stmt->execute();
            $item = $item_stmt->get_result()->fetch_assoc();

            if ($item && $item['status'] === 'Available') {
                try {
                    $mysql->begin_transaction();

                    // Update Request Status
                    $upd_req = $mysql->prepare("UPDATE requests SET request_status = 'Approved' WHERE request_id = ?");
                    $upd_req->bind_param("i", $request_id);
                    $upd_req->execute();

                    // Update Item Status to Borrowed
                    $upd_item = $mysql->prepare("UPDATE items SET status = 'Borrowed' WHERE item_id = ?");
                    $upd_item->bind_param("s", $item_id);
                    $upd_item->execute();

                    // Create Active Transaction
                    $ins_trans = $mysql->prepare("INSERT INTO transactions (student_id, item_id, transaction_status) VALUES (?, ?, 'Active')");
                    $ins_trans->bind_param("ss", $student_id, $item_id);
                    $ins_trans->execute();

                    $mysql->commit();
                    header("Location: ../MODULES/requests.php?status=approved");
                    exit();

                } catch (Exception $e) {
                    $mysql->rollback();
                    header("Location: ../MODULES/requests.php?status=error");
                    exit();
                }
            } else {
                // Item is already borrowed, defective, or lost
                header("Location: ../MODULES/requests.php?status=unavailable");
                exit();
            }

        } elseif ($action === 'reject') {
            // Simply update the request status to Rejected
            $rej_stmt = $mysql->prepare("UPDATE requests SET request_status = 'Rejected' WHERE request_id = ?");
            $rej_stmt->bind_param("i", $request_id);
            if ($rej_stmt->execute()) {
                header("Location: ../MODULES/requests.php?status=rejected");
            } else {
                header("Location: ../MODULES/requests.php?status=error");
            }
            exit();
        }
    }
}

// Fallback redirect if something goes wrong
header("Location: ../MODULES/requests.php");
exit();
?>