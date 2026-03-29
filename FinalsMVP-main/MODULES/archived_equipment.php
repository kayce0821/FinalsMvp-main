<?php
session_start();
include '../INCLUDES/database.php';
$message = "";

// Security check: Only Admin and Staff can manage equipment
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'Staff' && $_SESSION['role'] !== 'Admin')) {
    header("Location: ../PAGES/login.php");
    exit();
}

// DYNAMIC ROUTING & SIDEBAR LOGIC
if ($_SESSION['role'] === 'Admin') {
    $sidebar_file = '../INCLUDES/sidebarAdmin.php';
} else {
    $sidebar_file = '../INCLUDES/sidebarStaff.php';
}

// 1. HANDLE RESTORE EQUIPMENT
if (isset($_POST['restore_item'])) {
    $item_id = $_POST['item_id'];
    
    // Set status back to 'Available'
    $restore_sql = "UPDATE items SET status = 'Available' WHERE item_id = ?";
    if ($stmt = $mysql->prepare($restore_sql)) {
        $stmt->bind_param("i", $item_id);
        if($stmt->execute()){
             $message = "<script>document.addEventListener('DOMContentLoaded', function() { Swal.fire({title: 'Restored!', text: 'Equipment is now Available.', icon: 'success', confirmButtonColor: '#3a5a40'}); });</script>";
        }
        $stmt->close();
    }
}

// 2. HANDLE PERMANENT DELETE
if (isset($_POST['permanent_delete'])) {
    $item_id = $_POST['item_id'];

    $del_sql = "DELETE FROM items WHERE item_id = ?";
    if ($stmt = $mysql->prepare($del_sql)) {
        $stmt->bind_param("i", $item_id);
        if($stmt->execute()){
            $message = "<script>document.addEventListener('DOMContentLoaded', function() { Swal.fire({title: 'Deleted!', text: 'Equipment permanently removed.', icon: 'success', confirmButtonColor: '#3a5a40'}); });</script>";
        }
        $stmt->close();
    }
}

// --- PAGINATION LOGIC START ---
$records_per_page = 10; // Change this number to adjust rows per page
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

// Count total archived equipment for pagination
$count_query = "SELECT COUNT(*) FROM items WHERE status = 'Archived'";
$total_rows = $mysql->query($count_query)->fetch_row()[0];
$total_pages = ceil($total_rows / $records_per_page);
// --- PAGINATION LOGIC END ---

// FETCH ARCHIVED EQUIPMENT (With LIMIT and OFFSET added)
$query = "SELECT item_id, item_name, serial_Number FROM items WHERE status = 'Archived' ORDER BY item_id DESC LIMIT $records_per_page OFFSET $offset";
$result = $mysql->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Archived Equipment - EquipTrack</title>
    
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
        .table thead th {
            background-color: #f8f9fa;
            color: #6c757d;
            font-weight: 600;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #eaedf1;
        }
        .table tbody tr { transition: background-color 0.2s ease; }
        .table tbody tr:hover { background-color: #f8fbfa; }

        /* Buttons */
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
                            <span class="nav-link font-weight-bold text-light p-0" style="font-size: 1.1rem; letter-spacing: 0.5px;">Equipment Archives</span>
                        </li>
                    </ul>

                </div>
            </nav>

            <div class="container-fluid p-4">
                
                <div class="mb-4">
                    <h4 class="mb-0 text-dark fw-bold">Archived Equipment</h4>
                    <p class="text-muted small mb-0 mt-1">Review, restore, or permanently delete inactive system assets.</p>
                </div>

                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-header bg-white card-header-custom border-0 px-4">
                        <h5 class="mb-0 text-dark fw-bold">
                            <i class="bi bi-box-seam text-primary me-2" style="color: var(--brand-color)!important;"></i> Inactive Equipment
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive table-custom-wrapper m-3">
                            <table class="table align-middle mb-0 bg-white">
                                <thead>
                                    <tr class="text-uppercase" style="font-size: 0.80rem;">
                                        <th class="ps-4 py-3 border-0">Equipment Name</th>
                                        <th class="py-3 border-0">Serial Number</th>
                                        <th class="text-center py-3 border-0">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($result && $result->num_rows > 0): ?>
                                        <?php while($row = $result->fetch_assoc()): ?>
                                            <tr>
                                                <td class="ps-4 py-3 fw-bold text-dark" style="font-size: 0.95rem;">
                                                    <?php echo htmlspecialchars($row['item_name']); ?>
                                                </td>
                                                <td class="py-3 text-muted fw-semibold font-monospace" style="font-size: 0.9rem;">
                                                    <?php echo htmlspecialchars($row['serial_Number']); ?>
                                                </td>
                                                <td class="text-center py-3">
                                                    <button class="btn btn-sm btn-outline-success px-3 py-1 fw-bold action-btn restore-btn"
                                                            data-id="<?php echo htmlspecialchars($row['item_id']); ?>"
                                                            data-name="<?php echo htmlspecialchars($row['item_name']); ?>">
                                                       <i class="bi bi-arrow-counterclockwise me-1"></i> Restore
                                                    </button>
                                                    
                                                    <button class="btn btn-sm btn-outline-danger px-3 py-1 ms-2 fw-bold action-btn delete-btn"
                                                            data-id="<?php echo htmlspecialchars($row['item_id']); ?>"
                                                            data-name="<?php echo htmlspecialchars($row['item_name']); ?>">
                                                       <i class="bi bi-trash3 me-1"></i> Delete
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="3" class="text-center py-5 text-muted">
                                                <div class="d-flex flex-column align-items-center">
                                                    <i class="bi bi-archive fs-1 text-light mb-2"></i>
                                                    <span>No archived equipment found.</span>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <?php if ($total_pages > 1): ?>
                    <div class="card-footer bg-white border-top-0 py-3 px-4 d-flex justify-content-end">
                        <nav aria-label="Archived equipment page navigation">
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
        <input type="hidden" name="item_id" id="restore_item_id">
        <input type="hidden" name="restore_item" value="1">
    </form>

    <form id="deleteForm" method="POST" style="display: none;">
        <input type="hidden" name="item_id" id="delete_item_id">
        <input type="hidden" name="permanent_delete" value="1">
    </form>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Sidebar Toggle
            document.getElementById('sidebarToggle').addEventListener('click', function (e) {
                e.preventDefault();
                document.getElementById('mainContent').classList.toggle('expanded');
            });

            // Restore Logic
            const restoreButtons = document.querySelectorAll('.restore-btn');
            restoreButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    const name = this.getAttribute('data-name');

                    Swal.fire({
                        title: 'Restore Equipment?',
                        text: `This will make ${name} available for borrowing again.`,
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonColor: '#198754',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: 'Yes, Restore'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            document.getElementById('restore_item_id').value = id;
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
                        html: `Are you sure you want to permanently erase <strong>${name}</strong>?<br>This action cannot be undone.`,
                        icon: 'error',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: 'Yes, Delete Permanently'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            document.getElementById('delete_item_id').value = id;
                            document.getElementById('deleteForm').submit();
                        }
                    });
                });
            });
        });
    </script> 
</body>
</html>