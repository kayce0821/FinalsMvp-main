<?php
include '../INCLUDES/database.php';
session_start();
$error = "";

// If user is already logged in, redirect them to their dashboard
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'Admin') {
        header("Location: adminDashboard.php");
    } elseif ($_SESSION['role'] === 'Staff') {
        header("Location: staffDashboard.php");
    } elseif ($_SESSION['role'] === 'Student') {
        header("Location: studentDashboard.php");
    } elseif ($_SESSION['role'] === 'Super Admin') {
        header("Location: superAdminDashboard.php");
    } else {
        header("Location: guest.php"); // Fallback for Guests or any other roles
    }
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $mysql->prepare("SELECT * FROM user WHERE username = ? AND status = 1");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role'] = $user['role'];
        
        if ($user['role'] === 'Admin') {
            header("Location: adminDashboard.php");
        } elseif ($user['role'] === 'Staff') {
            header("Location: staffDashboard.php");
         } elseif ($_SESSION['role'] === 'Student') {
        header("Location: studentDashboard.php");
        }elseif ($_SESSION['role'] === 'Super Admin') {
        header("Location: superAdminDashboard.php");
    }  else {
            header("Location: guest.php"); 
        }
        exit();
    } else {
        $error = "Invalid username/password, or account has been archived!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>EquipTrack | Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            background-image: url('bg.jpg');
            background-size: cover;
            background-position: center;
        }
        /* Container for the button outside the card */
        .back-container {
            margin-bottom: 15px;
        }
        .btn-back {
            color: white;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            font-weight: 500;
            transition: all 0.3s ease;
            opacity: 0.8;
        }
        .btn-back:hover {
            opacity: 1;
            color: #d1e7dd; /* Light green tint on hover */
            transform: translateX(-5px); /* Subtle slide effect */
        }
        .login-card {
            border-radius: 8px;
        }
        .btn-primary {
            background-color: #3a5a40;
            border-color: #3a5a40;
        }
        .btn-primary:hover {
            background-color: #2b4330;
            border-color: #2b4330;
        }
    </style>
</head>
<body class="d-flex align-items-center min-vh-100">
    
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-4">

            <div class="back-container">
                    <a href="guest.php" class="btn-back">
                        <i class="bi bi-arrow-left-circle-fill me-2 fs-5"></i> 
                        Back to Home
                    </a>
                </div>

                <div class="card shadow-sm login-card">
                    <div class="text-center mt-4 pt-4">
                        <h5 class="card-title text-center mb-1 fw-bold">Account Login</h5>
                        <h2 class="fw-bold text-dark" style="letter-spacing: -1px;">
                            <i class="bi bi-shield-lock text-success me-2"></i><strong>EQUIP</strong>TRACK
                        </h2>
                        <p class="text-muted small mb-1">MIS Equipment Borrowing System</p>
                    </div>

                    <div class="card-body p-3">                        
                        <?php if($error): ?>
                            <div class="alert alert-danger text-center small py-2"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <div class="mb-2">
                                <label class="form-label text-muted small fw-bold">Username</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-person"></i></span>
                                    <input type="text" name="username" class="form-control" required placeholder="Enter username">
                                </div>
                            </div>
                            <div class="mb-4">
                                <label class="form-label text-muted small fw-bold">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-lock"></i></span>
                                    <input type="password" name="password" class="form-control" required placeholder="Enter password">
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary w-100 fw-bold py-2">Login</button>
                        </form>
                    </div>
                </div>
                
                <p class="text-center mt-4 text-light small">
                    Don't have an account? <a href="register.php" class="text-success fw-bold text-decoration-none">Register here</a>
                </p>
                
            </div>
        </div>
    </div>
</body>
</html>