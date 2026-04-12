<?php
session_start();
include '../INCLUDES/database.php';

// Check if a token is in the URL
if (isset($_GET['token'])) {
    $token = $_GET['token'];

    // FIXED: Select first_name and last_name instead of full_name
    $stmt = $mysql->prepare("SELECT user_id, first_name, last_name, role FROM user WHERE verification_token = ? AND status = 'Pending' LIMIT 1");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $user_id = $user['user_id'];
        
        // FIXED: Concatenate first and last name for the session variable
        $full_name = trim($user['first_name'] . ' ' . $user['last_name']);
        $role = $user['role'];

        // 1. Activate the account and erase the one-time token
        $update = $mysql->prepare("UPDATE user SET status = 'Active', verification_token = NULL WHERE user_id = ?");
        $update->bind_param("i", $user_id);
        $update->execute();

        // 2. Automatically log the student in!
        $_SESSION['user_id'] = $user_id;
        $_SESSION['full_name'] = $full_name;
        $_SESSION['role'] = $role;

        // 3. Show a nice success message and redirect to PAGES folder
        echo "<!DOCTYPE html><html><head>";
        echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";
        echo "<link href='https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback' rel='stylesheet'>";
        echo "<style>body { background-color: #f8f9fa; font-family: 'Source Sans Pro', sans-serif; }</style>";
        echo "</head><body><script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    title: 'Email Verified!',
                    text: 'Your account is now active. Welcome to EquipTrack!',
                    icon: 'success',
                    confirmButtonColor: '#198754',
                    confirmButtonText: 'Go to Dashboard'
                }).then(() => {
                    window.location.href = '../PAGES/studentDashboard.php'; // <--- FIXED PATH
                });
            });
        </script></body></html>";
    } else {
        // Token is invalid, expired, or already used
        echo "<!DOCTYPE html><html><head>";
        echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";
        echo "</head><body style='background-color: #f8f9fa;'><script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    title: 'Link Expired',
                    text: 'This verification link is invalid or has already been used.',
                    icon: 'error',
                    confirmButtonColor: '#dc3545',
                    confirmButtonText: 'Go to Login'
                }).then(() => {
                    window.location.href = '../PAGES/login.php'; // <--- FIXED PATH
                });
            });
        </script></body></html>";
    }
} else {
    // No token provided, send them away
    header("Location: ../PAGES/login.php"); // <--- FIXED PATH
    exit();
}
?>