<?php
include '../INCLUDES/database.php';
session_start();

$message = ""; // <--- Add this line back right here!

// Security check: ONLY Super Admins can access this page!
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Super Admin') {
    header("Location: login.php");
    exit();
}

$sidebar_file = '../INCLUDES/sidebarSuperAdmin.php';

// --- 1. HANDLE ADD ADMIN ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_admin'])) {
    $full_name = trim($_POST['full_name']);
    $username = trim($_POST['username']);
    $password_raw = $_POST['password'];
    $role = 'Admin'; // Strictly locked to Admin

    if (strlen($username) < 8 || strlen($username) > 16) {
        $message = "<script>document.addEventListener('DOMContentLoaded', function() { Swal.fire({title: 'Error', text: 'Username must be 8-16 characters.', icon: 'error', confirmButtonColor: '#d33'}); });</script>";
    } else {
        $password = password_hash($password_raw, PASSWORD_DEFAULT);
        
        $sql = "INSERT INTO user (full_name, username, password, role, status) VALUES (?, ?, ?, ?, 1)";
        if ($stmt = $mysql->prepare($sql)) {
            $stmt->bind_param("ssss", $full_name, $username, $password, $role);
            if ($stmt->execute()) {
                $message = "<script>document.addEventListener('DOMContentLoaded', function() { Swal.fire({title: 'Success!', text: 'New Admin account created successfully.', icon: 'success', confirmButtonColor: '#3a5a40'}); });</script>";
            } else {
                $message = "<script>document.addEventListener('DOMContentLoaded', function() { Swal.fire({title: 'Error', text: 'Username may already exist.', icon: 'error', confirmButtonColor: '#d33'}); });</script>";
            }
            $stmt->close();
        }
    }
}

// --- 2. HANDLE EDIT ADMIN (Optional Password Update) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_admin'])) {
    $user_id = $_POST['user_id'];
    $full_name = trim($_POST['full_name']);
    $username = trim($_POST['username']);
    $new_password = $_POST['password']; 

    if (strlen($username) < 8 || strlen($username) > 16) {
        $message = "<script>document.addEventListener('DOMContentLoaded', function() { Swal.fire({title: 'Error', text: 'Username must be 8-16 characters.', icon: 'error', confirmButtonColor: '#d33'}); });</script>";
    } else {
        if (!empty($new_password)) {
            // Update everything including a new hashed password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $sql = "UPDATE user SET full_name = ?, username = ?, password = ? WHERE user_id = ? AND role = 'Admin'";
            if ($stmt = $mysql->prepare($sql)) {
                $stmt->bind_param("sssi", $full_name, $username, $hashed_password, $user_id);
                if ($stmt->execute()) {
                    $message = "<script>document.addEventListener('DOMContentLoaded', function() { Swal.fire({title: 'Updated!', text: 'Admin details and password have been updated.', icon: 'success', confirmButtonColor: '#3a5a40'}); });</script>";
                }
                $stmt->close();
            }
        } else {
            // Update only the name and username
            $sql = "UPDATE user SET full_name = ?, username = ? WHERE user_id = ? AND role = 'Admin'";
            if ($stmt = $mysql->prepare($sql)) {
                $stmt->bind_param("ssi", $full_name, $username, $user_id);
                if ($stmt->execute()) {
                    $message = "<script>document.addEventListener('DOMContentLoaded', function() { Swal.fire({title: 'Updated!', text: 'Admin details have been updated.', icon: 'success', confirmButtonColor: '#3a5a40'}); });</script>";
                }
                $stmt->close();
            }
        }
    }
}

// --- 3. HANDLE ARCHIVE ADMIN ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['archive_admin'])) {
    $user_id = $_POST['user_id'];
    $sql = "UPDATE user SET status = 0 WHERE user_id = ? AND role = 'Admin'";
    if ($stmt = $mysql->prepare($sql)) {
        $stmt->bind_param("i", $user_id);
        if ($stmt->execute()) {
            $message = "<script>document.addEventListener('DOMContentLoaded', function() { Swal.fire({title: 'Archived!', text: 'Admin account deactivated.', icon: 'success', confirmButtonColor: '#3a5a40'}); });</script>";
        }
        $stmt->close();
    }
}

