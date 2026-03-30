<?php
include 'database.php';
$message = "";

// ADD USER
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_user'])) {
    $full_name = $_POST['full_name'];
    $username = $_POST['username'];
    $role = $_POST['role'];
    $password_raw = $_POST['password'];
    
    if (strlen($username) < 8 || strlen($username) > 16) {
        $message = "<script>document.addEventListener('DOMContentLoaded', function() { Swal.fire('Error', 'Username must be 8-16 characters.', 'error'); });</script>";
    } else {
        $password = password_hash($password_raw, PASSWORD_DEFAULT);
        
        // FIX #1: Added the 'status' column and set the default to 'Active' for new users
        $sql = "INSERT INTO user (full_name, username, password, role, status) VALUES (?, ?, ?, ?, 'Active')";
        if ($stmt = $mysql->prepare($sql)) {
            $stmt->bind_param("ssss", $full_name, $username, $password, $role);
            if ($stmt->execute()) {
                $message = "<script>document.addEventListener('DOMContentLoaded', function() { Swal.fire('Success!', 'New user added successfully.', 'success'); });</script>";
            } else {
                $message = "<script>document.addEventListener('DOMContentLoaded', function() { Swal.fire('Error', 'Username may already exist.', 'error'); });</script>";
            }
            $stmt->close();
        }
    }
}

// ARCHIVE USER
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['archive_user'])) {
    $user_id = $_POST['user_id'];
    
    // FIX #2: Update the 'status' column to 'Archived' instead of using is_archived
    $sql = "UPDATE user SET status = 'Archived' WHERE user_id = ?";
    if ($stmt = $mysql->prepare($sql)) {
        $stmt->bind_param("i", $user_id);
        if ($stmt->execute()) {
            $message = "<script>document.addEventListener('DOMContentLoaded', function() { Swal.fire('Archived!', 'User has been moved to the archive.', 'success'); });</script>";
        } else {
            $message = "<script>document.addEventListener('DOMContentLoaded', function() { Swal.fire('Error', 'Failed to archive user.', 'error'); });</script>";
        }
        $stmt->close();
    }
}

// DELETE USER
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_user'])) {
    $user_id = $_POST['user_id'];
    $sql = "DELETE FROM user WHERE user_id = ?";
    if ($stmt = $mysql->prepare($sql)) {
        $stmt->bind_param("i", $user_id);
        if ($stmt->execute()) {
            $message = "<script>document.addEventListener('DOMContentLoaded', function() { Swal.fire('Deleted!', 'User has been permanently removed.', 'success'); });</script>";
        } else {
            $message = "<script>document.addEventListener('DOMContentLoaded', function() { Swal.fire('Error', 'Failed to delete user.', 'error'); });</script>";
        }
        $stmt->close();
    }
}

