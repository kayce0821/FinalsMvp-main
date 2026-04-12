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
        header("Location: guest.php");
    }
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']); 
    $password = $_POST['password'];

    $stmt = $mysql->prepare("SELECT * FROM user WHERE email = ? AND (status = 'Active' OR status = 1)");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if ($user && password_verify($password, $user['password'])) {
        if (!empty($user['timeout_until']) && strtotime($user['timeout_until']) > time()) {
            $formatted_date = date('F j, Y', strtotime($user['timeout_until']));
            $error = "Account suspended until " . $formatted_date . " due to a penalty.";
        } else {
            $_SESSION['user_id'] = $user['user_id'];
            
            if (!empty($user['first_name']) || !empty($user['last_name'])) {
                $_SESSION['full_name'] = trim($user['first_name'] . ' ' . $user['last_name']);
            } else {
                $_SESSION['full_name'] = $user['full_name'] ?? 'User';
            }
            
            $_SESSION['role'] = $user['role'];
            
            if ($user['role'] === 'Admin') {
                header("Location: adminDashboard.php");
            } elseif ($user['role'] === 'Staff') {
                header("Location: staffDashboard.php");
            } elseif ($_SESSION['role'] === 'Student') {
                header("Location: studentDashboard.php");
            } else {
                header("Location: guest.php"); 
            }
            exit();
        }
    } else {
        $error = "Invalid email/password, or account has been archived!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EquipTrack | Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            background-image: url('bg.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
        }
        /* Overlay to ensure readability on mobile */
        body::before {
            content: "";
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0, 0, 0, 0.3);
            z-index: -1;
        }
        .btn-back {
            color: white;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            font-weight: 500;
            transition: all 0.3s ease;
            opacity: 0.9;
        }
        .btn-back:hover {
            color: #d1e7dd; 
            transform: translateX(-5px); 
        }
        .login-card {
            border-radius: 12px;
            border: none;
        }
        .btn-primary {
            background-color: #3a5a40;
            border-color: #3a5a40;
        }
        .btn-primary:hover {
            background-color: #2b4330;
        }
        /* Mobile specific adjustments */
        @media (max-width: 576px) {
            .card-title { font-size: 1.1rem; }
            .login-card { margin-top: 20px; }
        }
    </style>
</head>
<body class="d-flex align-items-center min-vh-100 py-4">
    
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-11 col-sm-8 col-md-6 col-lg-4">

                <div class="mb-3">
                    <a href="guest.php" class="btn-back">
                        <i class="bi bi-arrow-left-circle-fill me-2 fs-5"></i> 
                        Back to Home
                    </a>
                </div>

                <div class="card shadow-lg login-card">
                    <div class="text-center mt-4 px-3">
                        <h5 class="card-title mb-1 fw-bold text-uppercase" style="font-size: 0.9rem; color: #666;">Account Login</h5>
                        <h2 class="fw-bold text-dark" style="letter-spacing: -1px;">
                            <i class="bi bi-shield-lock text-success me-1"></i><strong>EQUIP</strong>TRACK
                        </h2>
                        <p class="text-muted small">MIS Equipment Borrowing System</p>
                    </div>

                    <div class="card-body p-4 pt-2">                        
                        <?php if($error): ?>
                            <div class="alert alert-danger text-center small py-2 fw-bold"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label text-muted small fw-bold">Email Address</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-envelope"></i></span>
                                    <input type="email" name="email" class="form-control border-start-0" required placeholder="Enter email address">
                                </div>
                            </div>
                            <div class="mb-4">
                                <label class="form-label text-muted small fw-bold">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-lock"></i></span>
                                    <input type="password" name="password" id="password" class="form-control border-start-0" required placeholder="Enter password">
                                    <span class="input-group-text bg-light" id="togglePassword" style="cursor: pointer;">
                                        <i class="bi bi-eye-slash"></i>
                                    </span>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary w-100 fw-bold py-2 shadow-sm">Login</button>
                        </form>
                    </div>
                </div>
                
                <p class="text-center mt-4 text-light small">
                    Don't have an account? <a href="register.php" class="text-white fw-bold text-decoration-underline">Register here</a>
                </p>
                
            </div>
        </div>
    </div>

    <script>
        document.getElementById('togglePassword').addEventListener('click', function (e) {
            const passwordInput = document.getElementById('password');
            const icon = this.querySelector('i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.replace('bi-eye-slash', 'bi-eye');
            } else {
                passwordInput.type = 'password';
                icon.classList.replace('bi-eye', 'bi-eye-slash');
            }
        });
    </script>
</body>
</html>