<?php
session_start();
include '../INCLUDES/database.php';
$message = "";

// Security check: ONLY Admins can access the Manage Users page
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../PAGES/login.php");
    exit();
}

$sidebar_file = '../INCLUDES/sidebarAdmin.php';

// --- 1. HANDLE ADD USER (SMART DUAL-SAVE) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_user'])) {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $role = $_POST['role'];
    $password_raw = $_POST['password'];
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "<script>document.addEventListener('DOMContentLoaded', function() { Swal.fire({title: 'Error', text: 'Please enter a valid email address.', icon: 'error', confirmButtonColor: '#d33'}); });</script>";
    } else {
        $password = password_hash($password_raw, PASSWORD_DEFAULT);
        
        $mysql->begin_transaction();
        $success = true;

        // Step A: Insert into the User table (Using first, last, and email)
        $sql = "INSERT INTO user (first_name, last_name, email, password, role, status) VALUES (?, ?, ?, ?, ?, 'Active')";
        if ($stmt = $mysql->prepare($sql)) {
            $stmt->bind_param("sssss", $first_name, $last_name, $email, $password, $role);
            if (!$stmt->execute()) {
                $success = false; 
            }
            $stmt->close();
        } else {
            $success = false;
        }

        // Step B: If it's a Student, insert into the Students table
        if ($success && $role === 'Student') {
            $student_no = trim($_POST['student_no']);
            $course_section = trim($_POST['course_section']);
            
            $sql_student = "INSERT INTO students (student_id, first_name, last_name, email, course_section) VALUES (?, ?, ?, ?, ?)";
            if ($stmt_student = $mysql->prepare($sql_student)) {
                $stmt_student->bind_param("sssss", $student_no, $first_name, $last_name, $email, $course_section);
                if (!$stmt_student->execute()) {
                    $success = false; 
                }
                $stmt_student->close();
            } else {
                $success = false;
            }
        }

        if ($success) {
            $mysql->commit();
            $message = "<script>document.addEventListener('DOMContentLoaded', function() { Swal.fire({title: 'Success!', text: 'New user created successfully.', icon: 'success', confirmButtonColor: '#3a5a40'}); });</script>";
        } else {
            $mysql->rollback(); 
            $message = "<script>document.addEventListener('DOMContentLoaded', function() { Swal.fire({title: 'Error', text: 'Failed to save. Email or Student ID may already exist.', icon: 'error', confirmButtonColor: '#d33'}); });</script>";
        }
    }
}

// --- 2. HANDLE EDIT USER (DUAL UPDATE) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_user'])) {
    $user_id = $_POST['user_id'];
    $old_email = $_POST['old_email']; // Used to find the student record
    
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $role = $_POST['role'];

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "<script>document.addEventListener('DOMContentLoaded', function() { Swal.fire({title: 'Error', text: 'Please enter a valid email address.', icon: 'error', confirmButtonColor: '#d33'}); });</script>";
    } else {
        
        $mysql->begin_transaction();
        $success = true;

        // Update basic user table details 
        $sql = "UPDATE user SET first_name = ?, last_name = ?, email = ?, role = ? WHERE user_id = ?";
        if ($stmt = $mysql->prepare($sql)) {
            $stmt->bind_param("ssssi", $first_name, $last_name, $email, $role, $user_id);
            if (!$stmt->execute()) {
                $success = false;
            }
            $stmt->close();
        } else {
            $success = false;
        }

        // If they are a student, update or insert their student data
        if ($success && $role === 'Student') {
            $student_no = trim($_POST['student_no']);
            $course_section = trim($_POST['course_section']);

            // Check if they already have a student record linked to their old email
            $check = $mysql->prepare("SELECT * FROM students WHERE email = ?");
            $check->bind_param("s", $old_email);
            $check->execute();
            $exists = $check->get_result()->num_rows > 0;
            $check->close();

            if ($exists) {
                // Update existing student record
                $upd = $mysql->prepare("UPDATE students SET student_id=?, first_name=?, last_name=?, email=?, course_section=? WHERE email=?");
                if ($upd) {
                    $upd->bind_param("ssssss", $student_no, $first_name, $last_name, $email, $course_section, $old_email);
                    if (!$upd->execute()) $success = false;
                    $upd->close();
                } else $success = false;
            } else {
                // Insert new student record (e.g., if Staff was promoted to Student)
                $ins = $mysql->prepare("INSERT INTO students (student_id, first_name, last_name, email, course_section) VALUES (?, ?, ?, ?, ?)");
                if ($ins) {
                    $ins->bind_param("sssss", $student_no, $first_name, $last_name, $email, $course_section);
                    if (!$ins->execute()) $success = false;
                    $ins->close();
                } else $success = false;
            }
        }

        if ($success) {
            $mysql->commit();
            $message = "<script>document.addEventListener('DOMContentLoaded', function() { Swal.fire({title: 'Updated!', text: 'Account details updated successfully.', icon: 'success', confirmButtonColor: '#3a5a40'}); });</script>";
        } else {
            $mysql->rollback();
            $message = "<script>document.addEventListener('DOMContentLoaded', function() { Swal.fire({title: 'Error', text: 'Failed to update. Check if Email or Student ID is a duplicate.', icon: 'error', confirmButtonColor: '#d33'}); });</script>";
        }
    }
}

