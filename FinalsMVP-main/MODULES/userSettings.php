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
} else {
    $sidebar_file = '../INCLUDES/sidebarStudent.php';
}

$user_id = $_SESSION['user_id'];
$message = '';
$message_type = ''; 

// ==========================================
// 1. FETCH USER DATA FROM DB ON LOAD
// ==========================================
$stmt = $mysql->prepare("
    SELECT u.email, u.first_name, u.last_name, u.role, s.student_id 
    FROM user u 
    LEFT JOIN students s ON u.email = s.email 
    WHERE u.user_id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $db_email = $row['email'];
    $db_fname = $row['first_name'];
    $db_lname = $row['last_name'];
    $db_role  = $row['role'];
    $db_student_no = !empty($row['student_id']) ? $row['student_id'] : 'N/A';
} else {
    $db_email = "Unknown";
    $db_fname = "Unknown";
    $db_lname = "User";
    $db_role  = "User";
    $db_student_no = "N/A";
}
$stmt->close();

// ==========================================
// 2. FORM PROCESSING LOGIC
// ==========================================
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // --- PROFILE UPDATE LOGIC ---
    if (isset($_POST['update_profile'])) {
        $new_fname = trim($_POST['first_name']);
        $new_lname = trim($_POST['last_name']);
        
        if (!empty($new_fname) && !empty($new_lname)) {
            $mysql->begin_transaction();
            $success = true;

            // 1. Update the main user table
            $update_stmt = $mysql->prepare("UPDATE user SET first_name = ?, last_name = ? WHERE user_id = ?");
            $update_stmt->bind_param("ssi", $new_fname, $new_lname, $user_id);
            if (!$update_stmt->execute()) {
                $success = false;
            }
            $update_stmt->close();

            // 2. If the user is a student, ALSO update their name in the students table
            if ($success && $db_role === 'Student') {
                $update_student = $mysql->prepare("UPDATE students SET first_name = ?, last_name = ? WHERE email = ?");
                $update_student->bind_param("sss", $new_fname, $new_lname, $db_email);
                if (!$update_student->execute()) {
                    $success = false;
                }
                $update_student->close();
            }
            
            if ($success) {
                $mysql->commit();
                $message = "Your profile has been updated successfully.";
                $message_type = "success";
                
                // Update local variables to reflect instantly
                $db_fname = htmlspecialchars($new_fname); 
                $db_lname = htmlspecialchars($new_lname); 
                
                // Update the Session so the Sidebar changes instantly
                $_SESSION['full_name'] = trim($new_fname . ' ' . $new_lname);
            } else {
                $mysql->rollback();
                $message = "Error updating profile.";
                $message_type = "danger";
            }
        } else {
            $message = "First and Last name cannot be empty.";
            $message_type = "danger";
        }
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
                        
                        // Automatically scroll down to the security section to show the success message
                        echo "<script>document.addEventListener('DOMContentLoaded', function() { document.getElementById('security-section').scrollIntoView({ behavior: 'smooth' }); });</script>";
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

        .settings-container { max-width: 800px; margin: 0 auto; width: 100%; }

        .form-label { font-size: 0.75rem; font-weight: 600; color: #6c757d; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 0.5rem; }
        .form-control { background-color: #f8f9fa; border: 1px solid #ced4da; border-radius: 0.375rem; padding: 0.6rem 1rem; color: #495057; transition: border-color 0.3s, box-shadow 0.3s; }
        .form-control:focus { background-color: #fff; border-color: var(--brand-color); box-shadow: 0 0 0 0.2rem rgba(58, 90, 64, 0.25); }
        .form-control:disabled { background-color: #e9ecef; cursor: not-allowed; }
        
        .btn-equiptrack { background-color: var(--brand-color); color: white; border-radius: 50rem; padding: 0.6rem 2rem; font-weight: 500; border: none; transition: 0.3s; }
        .btn-equiptrack:hover { background-color: var(--brand-hover); color: white; transform: translateY(-1px); box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        
        .btn-cancel { border-radius: 50rem; padding: 0.6rem 1.5rem; font-weight: 500; transition: 0.3s; }

        /* Validation Styling */
        .input-invalid { border-color: #dc3545 !important; box-shadow: 0 0 8px rgba(220, 53, 69, 0.4) !important; }
        .input-valid { border-color: #198754 !important; box-shadow: 0 0 8px rgba(25, 135, 84, 0.4) !important; }
        .cursor-pointer { cursor: pointer; color: #6c757d; transition: color 0.2s; }
        .cursor-pointer:hover { color: var(--brand-color); }
        
        /* Custom Card Enhancements */
        .settings-card { border: none; border-radius: 1rem; box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
        .section-icon { background-color: rgba(58, 90, 64, 0.1); color: var(--brand-color); width: 45px; height: 45px; display: flex; align-items: center; justify-content: center; border-radius: 12px; margin-right: 15px; }
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
                                <i class="bi bi-gear-fill me-3 fs-3 text-secondary"></i> Account Settings
                            </h3>
                            <p class="text-muted">Manage your personal information and security preferences below.</p>
                        </div>
                    </div>

                    <?php if (!empty($message)): ?>
                        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show shadow-sm rounded-3" role="alert">
                            <?php echo $message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <div class="card settings-card mb-4" id="profile-section">
                        <div class="card-body p-4 p-md-5">
                            
                            <div class="d-flex align-items-center mb-4 pb-3 border-bottom">
                                <div class="section-icon"><i class="bi bi-person-vcard fs-4"></i></div>
                                <div>
                                    <h5 class="fw-bold mb-0 text-dark">Personal Information</h5>
                                    <p class="text-muted small mb-0">Update your basic profile details.</p>
                                </div>
                            </div>

                            <form method="POST" action="">
                                <div class="row mb-4">
                                    <div class="col-md-6 mb-3 mb-md-0">
                                        <label class="form-label">First Name</label>
                                        <input type="text" name="first_name" class="form-control" value="<?php echo htmlspecialchars($db_fname); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Last Name</label>
                                        <input type="text" name="last_name" class="form-control" value="<?php echo htmlspecialchars($db_lname); ?>" required>
                                    </div>
                                </div>

                                <div class="row mb-4">
                                    <div class="col-md-6 mb-3 mb-md-0">
                                        <label class="form-label">Email Address <span class="text-muted fw-normal text-lowercase">(Non-editable)</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-envelope"></i></span>
                                            <input type="text" class="form-control border-start-0" value="<?php echo htmlspecialchars($db_email); ?>" disabled>
                                        </div>
                                    </div>
                                    
                                    <?php if ($db_role === 'Student'): ?>
                                    <div class="col-md-6">
                                        <label class="form-label">Student Number <span class="text-muted fw-normal text-lowercase">(Non-editable)</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-hash"></i></span>
                                            <input type="text" class="form-control border-start-0" value="<?php echo htmlspecialchars($db_student_no); ?>" disabled>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </div>

                                <div class="text-end mt-4 pt-2 d-flex justify-content-end gap-2">
                                    <button type="reset" class="btn btn-light border btn-cancel">Cancel</button>
                                    <button type="submit" name="update_profile" class="btn btn-equiptrack">Save Profile</button>
                                </div>
                            </form>
                            
                        </div>
                    </div>

                    <div class="card settings-card mb-5" id="security-section">
                        <div class="card-body p-4 p-md-5">
                            
                            <div class="d-flex align-items-center mb-4 pb-3 border-bottom">
                                <div class="section-icon"><i class="bi bi-shield-lock fs-4"></i></div>
                                <div>
                                    <h5 class="fw-bold mb-0 text-dark">Security Settings</h5>
                                    <p class="text-muted small mb-0">Ensure your account is using a long, random password to stay secure.</p>
                                </div>
                            </div>

                            <form method="POST" action="" id="passwordForm">
                                <div class="row mb-4">
                                    <div class="col-md-12">
                                        <label class="form-label">Current Password</label>
                                        <div class="position-relative">
                                            <input type="password" name="current_password" id="current_password" class="form-control pe-5" placeholder="Enter current password" required>
                                            <i class="bi bi-eye-slash position-absolute top-50 end-0 translate-middle-y me-3 cursor-pointer toggle-pwd"></i>
                                        </div>
                                    </div>
                                </div>

                                <div class="row mb-4">
                                    <div class="col-md-6 mb-3 mb-md-0">
                                        <label class="form-label">New Password</label>
                                        <div class="position-relative">
                                            <input type="password" name="new_password" id="new_password" class="form-control pe-5" placeholder="New password" required>
                                            <i class="bi bi-eye-slash position-absolute top-50 end-0 translate-middle-y me-3 cursor-pointer toggle-pwd"></i>
                                        </div>
                                        <div class="form-text text-muted small mt-1">Min 8 chars, 1 uppercase, 1 lowercase, 1 number.</div>
                                        <div id="err_new_password" class="text-danger small mt-1 d-none fw-bold">Requirements not met.</div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label class="form-label">Confirm New Password</label>
                                        <div class="position-relative">
                                            <input type="password" name="confirm_password" id="confirm_password" class="form-control pe-5" placeholder="Confirm new password" required>
                                            <i class="bi bi-eye-slash position-absolute top-50 end-0 translate-middle-y me-3 cursor-pointer toggle-pwd"></i>
                                        </div>
                                        <div id="err_confirm_password" class="text-danger small mt-1 d-none fw-bold">Passwords do not match.</div>
                                    </div>
                                </div>

                                <div class="text-end mt-4 pt-2 d-flex justify-content-end gap-2">
                                    <button type="reset" class="btn btn-light border btn-cancel">Cancel</button>
                                    <button type="submit" name="change_password" class="btn btn-equiptrack">Update Password</button>
                                </div>
                            </form>
                            
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
                // Clear validation styling when user clicks "Cancel" (reset button)
                passwordForm.addEventListener("reset", function() {
                    newPassword.classList.remove("input-valid", "input-invalid");
                    confirmPassword.classList.remove("input-valid", "input-invalid");
                    document.getElementById("err_new_password").classList.add("d-none");
                    document.getElementById("err_confirm_password").classList.add("d-none");
                    
                    // Reset all eye icons to hidden state
                    document.querySelectorAll('.toggle-pwd').forEach(icon => {
                        icon.classList.remove('bi-eye');
                        icon.classList.add('bi-eye-slash');
                        icon.previousElementSibling.type = 'password';
                    });
                });

                // Validate before submitting
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