<?php
include '../INCLUDES/database.php';
session_start();

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'Staff' && $_SESSION['role'] !== 'Admin')) {
    header("Location: ../PAGES/login.php");
    exit();
}

// NEW: Get the ID of the staff member clicking approve!
$staff_id = $_SESSION['user_id'];

if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $request_id = intval($_GET['id']);

    if ($action === 'approve') {
        
        $mysql->begin_transaction();
        try {
            $req_stmt = $mysql->prepare("SELECT student_id, item_id, duration_hours FROM requests WHERE request_id = ? AND request_status = 'Pending'");
            $req_stmt->bind_param("i", $request_id);
            $req_stmt->execute();
            $request = $req_stmt->get_result()->fetch_assoc();
            $req_stmt->close();

            if ($request) {
                $student_id = $request['student_id'];
                $item_id = $request['item_id'];
                $duration = (int)$request['duration_hours'];

                $check_item = $mysql->prepare("SELECT status FROM items WHERE item_id = ?");
                $check_item->bind_param("i", $item_id);
                $check_item->execute();
                $item_status = $check_item->get_result()->fetch_assoc()['status'];
                $check_item->close();

                if ($item_status !== 'Available') {
                    throw new Exception("Item is no longer available.");
                }

                $expected_return_time = date('H:i:s', strtotime("+$duration hours"));

                // FIXED: Insert staff_id into issued_by
                $trans_stmt = $mysql->prepare("INSERT INTO transactions (student_id, item_id, expected_return_time, transaction_status, issued_by) VALUES (?, ?, ?, 'Active', ?)");
                $trans_stmt->bind_param("sisi", $student_id, $item_id, $expected_return_time, $staff_id);
                $trans_stmt->execute();

                $update_req = $mysql->prepare("UPDATE requests SET request_status = 'Approved' WHERE request_id = ?");
                $update_req->bind_param("i", $request_id);
                $update_req->execute();

                $update_item = $mysql->prepare("UPDATE items SET status = 'Borrowed' WHERE item_id = ?");
                $update_item->bind_param("i", $item_id);
                $update_item->execute();

                $mysql->commit();
                header("Location: ../MODULES/requests.php?status=approved");
                exit();
            } else {
                throw new Exception("Request not found or already processed.");
            }
        } catch (Exception $e) {
            $mysql->rollback();
            if ($e->getMessage() == "Item is no longer available.") {
                header("Location: ../MODULES/requests.php?status=unavailable");
            } else {
                header("Location: ../MODULES/requests.php?status=error");
            }
            exit();
        }
        
    } elseif ($action === 'reject') {
        
        $update_req = $mysql->prepare("UPDATE requests SET request_status = 'Rejected' WHERE request_id = ?");
        $update_req->bind_param("i", $request_id);
        if ($update_req->execute()) {
            header("Location: ../MODULES/requests.php?status=rejected");
        } else {
            header("Location: ../MODULES/requests.php?status=error");
        }
        $update_req->close();
        exit();
    }
}
header("Location: ../MODULES/requests.php");
exit();
?>