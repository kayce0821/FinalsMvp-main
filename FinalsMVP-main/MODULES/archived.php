<?php
session_start();
include '../INCLUDES/database.php';
$message = "";

// Security check: Only Admin and Staff can manage equipment
if (!isset($_SESSION['user_id']) || ( $_SESSION['role'] !== 'Admin')) {
    header("Location: ../PAGES/login.php");
    exit();
}

// DYNAMIC ROUTING & SIDEBAR LOGIC
if ($_SESSION['role'] === 'Admin') {
    $sidebar_file = '../INCLUDES/sidebarAdmin.php';
} else {
    $sidebar_file = '../INCLUDES/sidebarStaff.php';
}

// --- 1. HANDLE RESTORE USER ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['restore_user'])) {
    $user_id = $_POST['user_id'];
    // FIXED: Changed 1 to 'Active' to match ENUM
    $sql = "UPDATE user SET status = 'Active' WHERE user_id = ?";
    if ($stmt = $mysql->prepare($sql)) {
        $stmt->bind_param("i", $user_id);
        if ($stmt->execute()) {
            $message = "<script>document.addEventListener('DOMContentLoaded', function() { Swal.fire('Restored!', 'User account has been reactivated.', 'success'); });</script>";
        }
        $stmt->close();
    }
}

// --- 2. HANDLE PERMANENT DELETE ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_user'])) {
    $user_id = $_POST['user_id'];
    $sql = "DELETE FROM user WHERE user_id = ?";
    if ($stmt = $mysql->prepare($sql)) {
        $stmt->bind_param("i", $user_id);
        if ($stmt->execute()) {
            $message = "<script>document.addEventListener('DOMContentLoaded', function() { Swal.fire('Deleted!', 'User has been permanently removed.', 'success'); });</script>";
        }
        $stmt->close();
    }
}

// --- PAGINATION LOGIC START ---
$records_per_page = 10; // Change this number to adjust rows per page
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

// Count total archived users for pagination
// FIXED: Changed 0 to 'Archived' to match ENUM
$count_query = "SELECT COUNT(*) FROM user WHERE status = 'Archived'";
$total_rows = $mysql->query($count_query)->fetch_row()[0];
$total_pages = ceil($total_rows / $records_per_page);
// --- PAGINATION LOGIC END ---

// Fetch Archived Users (With LIMIT and OFFSET added)
// FIXED: Changed query to fetch email, and concatenate first_name and last_name
$sql = "SELECT user_id, email, CONCAT(first_name, ' ', last_name) AS full_name, role 
        FROM user 
        WHERE status = 'Archived' 
        ORDER BY user_id DESC 
        LIMIT $records_per_page OFFSET $offset";