// FETCH ACTIVE USERS ONLY
// FIX #3: Fetch users where status is 'Active' instead of checking is_archived
$query = "SELECT user_id, full_name, username, role FROM user WHERE status = 'Active' ORDER BY user_id DESC";
$result = $mysql->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - EquipTrack</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        body { background-color: #f8f9fa; padding-top: 40px; }
        .container { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        .table-hover tbody tr:hover { background-color: #f1f3f5; }
        .badge-role { font-size: 0.85em; padding: 0.5em 0.8em; min-width: 80px; letter-spacing: 0.5px; }
        .action-btns .btn { margin: 0 2px; }
    </style>
</head>
<body class="d-flex flex-column min-vh-100" style="background-color: #f8f9fa; padding-top: 40px;">
<?php echo $message; ?>

<div class="container flex mb-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-0 text-dark">Manage Staff & Students</h2>
            <p class="text-muted small mb-0">System Administrator Access</p>
        </div>
        <div>
            <a href="index.php" class="btn btn-outline-secondary me-2">Back to Dashboard</a>
            <button type="button" class="btn btn-success fw-bold" data-bs-toggle="modal" data-bs-target="#addUserModal">
                <i class="bi bi-person-plus-fill me-1"></i> Add New User
            </button>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover table-bordered align-middle">
            <thead class="table-light">
                <tr class="text-center">
                    <th>User ID</th>
                    <th>Full Name</th>
                    <th>Username</th>
                    <th class="text-center">Role</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['user_id']); ?></td>
                            <td><strong><?php echo htmlspecialchars($row['full_name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($row['username']); ?></td>
                            
                            <td class="text-center">
                                <?php 
                                    $role = $row['role'];
                                    if ($role === 'Admin') echo '<span class="badge bg-danger badge-role text-uppercase">Admin</span>';
                                    elseif ($role === 'Staff') echo '<span class="badge bg-primary badge-role text-uppercase">Staff</span>';
                                    elseif ($role === 'Student') echo '<span class="badge bg-success badge-role text-uppercase">Student</span>';
                                    else echo '<span class="badge bg-secondary badge-role">Unknown</span>';
                                ?>
                            </td>
                            
                            <td class="text-center action-btns">
                                <button class="btn btn-warning btn-sm archive-btn text-dark fw-semibold" 
                                        data-id="<?php echo htmlspecialchars($row['user_id']); ?>"
                                        data-name="<?php echo htmlspecialchars($row['full_name']); ?>"
                                        title="Archive User">
                                    <i class="bi bi-archive-fill"></i> Archive
                                </button>
                                
                                <button class="btn btn-danger btn-sm delete-btn fw-semibold" 
                                        data-id="<?php echo htmlspecialchars($row['user_id']); ?>" 
                                        data-name="<?php echo htmlspecialchars($row['full_name']); ?>"
                                        title="Permanently Delete">
                                    <i class="bi bi-trash3-fill"></i> Delete
                                </button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="5" class="text-center text-muted py-4">No active users found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        </div>
</div>

<div class="modal fade" id="addUserModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title"><i class="bi bi-person-plus-fill me-2"></i>Add New User</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST">
          <div class="modal-body">
            <div class="mb-3">
                <label class="form-label text-muted small">Full Name</label>
                <input type="text" class="form-control" name="full_name" required>
            </div>
            <div class="mb-3">
                <label class="form-label text-muted small">Username</label>
                <input type="text" class="form-control" name="username" minlength="8" maxlength="16" placeholder="8-16 characters" required>
            </div>
            <div class="mb-3">
                <label class="form-label text-muted small">Temporary Password</label>
                <input type="password" class="form-control" name="password" required>
            </div>
            <div class="mb-3">
                <label class="form-label text-muted small">Role</label>
                <select class="form-select" name="role" required>
                    <option value="Student">Student</option>
                    <option value="Staff">Staff</option>
                    <option value="Admin">Admin</option>
                </select>
            </div>
          </div>
          <div class="modal-footer bg-light">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" name="add_user" class="btn btn-success fw-bold">Save User</button>
          </div>
          
      </form>
    </div>
  </div>
</div>


<form id="archiveForm" method="POST" style="display: none;">
    <input type="hidden" name="user_id" id="archive_user_id">
    <input type="hidden" name="archive_user" value="1">
</form>

<form id="deleteForm" method="POST" style="display: none;">
    <input type="hidden" name="user_id" id="delete_user_id">
    <input type="hidden" name="delete_user" value="1">
</form>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {

        const archiveButtons = document.querySelectorAll('.archive-btn');
        archiveButtons.forEach(button => {
            button.addEventListener('click', function() {
                const userId = this.getAttribute('data-id');
                const fullName = this.getAttribute('data-name');

                Swal.fire({
                    title: 'Archive User?',
                    html: `Are you sure you want to archive <strong>${fullName}</strong>?<br>They will be hidden from this active list.`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#ffc107',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, archive them!',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        document.getElementById('archive_user_id').value = userId;
                        document.getElementById('archiveForm').submit();
                    }
                });
            });
        });

        const deleteButtons = document.querySelectorAll('.delete-btn');
        deleteButtons.forEach(button => {
            button.addEventListener('click', function() {
                const userId = this.getAttribute('data-id');
                const fullName = this.getAttribute('data-name');

                Swal.fire({
                    title: 'Permanently Delete User?',
                    html: `Are you sure you want to completely remove <strong>${fullName}</strong>'s access?<br>This action cannot be undone.`,
                    icon: 'error',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, permanently delete!'
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

          <?php include 'footer.php'; ?>
                    
</body>
</html>