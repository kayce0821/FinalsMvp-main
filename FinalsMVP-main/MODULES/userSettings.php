<?php
session_start();
// Include your database connection
include '../INCLUDES/database.php'; 

// Redirect to login if user isn't logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SESSION['role'] === 'Admin') {
    $sidebar_file = '../INCLUDES/sidebarAdmin.php';
} elseif ($_SESSION['role'] === 'Staff') {
    $sidebar_file = '../INCLUDES/sidebarStaff.php';
}elseif ($_SESSION['role'] === 'Super Admin') {
    $sidebar_file = '../INCLUDES/sidebarSuperAdmin.php';
} else {
    $sidebar_file = '../INCLUDES/sidebarStudent.php';
}

$user_id = $_SESSION['user_id'];
$message = '';
$message_type = ''; 
$active_tab = 'profile'; 

// ==========================================
// 1. FETCH USER DATA FROM DB ON LOAD
// ==========================================
$stmt = $mysql->prepare("SELECT username, full_name, role FROM user WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $db_username = $row['username'];
    $db_fullname = $row['full_name'];
    $db_role = $row['role'];
} else {
    $db_username = "Unknown";
    $db_fullname = "Unknown User";
    $db_role = "User";
}
$stmt->close();

// ==========================================
// 2. FORM PROCESSING LOGIC
// ==========================================
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // --- PROFILE UPDATE LOGIC ---
    if (isset($_POST['update_profile'])) {
        $new_fullname = trim($_POST['full_name']);
        
        if (!empty($new_fullname)) {
            $update_stmt = $mysql->prepare("UPDATE user SET full_name = ? WHERE user_id = ?");
            $update_stmt->bind_param("si", $new_fullname, $user_id);
            
            if ($update_stmt->execute()) {
                $message = "Your profile has been updated successfully.";
                $message_type = "success";
                $db_fullname = htmlspecialchars($new_fullname); 
            } else {
                $message = "Error updating profile.";
                $message_type = "danger";
            }
            $update_stmt->close();
        } else {
            $message = "Full name cannot be empty.";
            $message_type = "danger";
        }
        $active_tab = 'profile';
    } 
    
    // --- ACTUAL PASSWORD CHANGE LOGIC ---
    elseif (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $message = "Please fill in all password fields.";
            $message_type = "danger";
        } elseif ($new_password !== $confirm_password) {
            $message = "New passwords do not match. Please try again.";
            $message_type = "danger";
        // Server side regex validation matching the JS
        } elseif (!preg_match('/^(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}$/', $new_password)) {
            $message = "Password must be at least 8 characters, with 1 uppercase, 1 lowercase, and 1 number.";
            $message_type = "danger";
        } else {
            $stmt = $mysql->prepare("SELECT password FROM user WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($row = $result->fetch_assoc()) {
                $db_hashed_password = $row['password'];
                
                if (password_verify($current_password, $db_hashed_password)) {
                    
                    $new_hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    
                    $update_stmt = $mysql->prepare("UPDATE user SET password = ? WHERE user_id = ?");
                    $update_stmt->bind_param("si", $new_hashed_password, $user_id);
                    
                    if ($update_stmt->execute()) {
                        $message = "Password successfully changed!";
                        $message_type = "success";
                    } else {
                        $message = "Database error. Could not update password.";
                        $message_type = "danger";
                    }
                    $update_stmt->close();
                    
                } else {
                    $message = "Incorrect current password. Please try again.";
                    $message_type = "danger";
                }
            } else {
                $message = "User account not found.";
                $message_type = "danger";
            }
            $stmt->close();
        }
        $active_tab = 'password'; 
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Settings | EquipTrack</title>
    
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,600,700&display=fallback">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        :root {
            --brand-color: #3a5a40;
            --brand-hover: #2c4430;
            --bg-body: #f4f6f9;
        }

        body {
            background-color: var(--bg-body);
            margin: 0;
            overflow: hidden;
            font-family: 'Source Sans Pro', sans-serif;
            color: #333;
        }
        
        .wrapper { display: flex; width: 100%; height: 100vh; position: relative; overflow: hidden; }
        .content-wrapper { 
            flex-grow: 1; display: flex; flex-direction: column; 
            width: calc(100% - 250px); height: 100vh; overflow-y: auto; overflow-x: hidden; transition: width 0.3s ease;
        }
        .content-wrapper.expanded { width: calc(100% - 70px); }
        .main-header { background-color: var(--brand-color); padding: 12px 20px; }

        @media (max-width: 768px) { .content-wrapper, .content-wrapper.expanded { width: 100%; } }

        .settings-container { max-width: 1000px; margin: 0 auto; }
        .settings-card-tabs { background: #fff; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.03); border: none; padding: 20px 15px; }

        .nav-pills .nav-link { display: flex; align-items: center; color: #555; border-radius: 8px; padding: 12px 20px; margin-bottom: 5px; font-weight: 500; font-size: 0.95rem; transition: all 0.2s ease; }
        .nav-pills .nav-link:hover { background-color: #f8f9fa; color: #333; }
        .nav-pills .nav-link.active { background-color: var(--brand-color) !important; color: white !important; }
        .nav-pills .nav-link i { margin-right: 12px; font-size: 1.1rem; }

        .form-label { font-size: 0.75rem; font-weight: 600; color: #6c757d; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 0.5rem; }
        .form-control { background-color: #fff; border: 1px solid #ced4da; border-radius: 0.375rem; padding: 0.6rem 1rem; color: #495057; transition: border-color 0.3s, box-shadow 0.3s; }
        .form-control:focus { border-color: var(--brand-color); box-shadow: 0 0 0 0.2rem rgba(58, 90, 64, 0.25); }
        .form-control:disabled { background-color: #e9ecef; }
        
        .btn-equiptrack { background-color: var(--brand-color); color: white; border-radius: 50rem; padding: 0.6rem 1.5rem; font-weight: 500; border: none; transition: 0.3s; }
        .btn-equiptrack:hover { background-color: var(--brand-hover); color: white; }

        /* Validation Styling (Copied from Register) */
        .input-invalid { border-color: #dc3545 !important; box-shadow: 0 0 8px rgba(220, 53, 69, 0.4) !important; }
        .input-valid { border-color: #198754 !important; box-shadow: 0 0 8px rgba(25, 135, 84, 0.4) !important; }
        .cursor-pointer { cursor: pointer; color: #6c757d; transition: color 0.2s; }
        .cursor-pointer:hover { color: var(--brand-color); }
    </style>
</head>
<body>

    <div class="wrapper">
         <?php include $sidebar_file; ?>

        <div class="content-wrapper" id="mainContent">
            
            <nav class="main-header navbar navbar-expand navbar-dark border-bottom-0 shadow-sm w-100 m-0">
                <div class="container-fluid">
                    <ul class="navbar-nav align-items-center">
                        <li class="nav-item">
                            <a class="nav-link" href="#" id="sidebarToggle" role="button"><i class="fas fa-bars"></i></a>
                        </li>
                        <li class="nav-item d-none d-sm-inline-block ms-2">
                            <span class="nav-link font-weight-bold text-light p-0 text-decoration-none" style="font-size: 1.1rem; letter-spacing: 0.5px;">
                                Account Settings
                            </span>
                        </li>
                    </ul>
                </div>
            </nav>

            <div class="container-fluid p-4">
                <div class="settings-container">
                    
                    <div class="row mb-4">
                        <div class="col-12">
                            <h3 class="fw-bold text-dark d-flex align-items-center">
                                <i class="bi bi-gear me-3 fs-2 text-secondary"></i> Settings
                            </h3>
                            <p class="text-muted ms-1">Manage your profile information and security preferences.</p>
                        </div>
                    </div>

                    <?php if (!empty($message)): ?>
                        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show shadow-sm" role="alert">
                            <?php echo $message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-md-4 col-lg-3 mb-4">
                            <div class="settings-card-tabs">
                                <div class="nav flex-column nav-pills" id="v-pills-tab" role="tablist" aria-orientation="vertical">
                                    <button class="nav-link text-start <?php echo ($active_tab == 'profile') ? 'active' : ''; ?>" id="v-pills-profile-tab" data-bs-toggle="pill" data-bs-target="#v-pills-profile" type="button" role="tab">
                                        <i class="bi bi-person-vcard"></i> View Profile
                                    </button>
                                    <button class="nav-link text-start <?php echo ($active_tab == 'password') ? 'active' : ''; ?>" id="v-pills-password-tab" data-bs-toggle="pill" data-bs-target="#v-pills-password" type="button" role="tab">
                                        <i class="bi bi-shield-check"></i> Security
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-8 col-lg-9 ps-lg-4">
                            <div class="tab-content" id="v-pills-tabContent">
                                
                                <div class="tab-pane fade <?php echo ($active_tab == 'profile') ? 'show active' : ''; ?>" id="v-pills-profile" role="tabpanel">
                                    <form method="POST" action="">
                                        <div class="row mb-3">
                                            <div class="col-md-8 mb-3">
                                                <label class="form-label">FULL NAME</label>
                                                <input type="text" name="full_name" class="form-control" value="<?php echo htmlspecialchars($db_fullname); ?>" required>
                                            </div>
                                        </div>

                                        <div class="row mb-3">
                                            <div class="col-md-8">
                                                <label class="form-label">USERNAME <span class="text-muted text-lowercase fw-normal">(Cannot be changed)</span></label>
                                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($db_username); ?>" disabled>
                                            </div>
                                        </div>
                                        
                                        <div class="row mb-4">
                                            <div class="col-md-8">
                                                <label class="form-label">ROLE</label>
                                                <input type="text" class="form-control fw-bold text-success" value="<?php echo htmlspecialchars($db_role); ?>" disabled>
                                            </div>
                                        </div>

                                        <button type="submit" name="update_profile" class="btn btn-equiptrack">Save Changes</button>
                                    </form>
                                </div>

                                <div class="tab-pane fade <?php echo ($active_tab == 'password') ? 'show active' : ''; ?>" id="v-pills-password" role="tabpanel">
                                    <form method="POST" action="" id="passwordForm">
                                        
                                        <div class="row mb-3">
                                            <div class="col-md-8">
                                                <label class="form-label">CURRENT PASSWORD</label>
                                                <div class="position-relative">
                                                    <input type="password" name="current_password" id="current_password" class="form-control pe-5" required>
                                                    <i class="bi bi-eye-slash position-absolute top-50 end-0 translate-middle-y me-3 cursor-pointer toggle-pwd"></i>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row mb-3 mt-4">
                                            <div class="col-md-8">
                                                <label class="form-label">NEW PASSWORD</label>
                                                <div class="position-relative">
                                                    <input type="password" name="new_password" id="new_password" class="form-control pe-5" required>
                                                    <i class="bi bi-eye-slash position-absolute top-50 end-0 translate-middle-y me-3 cursor-pointer toggle-pwd"></i>
                                                </div>
                                                <div class="form-text text-muted small mt-1">Min 8 chars, 1 uppercase, 1 lowercase, 1 number.</div>
                                                <div id="err_new_password" class="text-danger small mt-1 d-none fw-bold">Please follow the requirements above.</div>
                                            </div>
                                        </div>

                                        <div class="row mb-4">
                                            <div class="col-md-8">
                                                <label class="form-label">CONFIRM NEW PASSWORD</label>
                                                <div class="position-relative">
                                                    <input type="password" name="confirm_password" id="confirm_password" class="form-control pe-5" required>
                                                    <i class="bi bi-eye-slash position-absolute top-50 end-0 translate-middle-y me-3 cursor-pointer toggle-pwd"></i>
                                                </div>
                                                <div id="err_confirm_password" class="text-danger small mt-1 d-none fw-bold">Passwords do not match.</div>
                                            </div>
                                        </div>

                                        <button type="submit" name="change_password" class="btn btn-equiptrack">Update Password</button>
                                    </form>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
            </div> 
            
            <?php include '../INCLUDES/footer.php'; ?>
        </div> 
    </div> 

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Sidebar logic
            const toggleBtn = document.getElementById('sidebarToggle');
            if (toggleBtn) {
                toggleBtn.addEventListener('click', function (e) {
                    e.preventDefault();
                    document.getElementById('mainContent').classList.toggle('expanded');
                });
            }

            // --- PASSWORD VALIDATION LOGIC ---
            const passwordForm = document.getElementById('passwordForm');
            const newPassword = document.getElementById('new_password');
            const confirmPassword = document.getElementById('confirm_password');
            const passRegex = /^(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}$/;

            // Toggle Password Visibility
            document.querySelectorAll('.toggle-pwd').forEach(item => {
                item.addEventListener('click', function() {
                    const input = this.previousElementSibling;
                    if (input.type === "password") {
                        input.type = "text";
                        this.classList.replace("bi-eye-slash", "bi-eye");
                    } else {
                        input.type = "password";
                        this.classList.replace("bi-eye", "bi-eye-slash");
                    }
                });
            });

            function setValidationState(input, isValid, errorTextId) {
                if (isValid) {
                    input.classList.remove("input-invalid");
                    input.classList.add("input-valid");
                    document.getElementById(errorTextId).classList.add("d-none");
                } else {
                    input.classList.remove("input-valid");
                    input.classList.add("input-invalid");
                    document.getElementById(errorTextId).classList.remove("d-none");
                }
            }

            if(newPassword) {
                newPassword.addEventListener("input", function() {
                    setValidationState(this, passRegex.test(this.value), "err_new_password");
                    if (confirmPassword.value.length > 0) validateConfirmPassword(); 
                });

                confirmPassword.addEventListener("input", validateConfirmPassword);
            }

            function validateConfirmPassword() {
                const isValid = confirmPassword.value === newPassword.value && newPassword.value.length > 0;
                setValidationState(confirmPassword, isValid, "err_confirm_password");
            }

            if(passwordForm) {
                passwordForm.addEventListener("submit", function(e) {
                    const isPassValid = passRegex.test(newPassword.value);
                    const isConfValid = confirmPassword.value === newPassword.value && newPassword.value.length > 0;

                    if (!isPassValid || !isConfValid) {
                        e.preventDefault(); 
                        setValidationState(newPassword, isPassValid, "err_new_password");
                        setValidationState(confirmPassword, isConfValid, "err_confirm_password");
                    }
                });
            }
        });
    </script>
</body>
</html>