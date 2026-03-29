<?php
include '../INCLUDES/database.php';
$message = "";
$username_duplicate_err = ""; // ONLY for the inline username error

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $full_name = $_POST['full_name'];
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

    // Added regex for letters and spaces only
    if (!preg_match('/^[a-zA-Z\s]+$/', trim($full_name)) || strlen(trim($full_name)) < 2) {
        $isValid = false;
        $errorMsg = "Full name must be letters and spaces only, and at least 2 characters.";
    } elseif (strlen($username) < 8 || strlen($username) > 16) {
        $isValid = false;
        $errorMsg = "Username must be between 8 and 16 characters.";
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
        $errorMsg = "A valid email address is required for students.";
    } elseif (empty($course_section)) {
        $isValid = false;
        $errorMsg = "Course & Section is required for students.";
    } else {
        // --- Duplicate Username Check (NO SWAL, INLINE ONLY) ---
        $stmt_check_user = $mysql->prepare("SELECT username FROM user WHERE username = ?");
        $stmt_check_user->bind_param("s", $username);
        $stmt_check_user->execute();
        $stmt_check_user->store_result();
        
        if ($stmt_check_user->num_rows > 0) {
            $isValid = false;
            $username_duplicate_err = "This username is already taken."; // Sets inline error variable
        }
        $stmt_check_user->close();

        // --- Duplicate Student No Check (WITH SWAL) ---
        // We only check this if there isn't already a basic validation error
        if (empty($errorMsg)) {
            $stmt_check_student = $mysql->prepare("SELECT student_id FROM students WHERE student_id = ?");
            $stmt_check_student->bind_param("s", $student_no);
            $stmt_check_student->execute();
            $stmt_check_student->store_result();
            
            if ($stmt_check_student->num_rows > 0) {
                $isValid = false;
                $errorMsg = "This Student No. is already registered."; // Sets standard error for Swal
            }
            $stmt_check_student->close();
        }
    }

    // Proceed to insert if everything is valid
    if ($isValid) {
        $password = password_hash($password_raw, PASSWORD_DEFAULT);
        
        $mysql->begin_transaction();
        
        try {
            // 1. Insert into USER table
            $sql_user = "INSERT INTO user (username, password, full_name, role, status) VALUES (?, ?, ?, ?, 1)";
            $stmt_user = $mysql->prepare($sql_user);
            $stmt_user->bind_param("ssss", $username, $password, $full_name, $role);
            $stmt_user->execute();
            $stmt_user->close();

            // 2. Insert into STUDENTS table
            $sql_student = "INSERT INTO students (student_id, full_name, course_section, email) VALUES (?, ?, ?, ?)";
            $stmt_student = $mysql->prepare($sql_student);
            $stmt_student->bind_param("ssss", $student_no, $full_name, $course_section, $email);
            $stmt_student->execute();
            $stmt_student->close();

            $mysql->commit();
            $message = "<script>document.addEventListener('DOMContentLoaded', function() { Swal.fire({title: 'Registration Successful!', text: 'Your account has been created.', icon: 'success', showCancelButton: false, confirmButtonColor: '#11998e', confirmButtonText: 'Yes, Go to Login'}).then((result) => {if (result.isConfirmed) {window.location.href = 'login.php';}});});</script>";

        } catch (Exception $e) {
            $mysql->rollback();
            $message = "<script>document.addEventListener('DOMContentLoaded', function() { Swal.fire({icon: 'error', title: 'Registration Failed!', text: 'An unexpected error occurred. Please try again.', confirmButtonColor: '#d33', confirmButtonText: '✖ Try Again'});});</script>";
        }
    } else {
        // ONLY trigger Swal if $errorMsg is NOT empty. 
        if (!empty($errorMsg)) {
            $message = "<script>document.addEventListener('DOMContentLoaded', function() { Swal.fire({icon: 'warning', title: 'Invalid Input', text: '$errorMsg', confirmButtonColor: '#d33', confirmButtonText: '✖ Fix and Try Again'});});</script>";
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
        .custom-card-width { max-width: 400px; width: 100%; }
        .password-hint { font-size: 0.75rem; color: #6c757d; margin-top: 4px; }
        .cursor-pointer { cursor: pointer; color: #6c757d; transition: color 0.2s; }
        .cursor-pointer:hover { color: #38ef7d; }
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

        <form method="POST" id="registrationForm" novalidate>
          
          <input type="hidden" name="role" id="role" value="Student">

          <div class="mb-3">
            <label class="form-label small text-dark-50" for="full_name">Full Name</label>
            <input type="text" name="full_name" id="full_name" class="form-control" placeholder="e.g Juan Dela Cruz" />
            <div id="err_fullname" class="text-danger small mt-1 d-none fw-bold">Letters and spaces only.</div>
          </div>

         <div class="mb-3">
    <label class="form-label small text-dark-50" for="username">Username</label>
    <input type="text" name="username" id="username" class="form-control <?php echo !empty($username_duplicate_err) ? 'input-invalid' : ''; ?>" placeholder="8-16 characters" />
    
    <div id="err_username" class="text-danger small mt-1 d-none fw-bold">Must be 8-16 characters.</div>
    
    <?php if(!empty($username_duplicate_err)): ?>
        <div id="server_err_username" class="text-danger small mt-1 fw-bold"><?php echo $username_duplicate_err; ?></div>
    <?php endif; ?>
</div>

          <div class="mb-3">
            <label class="form-label small text-dark-50" for="password">Password</label>
            <div class="position-relative">
                <input type="password" name="password" id="password" class="form-control pe-5" />
                <i class="bi bi-eye-slash position-absolute top-50 end-0 translate-middle-y me-3 cursor-pointer" id="togglePassword"></i>
            </div>
            <div class="password-hint">Min 8 chars, 1 uppercase, 1 lowercase, 1 number.</div>
            <div id="err_password" class="text-danger small mt-1 d-none fw-bold">Please follow the requirements above.</div>
          </div>

          <div class="mb-3">
            <label class="form-label small text-dark-50" for="confirm_password">Confirm Password</label>
            <div class="position-relative">
                <input type="password" name="confirm_password" id="confirm_password" class="form-control pe-5" />
                <i class="bi bi-eye-slash position-absolute top-50 end-0 translate-middle-y me-3 cursor-pointer" id="toggleConfirmPassword"></i>
            </div>
            <div class="password-hint">Passwords must match.</div>
            <div id="err_confirm" class="text-danger small mt-1 d-none fw-bold">Passwords do not match.</div>
          </div>

          <div id="student_fields">
            <div class="mb-3">
              <label class="form-label small text-dark-50" for="student_no">Student No.</label>
              <input type="text" name="student_no" id="student_no" class="form-control" placeholder="e.g. 2024-0001" />
              <div id="err_student_no" class="text-danger small mt-1 d-none fw-bold">Student number is required.</div>
            </div>
            <div class="mb-3">
              <label class="form-label small text-dark-50" for="course_section">Course & Section</label>
              <input type="text" name="course_section" id="course_section" class="form-control" placeholder="e.g. BSIS 3A" />
              <div id="err_course" class="text-danger small mt-1 d-none fw-bold">Course and section required.</div>
            </div>
            <div class="mb-4">
              <label class="form-label small text-dark-50" for="email">Email Address</label>
              <input type="email" name="email" id="email" class="form-control" placeholder="student@example.com" />
              <div id="err_email" class="text-danger small mt-1 d-none fw-bold">Valid email required.</div>
            </div>
          </div>

          <button class="btn btn-outline-dark btn-lg w-100 fw-bold mt-2" type="submit">REGISTER</button>
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
        const fullNameInput = document.getElementById("full_name");
        const usernameInput = document.getElementById("username");
        const passwordInput = document.getElementById("password");
        const confirmInput = document.getElementById("confirm_password");
        
        const nameRegex = /^[a-zA-Z\s]+$/; 
        const passRegex = /^(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}$/;
        
        const studentNoInput = document.getElementById("student_no");
        const courseSectionInput = document.getElementById("course_section");
        const emailInput = document.getElementById("email");
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

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

        // Fixed: No longer hides the hints when an error shows
        function setValidationState(input, isValid, errorTextId) {
            if (isValid) {
                input.classList.remove("input-invalid");
                input.classList.add("input-valid");
                document.getElementById(errorTextId).classList.add("d-none"); // Hide red error text
            } else {
                input.classList.remove("input-valid");
                input.classList.add("input-invalid");
                document.getElementById(errorTextId).classList.remove("d-none"); // Show red error text
            }
        }

        fullNameInput.addEventListener("input", function() { 
            setValidationState(this, nameRegex.test(this.value.trim()) && this.value.trim().length >= 2, "err_fullname"); 
        });
        
        usernameInput.addEventListener("input", function() { 
    // 1. Run your standard 8-16 character length check
    setValidationState(this, this.value.length >= 8 && this.value.length <= 16, "err_username"); 
    
    // 2. Hide the PHP "already taken" error if it exists on the page
    const serverErr = document.getElementById("server_err_username");
    if (serverErr) {
        serverErr.classList.add("d-none"); // Hides the text
    }
    
    // 3. Optional: If the user deletes everything, you might want to remove the red border 
    // that PHP forced onto the input field. 
    if (this.value.length === 0) {
        this.classList.remove("input-invalid");
    }
});
        
        passwordInput.addEventListener("input", function() {
            setValidationState(this, passRegex.test(this.value), "err_password");
            if (confirmInput.value.length > 0) validateConfirmPassword(); 
        });

        function validateConfirmPassword() {
            const isValid = confirmInput.value === passwordInput.value && passwordInput.value.length > 0;
            setValidationState(confirmInput, isValid, "err_confirm");
        }
        confirmInput.addEventListener("input", validateConfirmPassword);
        
        studentNoInput.addEventListener("input", function() { setValidationState(this, this.value.trim().length > 0, "err_student_no"); });
        courseSectionInput.addEventListener("input", function() { setValidationState(this, this.value.trim().length > 0, "err_course"); });
        emailInput.addEventListener("input", function() { setValidationState(this, emailRegex.test(this.value), "err_email"); });

        form.addEventListener("submit", function(e) {
            const isFullValid = nameRegex.test(fullNameInput.value.trim()) && fullNameInput.value.trim().length >= 2;
            const isUserValid = usernameInput.value.length >= 8 && usernameInput.value.length <= 16;
            const isPassValid = passRegex.test(passwordInput.value);
            const isConfValid = confirmInput.value === passwordInput.value && passwordInput.value.length > 0;
            const isStudentNoValid = studentNoInput.value.trim().length > 0;
            const isCourseSectionValid = courseSectionInput.value.trim().length > 0;
            const isEmailValid = emailRegex.test(emailInput.value);

            if (!isFullValid || !isUserValid || !isPassValid || !isConfValid || !isStudentNoValid || !isCourseSectionValid || !isEmailValid) {
                e.preventDefault(); 
                
                setValidationState(fullNameInput, isFullValid, "err_fullname");
                setValidationState(usernameInput, isUserValid, "err_username");
                setValidationState(passwordInput, isPassValid, "err_password");
                setValidationState(confirmInput, isConfValid, "err_confirm");
                setValidationState(studentNoInput, isStudentNoValid, "err_student_no");
                setValidationState(courseSectionInput, isCourseSectionValid, "err_course");
                setValidationState(emailInput, isEmailValid, "err_email");

                // Your original SweetAlert
                let errorHtml = "<div style='text-align:left; font-size: 0.9rem;'><ul>";
                if (!isFullValid) errorHtml += "<li><strong>Full Name</strong> requires letters and spaces only.</li>";
                if (!isUserValid) errorHtml += "<li><strong>Username</strong> must be 8-16 characters.</li>";
                if (!isPassValid) errorHtml += "<li><strong>Password</strong> needs 8+ chars, 1 uppercase, 1 lowercase, and 1 number.</li>";
                if (!isConfValid) errorHtml += "<li><strong>Passwords</strong> do not match.</li>";
                if (!isStudentNoValid) errorHtml += "<li><strong>Student No.</strong> is required.</li>";
                if (!isCourseSectionValid) errorHtml += "<li><strong>Course & Section</strong> is required.</li>";
                if (!isEmailValid) errorHtml += "<li><strong>Email Address</strong> is invalid or missing.</li>";
                errorHtml += "</ul></div>";

                Swal.fire({
                    icon: 'warning',
                    title: 'Check your inputs',
                    html: errorHtml
                    confirmButtonColor: '#d33',
                    confirmButtonText: 'Fix and Try Again'
                });
            }
        });
    });
</script>

</body>
</html>