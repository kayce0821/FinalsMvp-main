<?php
include '../INCLUDES/database.php';

// Include PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require '../vendor/autoload.php';

$message = "";
$email_duplicate_err = ""; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $role = 'Student'; // Locked to Student
    $password_raw = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Capture the student-specific fields directly
    $student_no = trim($_POST['student_no']);
    $email = trim($_POST['email']);
    $course_section = trim($_POST['course_section']);

    // Server-side validation
    $isValid = true;
    $errorMsg = "";

    // 1. Basic Text & Format Validation
    if (!preg_match('/^[a-zA-Z\s\-\.]+$/', $first_name) || strlen($first_name) < 2) {
        $isValid = false;
        $errorMsg = "First name must be letters only and at least 2 characters.";
    } elseif (!preg_match('/^[a-zA-Z\s\-\.]+$/', $last_name) || strlen($last_name) < 2) {
        $isValid = false;
        $errorMsg = "Last name must be letters only and at least 2 characters.";
    } elseif (!preg_match('/^(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}$/', $password_raw)) {
        $isValid = false;
        $errorMsg = "Password must be at least 8 characters, with 1 uppercase, 1 lowercase, and 1 number.";
    } elseif ($password_raw !== $confirm_password) {
        $isValid = false;
        $errorMsg = "Passwords do not match.";
    } elseif (empty($student_no)) {
        $isValid = false;
        $errorMsg = "Student number is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $isValid = false;
        $errorMsg = "A valid email address is required.";
    } elseif (empty($course_section)) {
        $isValid = false;
        $errorMsg = "Course & Section is required.";
    }

    // 2. Database Duplicate Checks
    if ($isValid) {
        // --- FIXED: Duplicate Email Check (Looking at 'email' column instead of 'username') ---
        $stmt_check_user = $mysql->prepare("SELECT email FROM user WHERE email = ?");
        $stmt_check_user->bind_param("s", $email);
        $stmt_check_user->execute();
        if ($stmt_check_user->get_result()->num_rows > 0) {
            $isValid = false;
            $email_duplicate_err = "This email is already registered."; 
            $clear_email = true;
        }
        $stmt_check_user->close();

        // --- Duplicate Student No Check ---
        if ($isValid) {
            $stmt_check_student = $mysql->prepare("SELECT student_id FROM students WHERE student_id = ?");
            $stmt_check_student->bind_param("s", $student_no);
            $stmt_check_student->execute();
            if ($stmt_check_student->get_result()->num_rows > 0) {
                $isValid = false;
                $errorMsg = "This Student No. is already registered."; 
            }
            $stmt_check_student->close();
        }
    }

    // 3. Strict Database Insertion & Email Sending
    if ($isValid) {
        $password = password_hash($password_raw, PASSWORD_DEFAULT);
        $token = bin2hex(random_bytes(32)); 
        
        $mysql->begin_transaction();
        
        try {
            // FIXED: Insert into USER table (Using 'email' column instead of 'username')
            $sql_user = "INSERT INTO user (email, password, first_name, last_name, role, status, verification_token) VALUES (?, ?, ?, ?, ?, 'Pending', ?)";
            $stmt_user = $mysql->prepare($sql_user);
            $stmt_user->bind_param("ssssss", $email, $password, $first_name, $last_name, $role, $token);
            
            if (!$stmt_user->execute()) {
                throw new Exception("User table insertion failed.");
            }
            $stmt_user->close();

            // Insert into STUDENTS table
            $sql_student = "INSERT INTO students (student_id, first_name, last_name, course_section, email) VALUES (?, ?, ?, ?, ?)";
            $stmt_student = $mysql->prepare($sql_student);
            $stmt_student->bind_param("sssss", $student_no, $first_name, $last_name, $course_section, $email);
            
            if (!$stmt_student->execute()) {
                throw new Exception("Student table insertion failed.");
            }
            $stmt_student->close();

            // Send Verification Email
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'bachelorofsis@gmail.com'; 
            $mail->Password   = 'eifk cvdc chwn tnbb'; 
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            $mail->setFrom('bachelorofsis@gmail.com', 'EquipTrack MIS');
            $mail->addAddress($email);
            $mail->isHTML(true);
            $mail->Subject = 'Verify your EquipTrack Student Account';
            
            $verify_link = "http://localhost/Design-update-main/FinalsMVP-main/MODULES/verify.php?token=" . $token;

            $mail->Body = "
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset='UTF-8'>
                <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            </head>
            <body style=\"font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f7f6; margin: 0; padding: 20px 0;\">
                
                <table width='100%' cellpadding='0' cellspacing='0' border='0' style='background-color: #f4f7f6;'>
                    <tr>
                        <td align='center'>
                            
                            <table width='100%' max-width='600' cellpadding='0' cellspacing='0' border='0' style='max-width: 600px; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 10px 25px rgba(0,0,0,0.05); margin: 20px auto;'>
                                
                                <tr>
                                    <td align='center' style='background-color: #3a5a40; padding: 35px 20px;'>
                                        <h1 style='color: #ffffff; margin: 0; font-size: 26px; letter-spacing: 1px; font-weight: 800;'>
                                            <span style='color: #38ef7d;'>EQUIP</span>TRACK
                                        </h1>
                                        <p style='color: #d1e7dd; margin: 5px 0 0 0; font-size: 14px;'>MIS Equipment Borrowing System</p>
                                    </td>
                                </tr>
                                
                                <tr>
                                    <td style='padding: 40px 30px; color: #444444; line-height: 1.6; font-size: 16px;'>
                                        <h2 style='color: #2c4430; margin-top: 0; font-size: 22px;'>Welcome to EquipTrack, " . htmlspecialchars($first_name) . "!</h2>
                                        <p>Thank you for registering for an account. Your registration has been received for the <strong>EquipTrack MIS facility located at UCC Congress North</strong>.</p>
                                        <p>You are just one step away from being able to browse and borrow available equipment for your academic needs. To secure your account and activate your borrowing privileges, please confirm your email address by clicking the button below:</p>
                                        
                                        <div style='text-align: center; margin: 40px 0;'>
                                            <a href='" . $verify_link . "' style='background-color: #198754; color: #ffffff; padding: 14px 35px; text-decoration: none; border-radius: 50px; font-weight: bold; font-size: 16px; display: inline-block; letter-spacing: 0.5px;'>Verify My Account</a>
                                        </div>
                                        
                                        <p>Once verified, you can visit the MIS desk during office hours to pick up your requested items. We look forward to supporting your projects!</p>

                                    </td>
                                </tr>
                                
                                <tr>
                                    <td align='center' style='background-color: #f8f9fa; padding: 25px 20px; font-size: 12px; color: #888888; border-top: 1px solid #eaedf1;'>
                                        <p style='margin: 0 0 10px 0; font-weight: bold; color: #444444;'>
                                            <i class='bi bi-geo-alt-fill'></i> MIS Office - UCC Congress North Campus
                                        </p>
                                        <p style='margin: 0 0 5px 0;'>&copy; " . date('Y') . " EquipTrack MIS. All rights reserved.</p>
                                        <p style='margin: 0;'>If you did not request this account, please ignore this email. No further action is required.</p>
                                    </td>
                                </tr>
                                
                            </table>
                            
                        </td>
                    </tr>
                </table>
                
            </body>
            </html>
            ";
            $mail->send();

            // Commit to Database ONLY if email succeeds
            $mysql->commit(); 
            
            $message = "<script>document.addEventListener('DOMContentLoaded', function() { Swal.fire({title: 'Check your email!', text: 'We sent a verification link to your email address. You must click it to log in.', icon: 'info', showCancelButton: false, confirmButtonColor: '#11998e', confirmButtonText: 'Understood'}).then((result) => {if (result.isConfirmed) {window.location.href = 'login.php';}});});</script>";
            
            $_POST = array(); // Clear data

        } catch (Exception $e) {
            $mysql->rollback(); 
            $message = "<script>document.addEventListener('DOMContentLoaded', function() { Swal.fire({icon: 'error', title: 'Registration Failed!', text: 'An unexpected error occurred. Please try again.', confirmButtonColor: '#d33', confirmButtonText: '✖ Try Again'});});</script>";
        }
    } else {
        $displayErr = !empty($errorMsg) ? $errorMsg : $email_duplicate_err;
        if (!empty($displayErr)) {
            $message = "<script>document.addEventListener('DOMContentLoaded', function() { Swal.fire({icon: 'warning', title: 'Invalid Input', text: '$displayErr', confirmButtonColor: '#d33', confirmButtonText: '✖ Fix and Try Again'});});</script>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EquipTrack | Register</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        body, html { height: 100%; margin: 0; overflow-x: hidden; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-image: url('bg.jpg'); background-size: cover; background-position: center; background-attachment: fixed; }
        .card { border: none; box-shadow: 0 15px 35px rgba(0,0,0,0.4); }
        .form-control, .form-select { border: 1px solid rgba(0, 0, 0, 0.2); background-color: transparent !important; color: #000000 !important; transition: border-color 0.3s, box-shadow 0.3s; }
        .form-control:focus, .form-select:focus { border-color: #38ef7d; box-shadow: 0 0 8px rgba(56, 239, 125, 0.4); }
        .input-invalid { border-color: #dc3545 !important; box-shadow: 0 0 8px rgba(220, 53, 69, 0.4) !important; }
        .input-valid { border-color: #198754 !important; box-shadow: 0 0 8px rgba(25, 135, 84, 0.4) !important; }
        .btn-outline-dark:hover { background-color: #3a5a40; border-color: #38ef7d; color:#ffffff; }
        .custom-card-width { max-width: 450px; width: 100%; } 
        .password-hint { font-size: 0.75rem; color: #6c757d; margin-top: 4px; }
        .cursor-pointer { cursor: pointer; color: #6c757d; transition: color 0.2s; }
        .cursor-pointer:hover { color: #38ef7d; }

        /* Stepper Display Logic */
        .step { display: none; }
        .step.active { display: block; animation: fadeIn 0.4s; }
        .step-indicator { width: 30px; height: 30px; background: #e9ecef; color: #6c757d; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; transition: 0.3s; }
        .step-indicator.active { background: #38ef7d; color: white; box-shadow: 0 0 0 4px rgba(56, 239, 125, 0.2); }
        .step-label { text-align: center; font-size: 0.75rem; color: #6c757d; margin-top: 5px; }
        @keyframes fadeIn { from { opacity: 0; transform: translateX(10px); } to { opacity: 1; transform: translateX(0); } }
    </style>
</head>
<body>

<section class="d-flex align-items-center py-5 min-vh-100">
  <div class="container d-flex justify-content-center">
    <div class="card text-dark custom-card-width bg-white" style="border-radius: 1rem;">
      <div class="card-body p-4 p-md-5">

        <div class="text-center">
            <h2 class="fw-bold mb-2 text-uppercase text-success">Create Account</h2>
            <p class="text-dark-50 mb-4">Register new member for EquipTrack</p>
        </div>

        <?php echo $message; ?>

        <div class="stepper-wrapper mb-4">
            <div class="position-relative d-flex justify-content-between align-items-center px-3">
                <div class="position-absolute top-50 start-0 w-100 bg-secondary" style="height: 2px; z-index: 1; opacity: 0.2;"></div>
                <div class="z-2 bg-white px-1"><div class="step-indicator active" id="indicator-1">1</div></div>
                <div class="z-2 bg-white px-1"><div class="step-indicator" id="indicator-2">2</div></div>
                <div class="z-2 bg-white px-1"><div class="step-indicator" id="indicator-3">3</div></div>
            </div>
            <div class="d-flex justify-content-between px-2 mt-2">
                <span class="step-label fw-bold text-dark" id="label-1">Info</span>
                <span class="step-label fw-bold" id="label-2">Security</span>
                <span class="step-label fw-bold" id="label-3">Review</span>
            </div>
        </div>

        <form method="POST" id="registrationForm" novalidate>
          <input type="hidden" name="role" id="role" value="Student">

          <div class="step active" id="step-1">
              
              <div class="row mb-3">
                  <div class="col-6">
                      <label class="form-label small text-dark-50" for="first_name">First Name</label>
                      <input type="text" name="first_name" id="first_name" class="form-control" placeholder="e.g. Juan" value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>" />
                      <div id="err_firstname" class="text-danger small mt-1 d-none fw-bold">Letters only.</div>
                  </div>
                  <div class="col-6">
                      <label class="form-label small text-dark-50" for="last_name">Last Name</label>
                      <input type="text" name="last_name" id="last_name" class="form-control" placeholder="e.g. Dela Cruz" value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>" />
                      <div id="err_lastname" class="text-danger small mt-1 d-none fw-bold">Letters only.</div>
                  </div>
              </div>

              <div class="mb-3">
                <label class="form-label small text-dark-50" for="email">Email Address</label>
                <input type="email" name="email" id="email" class="form-control <?php echo !empty($email_duplicate_err) ? 'input-invalid' : ''; ?>" placeholder="student@example.com" value="<?php echo (isset($_POST['email']) && !isset($clear_email)) ? htmlspecialchars($_POST['email']) : ''; ?>" />
                <div id="err_email" class="text-danger small mt-1 d-none fw-bold">Valid email required.</div>
                <?php if(!empty($email_duplicate_err)): ?>
                    <div id="server_err_email" class="text-danger small mt-1 fw-bold"><?php echo $email_duplicate_err; ?></div>
                <?php endif; ?>
              </div>

              <div class="mb-3">
                <label class="form-label small text-dark-50" for="student_no">Student No.</label>
                <input type="text" name="student_no" id="student_no" class="form-control" placeholder="e.g. 2024-0001" value="<?php echo isset($_POST['student_no']) ? htmlspecialchars($_POST['student_no']) : ''; ?>" />
                <div id="err_student_no" class="text-danger small mt-1 d-none fw-bold">Student number is required.</div>
              </div>

              <div class="mb-4">
                <label class="form-label small text-dark-50" for="course_section">Course & Section</label>
                <input type="text" name="course_section" id="course_section" class="form-control" placeholder="e.g. BSIS 3A" value="<?php echo isset($_POST['course_section']) ? htmlspecialchars($_POST['course_section']) : ''; ?>" />
                <div id="err_course" class="text-danger small mt-1 d-none fw-bold">Course and section required.</div>
              </div>

              <button type="button" class="btn btn-outline-dark btn-lg w-100 fw-bold mt-2" onclick="goToStep(2)">NEXT STEP</button>
          </div>

          <div class="step" id="step-2">
              <div class="mb-3">
                <label class="form-label small text-dark-50" for="password">Password</label>
                <div class="position-relative">
                    <input type="password" name="password" id="password" class="form-control pe-5" value="<?php echo isset($_POST['password']) ? htmlspecialchars($_POST['password']) : ''; ?>" />
                    <i class="bi bi-eye-slash position-absolute top-50 end-0 translate-middle-y me-3 cursor-pointer" id="togglePassword"></i>
                </div>
                <div class="password-hint">Min 8 chars, 1 uppercase, 1 lowercase, 1 number.</div>
                <div id="err_password" class="text-danger small mt-1 d-none fw-bold">Please follow the requirements above.</div>
              </div>

              <div class="mb-4">
                <label class="form-label small text-dark-50" for="confirm_password">Confirm Password</label>
                <div class="position-relative">
                    <input type="password" name="confirm_password" id="confirm_password" class="form-control pe-5" value="<?php echo isset($_POST['confirm_password']) ? htmlspecialchars($_POST['confirm_password']) : ''; ?>" />
                    <i class="bi bi-eye-slash position-absolute top-50 end-0 translate-middle-y me-3 cursor-pointer" id="toggleConfirmPassword"></i>
                </div>
                <div class="password-hint">Passwords must match.</div>
                <div id="err_confirm" class="text-danger small mt-1 d-none fw-bold">Passwords do not match.</div>
              </div>

              <div class="d-flex gap-2 mt-2">
                  <button type="button" class="btn btn-secondary btn-lg w-50 fw-bold" onclick="goToStep(1)">BACK</button>
                  <button type="button" class="btn btn-outline-dark btn-lg w-50 fw-bold" onclick="goToStep(3)">REVIEW</button>
              </div>
          </div>

          <div class="step" id="step-3">
              <div class="mb-4 bg-light p-3 rounded border">
                  <h6 class="fw-bold mb-3 text-success border-bottom pb-2">Information Summary</h6>
                  <p class="mb-1 small"><strong class="text-dark-50">Full Name:</strong> <span id="review_name" class="fw-bold text-dark"></span></p>
                  <p class="mb-1 small"><strong class="text-dark-50">Email:</strong> <span id="review_email" class="fw-bold text-dark"></span></p>
                  <p class="mb-1 small"><strong class="text-dark-50">Student No:</strong> <span id="review_student_no" class="fw-bold text-dark"></span></p>
                  <p class="mb-1 small"><strong class="text-dark-50">Course:</strong> <span id="review_course" class="fw-bold text-dark"></span></p>
              </div>

              <div class="d-flex gap-2">
                  <button type="button" class="btn btn-secondary btn-lg w-50 fw-bold" onclick="goToStep(2)">BACK</button>
                  <button class="btn btn-success btn-lg w-50 fw-bold" type="submit" id="submitBtn">REGISTER</button>
              </div>
          </div>
        </form>

        <div class="text-center mt-4">
          <p class="mb-0 small">Already have an account? <a href="login.php" class="text-success fw-bold text-decoration-none">Login here</a></p>
        </div>

      </div>
    </div>
  </div>
</section>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const form = document.getElementById("registrationForm");
        const firstNameInput = document.getElementById("first_name");
        const lastNameInput = document.getElementById("last_name");
        const passwordInput = document.getElementById("password");
        const confirmInput = document.getElementById("confirm_password");
        const studentNoInput = document.getElementById("student_no");
        const courseSectionInput = document.getElementById("course_section");
        const emailInput = document.getElementById("email");
        
        const nameRegex = /^[a-zA-Z\s\-\.]+$/; 
        const passRegex = /^(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}$/;
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

        // Eye Toggles for Passwords
        function setupToggle(inputId, iconId) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById(iconId);
            icon.addEventListener("click", function() {
                if (input.type === "password") {
                    input.type = "text";
                    icon.classList.remove("bi-eye-slash");
                    icon.classList.add("bi-eye");
                } else {
                    input.type = "password";
                    icon.classList.remove("bi-eye");    
                    icon.classList.add("bi-eye-slash");
                }
            });
        }
        setupToggle("password", "togglePassword");
        setupToggle("confirm_password", "toggleConfirmPassword");

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

        // Live Validation Handlers
        firstNameInput.addEventListener("input", function() { 
            this.value = this.value.replace(/[0-9]/g, '');
            setValidationState(this, nameRegex.test(this.value.trim()) && this.value.trim().length >= 2, "err_firstname"); 
        });
        lastNameInput.addEventListener("input", function() { 
            this.value = this.value.replace(/[0-9]/g, '');
            setValidationState(this, nameRegex.test(this.value.trim()) && this.value.trim().length >= 2, "err_lastname"); 
        });
        emailInput.addEventListener("input", function() { 
            setValidationState(this, emailRegex.test(this.value), "err_email"); 
            const serverErr = document.getElementById("server_err_email");
            if(serverErr) serverErr.classList.add("d-none");
        });
        studentNoInput.addEventListener("input", function() { setValidationState(this, this.value.trim().length > 0, "err_student_no"); });
        courseSectionInput.addEventListener("input", function() { setValidationState(this, this.value.trim().length > 0, "err_course"); });
        
        passwordInput.addEventListener("input", function() {
            setValidationState(this, passRegex.test(this.value), "err_password");
            if (confirmInput.value.length > 0) validateConfirmPassword(); 
        });
        function validateConfirmPassword() {
            const isValid = confirmInput.value === passwordInput.value && passwordInput.value.length > 0;
            setValidationState(confirmInput, isValid, "err_confirm");
        }
        confirmInput.addEventListener("input", validateConfirmPassword);

        // 3-Step Navigation Logic
        window.goToStep = function(stepNum) {
            // Validate Step 1 before leaving
            if(stepNum > 1) {
                const isFirstValid = nameRegex.test(firstNameInput.value.trim()) && firstNameInput.value.trim().length >= 2;
                const isLastValid = nameRegex.test(lastNameInput.value.trim()) && lastNameInput.value.trim().length >= 2;
                const isEmailValid = emailRegex.test(emailInput.value);
                const isStudentNoValid = studentNoInput.value.trim().length > 0;
                const isCourseSectionValid = courseSectionInput.value.trim().length > 0;

                setValidationState(firstNameInput, isFirstValid, "err_firstname");
                setValidationState(lastNameInput, isLastValid, "err_lastname");
                setValidationState(emailInput, isEmailValid, "err_email");
                setValidationState(studentNoInput, isStudentNoValid, "err_student_no");
                setValidationState(courseSectionInput, isCourseSectionValid, "err_course");

                if(!isFirstValid || !isLastValid || !isEmailValid || !isStudentNoValid || !isCourseSectionValid) {
                    Swal.fire({ icon: 'warning', title: 'Check your inputs', text: 'Please complete all required fields correctly before proceeding.', confirmButtonColor: '#3a5a40' });
                    return;
                }
            }

            // Validate Step 2 before going to Review
            if(stepNum === 3) {
                const isPassValid = passRegex.test(passwordInput.value);
                const isConfValid = confirmInput.value === passwordInput.value && passwordInput.value.length > 0;

                setValidationState(passwordInput, isPassValid, "err_password");
                setValidationState(confirmInput, isConfValid, "err_confirm");

                if(!isPassValid || !isConfValid) {
                    Swal.fire({ icon: 'warning', title: 'Check your inputs', text: 'Please ensure your passwords match and meet the complexity requirements.', confirmButtonColor: '#3a5a40' });
                    return;
                }

                // Populate Review Data (Concatenating First and Last Name!)
                const fullName = firstNameInput.value.trim() + " " + lastNameInput.value.trim();
                document.getElementById('review_name').textContent = fullName;
                
                document.getElementById('review_email').textContent = emailInput.value.trim();
                document.getElementById('review_student_no').textContent = studentNoInput.value.trim();
                document.getElementById('review_course').textContent = courseSectionInput.value.trim();
            }

            // Hide all steps, un-active all indicators
            [1, 2, 3].forEach(i => {
                document.getElementById('step-' + i).classList.remove('active');
                document.getElementById('indicator-' + i).classList.remove('active');
                document.getElementById('label-' + i).classList.remove('text-dark');
            });

            // Show target step, activate indicators up to that step
            document.getElementById('step-' + stepNum).classList.add('active');
            for(let i = 1; i <= stepNum; i++) {
                document.getElementById('indicator-' + i).classList.add('active');
                document.getElementById('label-' + i).classList.add('text-dark');
            }
        };

        // Final Submission Safety Catch
        form.addEventListener("submit", function(e) {
            if(!passRegex.test(passwordInput.value)) {
                e.preventDefault();
                Swal.fire({ icon: 'error', title: 'Error', text: 'Password requirement not met.', confirmButtonColor: '#d33' });
            }
        });
    });
</script>

</body>
</html>