$result = $mysql->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Archived Users - EquipTrack</title>
    
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
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
        
        /* Header & Theme */
        .main-header { 
            background-color: var(--brand-color); 
            padding: 12px 20px; 
        }

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
        /* Fix for scrollable table inner border-radius */
        .table-custom-wrapper .table-responsive {
            border-radius: 1rem;
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
        
        /* Badges & Buttons */
        .badge-role { 
            font-size: 0.80em; 
            padding: 0.5em 1em; 
            min-width: 85px; 
            letter-spacing: 0.5px; 
            font-weight: 600;
        }
        .action-btn {
            border-radius: 0.5rem;
            transition: all 0.2s ease;
        }
        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1) !important;
        }

        /* Pagination custom colors */
        .pagination-custom .page-item .page-link {
            border: none;
            color: #6c757d;
            border-radius: 0.5rem;
            margin: 0 0.2rem;
            transition: all 0.2s;
        }
        .pagination-custom .page-item.active .page-link { 
            background-color: var(--brand-color); 
            color: white; 
            box-shadow: 0 4px 6px rgba(58, 90, 64, 0.2);
        }
        .pagination-custom .page-item .page-link:hover:not(.active) {
            background-color: #eaedf1;
            color: var(--brand-color);
        }

        /* Responsive Mobile Adjustments */
        @media (max-width: 768px) {
            .content-wrapper, .content-wrapper.expanded { 
                width: 100%; 
            }
            
            /* Adjust padding for mobile screen */
            .container-fluid.p-4 {
                padding: 1.5rem 1rem !important;
            }

            /* Prevent table data from squishing, enable horizontal scroll */
            .table th, .table td {
                white-space: nowrap;
                padding: 0.75rem 0.5rem !important;
            }

            /* Better mobile pagination wrapping */
            .pagination-custom {
                flex-wrap: wrap;
                justify-content: center;
                gap: 0.25rem;
            }
            
            .card-footer.d-flex {
                justify-content: center !important;
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
                            <span class="nav-link font-weight-bold text-light p-0" style="font-size: 1.1rem; letter-spacing: 0.5px;">User Archives</span>
                        </li>
                    </ul>
                </div>
            </nav>

            <div class="container-fluid p-4">
                
                <div class="mb-4">
                    <h4 class="mb-0 text-dark fw-bold">Archived Accounts</h4>
                    <p class="text-muted small mb-0 mt-1">Review, restore, or permanently delete inactive user accounts.</p>
                </div>

                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-header bg-white card-header-custom border-0 px-4">
                        <h5 class="mb-0 text-dark fw-bold">
                            <i class="bi bi-person-dash text-primary me-2" style="color: var(--brand-color)!important;"></i> Inactive Users
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-custom-wrapper m-3">
                            <div class="table-responsive">
                                <table class="table align-middle mb-0 bg-white">
                                    <thead>
                                        <tr class="text-uppercase" style="font-size: 0.80rem;">
                                            <th class="ps-4 py-3 border-0">Full Name</th>
                                            <th class="py-3 border-0">Email Address</th>
                                            <th class="text-center py-3 border-0">Role</th>
                                            <th class="text-center py-3 border-0">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($result && $result->num_rows > 0): ?>
                                            <?php while($row = $result->fetch_assoc()): ?>
                                                <tr>
                                                    <td class="ps-4 py-3 fw-bold text-dark" style="font-size: 0.95rem;">
                                                        <?php echo htmlspecialchars($row['full_name'] ?: 'No Name Set'); ?>
                                                    </td>
                                                    <td class="py-3 text-muted fw-semibold font-monospace" style="font-size: 0.9rem;">
                                                        <?php echo htmlspecialchars($row['email'] ?: 'No Email'); ?>
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
                                                        <button class="btn btn-sm btn-outline-success px-3 py-1 fw-bold action-btn restore-btn"
                                                                data-id="<?php echo htmlspecialchars($row['user_id']); ?>"
                                                                data-name="<?php echo htmlspecialchars($row['full_name']); ?>">
                                                           <i class="bi bi-arrow-counterclockwise me-1"></i> 
                                                        </button>
                                                        
                                                        <button class="btn btn-sm btn-outline-danger px-3 py-1 ms-2 fw-bold action-btn delete-btn"
                                                                data-id="<?php echo htmlspecialchars($row['user_id']); ?>"
                                                                data-name="<?php echo htmlspecialchars($row['full_name']); ?>">
                                                           <i class="bi bi-trash3 me-1"></i> 
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="4" class="text-center py-5 text-muted">
                                                    <div class="d-flex flex-column align-items-center">
                                                        <i class="bi bi-person-x fs-1 opacity-50 mb-2"></i>
                                                        <span>No archived users found.</span>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <?php if ($total_pages > 1): ?>
                    <div class="card-footer bg-white border-top-0 py-3 px-4 d-flex justify-content-end">
                        <nav aria-label="Archived users page navigation">
                            <ul class="pagination pagination-custom mb-0">
                                <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                                    <a class="page-link shadow-sm" href="?page=<?php echo $page - 1; ?>"><i class="bi bi-chevron-left"></i></a>
                                </li>
                                
                                <?php for($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                                        <a class="page-link shadow-sm fw-bold" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                    </li>
                                <?php endfor; ?>
                                
                                <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                                    <a class="page-link shadow-sm" href="?page=<?php echo $page + 1; ?>"><i class="bi bi-chevron-right"></i></a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if(file_exists('../INCLUDES/footer.php')) include '../INCLUDES/footer.php'; ?>
        </div> 
    </div> 

    <form id="restoreForm" method="POST" style="display: none;">
        <input type="hidden" name="user_id" id="restore_user_id">
        <input type="hidden" name="restore_user" value="1">
    </form>

    <form id="deleteForm" method="POST" style="display: none;">
        <input type="hidden" name="user_id" id="delete_user_id">
        <input type="hidden" name="delete_user" value="1">
    </form>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            
            // Sidebar Toggle Logic
            const sidebarToggle = document.getElementById('sidebarToggle');
            if (sidebarToggle) {
                sidebarToggle.addEventListener('click', function (e) {
                    e.preventDefault();
                    document.getElementById('mainContent').classList.toggle('expanded');
                });
            }

            // Restore Logic
            const restoreButtons = document.querySelectorAll('.restore-btn');
            restoreButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    const name = this.getAttribute('data-name');

                    Swal.fire({
                        title: 'Restore User?',
                        text: `Reactivate account for ${name}?`,
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonColor: '#198754',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: 'Yes, Restore'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            document.getElementById('restore_user_id').value = id;
                            document.getElementById('restoreForm').submit();
                        }
                    });
                });
            });

            // Permanent Delete Logic
            const deleteButtons = document.querySelectorAll('.delete-btn');
            deleteButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    const name = this.getAttribute('data-name');

                    Swal.fire({
                        title: 'Permanent Delete?',
                        html: `Are you sure you want to <strong>delete</strong> ${name}?<br>This cannot be undone!`,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#dc3545',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: 'Delete Permanently'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            document.getElementById('delete_user_id').value = id;
                            document.getElementById('deleteForm').submit();
                        }
                    });
                });
            });
        });
    </script> 
</body>
</html>