// --- 3. HANDLE ARCHIVE USER ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['archive_user'])) {
    $user_id = $_POST['user_id'];
    // FIXED: Using the string 'Archived' to match your SQL ENUM
    $sql = "UPDATE user SET status = 'Archived' WHERE user_id = ?";
    if ($stmt = $mysql->prepare($sql)) {
        $stmt->bind_param("i", $user_id);
        if ($stmt->execute()) {
            $message = "<script>document.addEventListener('DOMContentLoaded', function() { Swal.fire({title: 'Archived!', text: 'User has been moved to the archive.', icon: 'success', confirmButtonColor: '#3a5a40'}); });</script>";
        }
        $stmt->close();
    }
}

// --- 4. SMART QUERY: Fetches User Info + Student Info ---
$query = "SELECT u.user_id, u.first_name, u.last_name, u.email as user_email, u.role, 
                 s.student_id, s.course_section 
          FROM user u 
          LEFT JOIN students s ON u.email = s.email 
          WHERE u.status = 'Active' 
          ORDER BY u.user_id ASC";
$result = $mysql->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - EquipTrack</title>
    
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,600,700&display=fallback">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        :root {
            --brand-color: #3a5a40;
            --brand-hover: #2c4430;
            --bg-body: #f4f7f6;
        }

        body { 
            background-color: var(--bg-body); 
            margin: 0;
            overflow: hidden; 
            font-family: 'Source Sans Pro', sans-serif;
            color: #333;
        }
        
        /* Layout Wrappers */
        .wrapper { display: flex; width: 100%; height: 100vh; position: relative; overflow: hidden; }
        .content-wrapper { flex-grow: 1; display: flex; flex-direction: column; width: calc(100% - 250px); height: 100vh; overflow-y: auto; overflow-x: hidden; transition: width 0.3s ease; }
        .content-wrapper.expanded { width: calc(100% - 70px); }
        .main-header { background-color: var(--brand-color); padding: 12px 20px; }

        /* Card & Table Styling */
        .card-header-custom { border-bottom: 1px solid #eaedf1 !important; padding-bottom: 1.25rem !important; padding-top: 1.25rem !important; }
        .table-custom-wrapper { border-radius: 1rem; overflow: hidden; border: 1px solid #eaedf1; }
        .table-custom-wrapper .table-responsive { border-radius: 1rem; }
        .table thead th { background-color: #f8f9fa; color: #6c757d; font-weight: 600; letter-spacing: 0.5px; border-bottom: 2px solid #eaedf1; }
        .table tbody tr { transition: background-color 0.2s ease; }
        .table tbody tr:hover { background-color: #f8fbfa; }

        /* Buttons & Inputs */
        .btn-brand { background-color: var(--brand-color); color: white; border: none; transition: all 0.3s ease; }
        .btn-brand:hover { background-color: var(--brand-hover); color: white; transform: translateY(-1px); box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .action-btn { border-radius: 0.5rem; transition: all 0.2s ease; }
        .action-btn:hover { transform: translateY(-2px); box-shadow: 0 4px 8px rgba(0,0,0,0.1) !important; }
        .custom-input { border-radius: 0.5rem; padding: 0.6rem 1rem; border: 1px solid #dee2e6; background-color: #f8f9fa; transition: all 0.2s ease-in-out; }
        .custom-input:focus { background-color: #fff; border-color: var(--brand-color); box-shadow: 0 0 0 0.25rem rgba(58, 90, 64, 0.15); }
        
        /* Badges */
        .badge-role { font-size: 0.80em; padding: 0.5em 1em; min-width: 85px; letter-spacing: 0.5px; font-weight: 600; }

        /* Modals */
        .modal-content { border: none; border-radius: 1rem; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }

        /* Responsive Mobile Adjustments */
        @media (max-width: 768px) {
            .content-wrapper, .content-wrapper.expanded { width: 100%; }
            .container-fluid.p-4 { padding: 1.5rem 1rem !important; }

            /* Stack header elements */
            .d-flex.flex-column.flex-md-row { align-items: stretch !important; }
            .d-flex.flex-column.flex-md-row > div { text-align: center; margin-bottom: 1rem; }

            /* Full width button */
            .btn-brand { width: 100%; padding: 0.75rem !important; }

            /* Prevent table data from squishing */
            .table th, .table td {
                white-space: nowrap;
                padding: 0.75rem 0.5rem !important;
            }
        }
    </style>
</head>
<body>
<?php echo $message; ?>

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
                            <span class="nav-link font-weight-bold text-light p-0" style="font-size: 1.1rem; letter-spacing: 0.5px;">Manage Accounts</span>
                        </li>
                    </ul>
                </div>
            </nav>

            <div class="container-fluid p-4">
                
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
                    <div>
                        <h4 class="mb-0 text-dark fw-bold">Manage Users</h4>
                        <p class="text-muted small mb-0 mt-1">System Administrator Access: Add, edit, or archive accounts.</p>
                    </div>
                    
                    <button type="button" class="btn btn-brand px-4 py-2 fw-semibold shadow-sm rounded-pill" data-bs-toggle="modal" data-bs-target="#addUserModal">
                        <i class="bi bi-person-plus-fill me-2"></i> Add New User
                    </button>
                </div>

                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white card-header-custom border-0 px-4">
                        <h5 class="mb-0 text-dark fw-bold">
                            <i class="bi bi-people-fill text-primary me-2" style="color: var(--brand-color)!important;"></i> Active Accounts
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-custom-wrapper m-3">
                            <div class="table-responsive">
                                <table class="table align-middle mb-0 bg-white">
                                    <thead>
                                        <tr class="text-uppercase" style="font-size: 0.80rem;">
                                            <th class="ps-4 py-3 border-0">Full Name</th>
                                            <th class="py-3 border-0">Email</th>
                                            <th class="text-center py-3 border-0">Role</th>
                                            <th class="text-center py-3 border-0">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($result && $result->num_rows > 0): ?>
                                            <?php while ($row = $result->fetch_assoc()): ?>
                                                <?php $full_name = trim($row['first_name'] . " " . $row['last_name']); ?>
                                                <tr>
                                                    <td class="ps-4 py-3 fw-bold text-dark" style="font-size: 0.95rem;">
                                                        <?php echo htmlspecialchars($full_name ?: 'No Name'); ?>
                                                    </td>
                                                    <td class="py-3 text-muted fw-semibold font-monospace" style="font-size: 0.9rem;">
                                                        <?php echo htmlspecialchars($row['user_email']); ?>
                                                    </td>
                                                    <td class="text-center py-3">
                                                        <?php 
                                                            $role = $row['role'];
                                                            if ($role === 'Admin') echo '<span class="badge bg-transparent text-danger border border-danger rounded-pill badge-role text-uppercase">Admin</span>';
                                                            elseif ($role === 'Staff') echo '<span class="badge bg-transparent text-primary border border-primary rounded-pill badge-role text-uppercase">Staff</span>';
                                                            elseif ($role === 'Student') echo '<span class="badge bg-transparent text-success border border-success rounded-pill badge-role text-uppercase">Student</span>';
                                                            else echo '<span class="badge bg-transparent text-secondary border border-secondary rounded-pill badge-role">Unknown</span>';
                                                        ?>
                                                    </td>
                                                    <td class="text-center py-3">
                                                        <button class="btn btn-sm btn-outline-primary px-3 py-1 fw-bold action-btn edit-btn" 
                                                                data-id="<?= $row['user_id'] ?>" 
                                                                data-fname="<?= htmlspecialchars($row['first_name']) ?>"
                                                                data-lname="<?= htmlspecialchars($row['last_name']) ?>"
                                                                data-email="<?= htmlspecialchars($row['user_email']) ?>" 
                                                                data-role="<?= $row['role'] ?>"
                                                                data-student-id="<?= htmlspecialchars($row['student_id'] ?? '') ?>"
                                                                data-course="<?= htmlspecialchars($row['course_section'] ?? '') ?>">
                                                            <i class="bi bi-pencil-square me-1"></i> 
                                                        </button>
                                                        
                                                        <button class="btn btn-sm btn-outline-warning text-dark px-3 py-1 ms-2 fw-bold action-btn archive-btn" 
                                                                data-id="<?= $row['user_id'] ?>" data-name="<?= htmlspecialchars($full_name) ?>">
                                                            <i class="bi bi-archive-fill me-1"></i> 
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="4" class="text-center py-5 text-muted">
                                                    <div class="d-flex flex-column align-items-center">
                                                        <i class="bi bi-people fs-1 text-light mb-2"></i>
                                                        <span>No active users found.</span>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php if(file_exists('../INCLUDES/footer.php')) include '../INCLUDES/footer.php'; ?>
        </div>
    </div>

<div class="modal fade" id="addUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-white border-bottom pt-4 px-4 pb-3">
                <h5 class="modal-title fw-bold text-dark"><i class="bi bi-person-plus-fill text-success me-2"></i>Add New User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body p-4">
                    
                    <div class="row mb-3">
                        <div class="col-6">
                            <label class="form-label text-dark fw-semibold small">First Name</label>
                            <input type="text" class="form-control custom-input" name="first_name" placeholder="e.g. John" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label text-dark fw-semibold small">Last Name</label>
                            <input type="text" class="form-control custom-input" name="last_name" placeholder="e.g. Doe" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-dark fw-semibold small">Email Address</label>
                        <input type="email" class="form-control custom-input" name="email" placeholder="user@example.com" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-dark fw-semibold small">Temporary Password</label>
                        <input type="password" class="form-control custom-input" name="password" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label text-dark fw-semibold small">Role</label>
                        <select class="form-select custom-input" name="role" id="addRoleSelect" required>
                            <option value="Student" selected>Student</option>
                            <option value="Staff">Staff</option>
                            <option value="Admin">Admin</option>
                        </select>
                    </div>

                    <div id="addStudentFields" class="bg-light p-3 border rounded-3 mt-4">
                        <p class="small text-primary fw-bold mb-3 text-uppercase tracking-wide" style="color: var(--brand-color)!important;"><i class="bi bi-info-circle-fill me-1"></i> Student Details</p>
                        <div class="mb-3">
                            <label class="form-label text-dark fw-semibold small">Student No.</label>
                            <input type="text" class="form-control custom-input bg-white" name="student_no" placeholder="e.g. 2024-1001">
                        </div>
                        <div class="mb-0">
                            <label class="form-label text-dark fw-semibold small">Course & Section</label>
                            <input type="text" class="form-control custom-input bg-white" name="course_section" placeholder="e.g. BSIS 3A">
                        </div>
                    </div>

                </div>
                <div class="modal-footer bg-light border-top-0 px-4 py-3">
                    <button type="button" class="btn btn-light border px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_user" class="btn btn-success fw-bold px-4 shadow-sm">Save User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="editUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-white border-bottom pt-4 px-4 pb-3">
                <h5 class="modal-title fw-bold text-dark"><i class="bi bi-pencil-square text-primary me-2"></i>Edit User Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body p-4">
                    <input type="hidden" name="user_id" id="edit_user_id">
                    <input type="hidden" name="old_email" id="old_email">
                    
                    <div class="row mb-3">
                        <div class="col-6">
                            <label class="form-label text-dark fw-semibold small">First Name</label>
                            <input type="text" class="form-control custom-input" name="first_name" id="edit_first_name" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label text-dark fw-semibold small">Last Name</label>
                            <input type="text" class="form-control custom-input" name="last_name" id="edit_last_name" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-dark fw-semibold small">Email Address</label>
                        <input type="email" class="form-control custom-input" name="email" id="edit_email" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-dark fw-semibold small">Role</label>
                        <select class="form-select custom-input" name="role" id="editRoleSelect" required>
                            <option value="Student">Student</option>
                            <option value="Staff">Staff</option>
                            <option value="Admin">Admin</option>
                        </select>
                    </div>

                    <div id="editStudentFields" class="bg-light p-3 border rounded-3 mt-4">
                        <p class="small text-primary fw-bold mb-3 text-uppercase tracking-wide" style="color: var(--brand-color)!important;"><i class="bi bi-info-circle-fill me-1"></i> Student Details</p>
                        <div class="mb-3">
                            <label class="form-label text-dark fw-semibold small">Student No.</label>
                            <input type="text" class="form-control custom-input bg-white" name="student_no" id="edit_student_no">
                        </div>
                        <div class="mb-0">
                            <label class="form-label text-dark fw-semibold small">Course & Section</label>
                            <input type="text" class="form-control custom-input bg-white" name="course_section" id="edit_course_section" placeholder="e.g. BSIS 3A">
                        </div>
                    </div>

                </div>
                <div class="modal-footer bg-light border-top-0 px-4 py-3">
                    <button type="button" class="btn btn-light border px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="edit_user" class="btn btn-primary fw-bold px-4 shadow-sm">Update Account</button>
                </div>
            </form>
        </div>
    </div>
</div>

<form id="archiveForm" method="POST" style="display: none;">
    <input type="hidden" name="user_id" id="archive_user_id">
    <input type="hidden" name="archive_user" value="1">
</form>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        
        // --- ADD MODAL LOGIC ---
        const addRoleSelect = document.getElementById('addRoleSelect');
        const addStudentFields = document.getElementById('addStudentFields');
        const addStudentInputs = addStudentFields.querySelectorAll('input');

        function toggleAddStudentFields() {
            if (addRoleSelect.value === 'Student') {
                addStudentFields.style.display = 'block';
                addStudentInputs.forEach(input => input.setAttribute('required', 'required'));
            } else {
                addStudentFields.style.display = 'none';
                addStudentInputs.forEach(input => {
                    input.removeAttribute('required');
                    input.value = ''; 
                });
            }
        }
        addRoleSelect.addEventListener('change', toggleAddStudentFields);
        toggleAddStudentFields();

        // --- EDIT MODAL LOGIC ---
        const editRoleSelect = document.getElementById('editRoleSelect');
        const editStudentFields = document.getElementById('editStudentFields');
        const editStudentInputs = editStudentFields.querySelectorAll('input');

        function toggleEditStudentFields() {
            if (editRoleSelect.value === 'Student') {
                editStudentFields.style.display = 'block';
                editStudentInputs.forEach(input => input.setAttribute('required', 'required'));
            } else {
                editStudentFields.style.display = 'none';
                editStudentInputs.forEach(input => input.removeAttribute('required'));
            }
        }
        editRoleSelect.addEventListener('change', toggleEditStudentFields);

        // --- SIDEBAR & POPULATE EDIT MODAL ---
        const sidebarToggle = document.getElementById('sidebarToggle');
        if(sidebarToggle) {
            sidebarToggle.addEventListener('click', function (e) {
                e.preventDefault();
                document.getElementById('mainContent').classList.toggle('expanded');
            });
        }

        const editModal = new bootstrap.Modal(document.getElementById('editUserModal'));
        document.querySelectorAll('.edit-btn').forEach(button => {
            button.addEventListener('click', function() {
                // Populate Basic User Data
                document.getElementById('edit_user_id').value = this.getAttribute('data-id');
                document.getElementById('old_email').value = this.getAttribute('data-email');
                document.getElementById('edit_first_name').value = this.getAttribute('data-fname');
                document.getElementById('edit_last_name').value = this.getAttribute('data-lname');
                document.getElementById('edit_email').value = this.getAttribute('data-email');
                document.getElementById('editRoleSelect').value = this.getAttribute('data-role');
                
                // Populate Student Data 
                document.getElementById('edit_student_no').value = this.getAttribute('data-student-id') || '';
                document.getElementById('edit_course_section').value = this.getAttribute('data-course') || '';

                // Run the toggle function to show/hide the fields based on the role
                toggleEditStudentFields();
                editModal.show();
            });
        });

        // --- ARCHIVE BUTTON LOGIC ---
        document.querySelectorAll('.archive-btn').forEach(button => {
            button.addEventListener('click', function() {
                const userId = this.getAttribute('data-id');
                const fullName = this.getAttribute('data-name');
                Swal.fire({
                    title: 'Archive User?',
                    html: `Are you sure you want to archive <strong>${fullName}</strong>?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#ffc107',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, archive'
                }).then((result) => {
                    if (result.isConfirmed) {
                        document.getElementById('archive_user_id').value = userId;
                        document.getElementById('archiveForm').submit();
                    }
                });
            });
        });
    });
</script>
</body>
</html>