// --- 4. HANDLE PERMANENT DELETE ADMIN ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_admin'])) {
    $user_id = $_POST['user_id'];
    $sql = "DELETE FROM user WHERE user_id = ? AND role = 'Admin'";
    if ($stmt = $mysql->prepare($sql)) {
        $stmt->bind_param("i", $user_id);
        if ($stmt->execute()) {
            $message = "<script>document.addEventListener('DOMContentLoaded', function() { Swal.fire({title: 'Deleted!', text: 'Admin account permanently removed.', icon: 'success', confirmButtonColor: '#3a5a40'}); });</script>";
        }
        $stmt->close();
    }
}

// Fetch all Active Admins
$query = "SELECT user_id, full_name, username, status FROM user WHERE role = 'Admin' ORDER BY user_id ASC";
$result = $mysql->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin Dashboard - EquipTrack</title>
    
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
            overflow: hidden; /* Lock main page scroll */
            font-family: 'Source Sans Pro', sans-serif;
            color: #333;
        }
        
        /* Layout Wrappers */
        .wrapper { 
            display: flex; 
            width: 100%; 
            height: 100vh; 
            position: relative; 
            overflow: hidden; 
        }
        .content-wrapper { 
            flex-grow: 1; 
            display: flex; 
            flex-direction: column; 
            width: calc(100% - 250px); 
            height: 100vh; 
            overflow-y: auto; 
            overflow-x: hidden; 
            transition: width 0.3s ease; 
        }
        .content-wrapper.expanded { width: calc(100% - 70px); }
        .main-header { background-color: var(--brand-color); padding: 12px 20px; }

        /* Card & Table Styling */
        .card-header-custom {
            border-bottom: 1px solid #eaedf1 !important;
            padding-bottom: 1.25rem !important;
            padding-top: 1.25rem !important;
        }
        .table-custom-wrapper {
            border-radius: 1rem;
            overflow: hidden;
            border: 1px solid #eaedf1;
        }
        .table thead th {
            background-color: #f8f9fa;
            color: #6c757d;
            font-weight: 600;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #eaedf1;
        }
        .table tbody tr { transition: background-color 0.2s ease; }
        .table tbody tr:hover { background-color: #f8fbfa; }

        /* Buttons & Inputs */
        .btn-brand {
            background-color: var(--brand-color);
            color: white;
            border: none;
            transition: all 0.3s ease;
        }
        .btn-brand:hover {
            background-color: var(--brand-hover);
            color: white;
            transform: translateY(-1px);
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .action-btn {
            border-radius: 0.5rem;
            transition: all 0.2s ease;
        }
        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1) !important;
        }
        .custom-input {
            border-radius: 0.5rem;
            padding: 0.6rem 1rem;
            border: 1px solid #dee2e6;
            background-color: #f8f9fa;
            transition: all 0.2s ease-in-out;
        }
        .custom-input:focus {
            background-color: #fff;
            border-color: var(--brand-color);
            box-shadow: 0 0 0 0.25rem rgba(58, 90, 64, 0.15);
        }

        /* Modals */
        .modal-content {
            border: none;
            border-radius: 1rem;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        @media (max-width: 768px) {
            .content-wrapper, .content-wrapper.expanded { width: 100%; }
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
                            <span class="nav-link font-weight-bold text-light p-0" style="font-size: 1.1rem; letter-spacing: 0.5px;">Master Control</span>
                        </li>
                    </ul>
                </div>
            </nav>

            <div class="container-fluid p-4">
                
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
                    <div>
                        <h4 class="mb-0 text-dark fw-bold">System Administrators</h4>
                        <p class="text-muted small mb-0 mt-1">Manage top-level Admin access for EquipTrack.</p>
                    </div>
                    
                    <button type="button" class="btn btn-brand px-4 py-2 fw-semibold shadow-sm rounded-pill" data-bs-toggle="modal" data-bs-target="#addAdminModal">
                        <i class="bi bi-shield-plus me-2"></i> Add New Admin
                    </button>
                </div>

                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white card-header-custom border-0 px-4">
                        <h5 class="mb-0 text-dark fw-bold">
                            <i class="bi bi-shield-check text-primary me-2" style="color: var(--brand-color)!important;"></i> Registered Admins
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive table-custom-wrapper m-3">
                            <table class="table align-middle mb-0 bg-white">
                                <thead>
                                    <tr class="text-uppercase" style="font-size: 0.80rem;">
                                        <th class="ps-4 py-3 border-0">Full Name</th>
                                        <th class="py-3 border-0">Username</th>
                                        <th class="text-center py-3 border-0">Status</th>
                                        <th class="text-center py-3 border-0">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($result && $result->num_rows > 0): ?>
                                        <?php while ($row = $result->fetch_assoc()): ?>
                                            <tr>
                                                <td class="ps-4 py-3 fw-bold text-dark" style="font-size: 0.95rem;">
                                                    <?php echo htmlspecialchars($row['full_name']); ?>
                                                </td>
                                                <td class="py-3 text-muted fw-semibold font-monospace" style="font-size: 0.9rem;">
                                                    <?php echo htmlspecialchars($row['username']); ?>
                                                </td>
                                                <td class="text-center py-3">
                                                    <?php if ($row['status'] == 1): ?>
                                                        <span class="badge bg-transparent text-success border border-success px-3 py-1 rounded-pill">Active</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-transparent text-danger border border-danger px-3 py-1 rounded-pill">Archived</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-center py-3">
                                                    <button class="btn btn-sm btn-outline-primary px-3 py-1 fw-bold action-btn edit-btn" 
                                                            data-id="<?= $row['user_id'] ?>" 
                                                            data-name="<?= htmlspecialchars($row['full_name']) ?>"
                                                            data-user="<?= htmlspecialchars($row['username']) ?>">
                                                        <i class="bi bi-pencil-square me-1"></i> Edit
                                                    </button>
                                                    
                                                    <?php if ($row['status'] == 1): ?>
                                                        <button class="btn btn-sm btn-outline-warning text-dark px-3 py-1 ms-2 fw-bold action-btn archive-btn" 
                                                                data-id="<?= $row['user_id'] ?>" 
                                                                data-name="<?= htmlspecialchars($row['full_name']) ?>">
                                                            <i class="bi bi-archive-fill me-1"></i> Archive
                                                        </button>
                                                    <?php endif; ?>

                                                    <button class="btn btn-sm btn-outline-danger px-3 py-1 ms-2 fw-bold action-btn delete-btn" 
                                                            data-id="<?= $row['user_id'] ?>" 
                                                            data-name="<?= htmlspecialchars($row['full_name']) ?>">
                                                        <i class="bi bi-trash3 me-1"></i> Delete
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="text-center py-5 text-muted">
                                                <div class="d-flex flex-column align-items-center">
                                                    <i class="bi bi-shield-x fs-1 text-light mb-2"></i>
                                                    <span>No Admin accounts found.</span>
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
            
            <?php if(file_exists('../INCLUDES/footer.php')) include '../INCLUDES/footer.php'; ?>
        </div>
    </div>

<div class="modal fade" id="addAdminModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-white border-bottom pt-4 px-4 pb-3">
                <h5 class="modal-title fw-bold text-dark"><i class="bi bi-shield-plus text-success me-2"></i>Create Admin Account</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label text-dark fw-semibold small">Full Name</label>
                        <input type="text" class="form-control custom-input" name="full_name" placeholder="e.g. John Doe" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-dark fw-semibold small">Username</label>
                        <input type="text" class="form-control custom-input" name="username" minlength="8" maxlength="16" placeholder="8-16 characters" required>
                    </div>
                    <div class="mb-0">
                        <label class="form-label text-dark fw-semibold small">Secure Password</label>
                        <input type="password" class="form-control custom-input" name="password" required>
                    </div>
                </div>
                <div class="modal-footer bg-light border-top-0 px-4 py-3">
                    <button type="button" class="btn btn-light border px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_admin" class="btn btn-success fw-bold px-4 shadow-sm">Create Admin</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="editAdminModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-white border-bottom pt-4 px-4 pb-3">
                <h5 class="modal-title fw-bold text-dark"><i class="bi bi-pencil-square text-primary me-2"></i>Edit Admin Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body p-4">
                    <input type="hidden" name="user_id" id="edit_user_id">
                    <div class="mb-3">
                        <label class="form-label text-dark fw-semibold small">Full Name</label>
                        <input type="text" class="form-control custom-input" name="full_name" id="edit_full_name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-dark fw-semibold small">Username</label>
                        <input type="text" class="form-control custom-input" name="username" id="edit_username" minlength="8" maxlength="16" required>
                    </div>
                    <div class="mb-0">
                        <label class="form-label text-danger fw-semibold small">New Password <span class="fw-normal text-muted">(Optional)</span></label>
                        <input type="password" class="form-control custom-input border-danger-subtle" name="password" placeholder="Leave blank to keep current">
                    </div>
                </div>
                <div class="modal-footer bg-light border-top-0 px-4 py-3">
                    <button type="button" class="btn btn-light border px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="edit_admin" class="btn btn-primary fw-bold px-4 shadow-sm">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<form id="archiveForm" method="POST" style="display: none;">
    <input type="hidden" name="user_id" id="archive_user_id">
    <input type="hidden" name="archive_admin" value="1">
</form>

<form id="deleteForm" method="POST" style="display: none;">
    <input type="hidden" name="user_id" id="delete_user_id">
    <input type="hidden" name="delete_admin" value="1">
</form>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        
        // --- SIDEBAR TOGGLE ---
        const sidebarToggle = document.getElementById('sidebarToggle');
        if(sidebarToggle) {
            sidebarToggle.addEventListener('click', function (e) {
                e.preventDefault();
                document.getElementById('mainContent').classList.toggle('expanded');
            });
        }

        // --- EDIT LOGIC ---
        const editModal = new bootstrap.Modal(document.getElementById('editAdminModal'));
        document.querySelectorAll('.edit-btn').forEach(button => {
            button.addEventListener('click', function() {
                document.getElementById('edit_user_id').value = this.getAttribute('data-id');
                document.getElementById('edit_full_name').value = this.getAttribute('data-name');
                document.getElementById('edit_username').value = this.getAttribute('data-user');
                editModal.show();
            });
        });

        // --- ARCHIVE LOGIC ---
        document.querySelectorAll('.archive-btn').forEach(button => {
            button.addEventListener('click', function() {
                const userId = this.getAttribute('data-id');
                const fullName = this.getAttribute('data-name');
                Swal.fire({
                    title: 'Deactivate Admin?',
                    html: `Are you sure you want to suspend access for <strong>${fullName}</strong>?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#ffc107',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, suspend account'
                }).then((result) => {
                    if (result.isConfirmed) {
                        document.getElementById('archive_user_id').value = userId;
                        document.getElementById('archiveForm').submit();
                    }
                });
            });
        });

        // --- DELETE LOGIC ---
        document.querySelectorAll('.delete-btn').forEach(button => {
            button.addEventListener('click', function() {
                const userId = this.getAttribute('data-id');
                const fullName = this.getAttribute('data-name');
                Swal.fire({
                    title: 'Permanent Deletion',
                    html: `You are about to permanently erase <strong>${fullName}</strong> from the database.<br>This action cannot be undone.`,
                    icon: 'error',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Erase Account'
                }).then((result) => {
                    if (result.isConfirmed) {
                        document.getElementById('delete_user_id').value = userId;
                        document.getElementById('deleteForm').submit();
                    }
                });
            });
        });
    });
</script>
</body>
</html>