<?php
include '../INCLUDES/database.php';
session_start();

// Redirect to login if user isn't logged in
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'Staff' && $_SESSION['role'] !== 'Admin')) {
    header("Location: login.php");
    exit();
}

$sidebar_file = ($_SESSION['role'] === 'Admin') ? '../INCLUDES/sidebarAdmin.php' : '../INCLUDES/sidebarStaff.php';

// --- PAGINATION LOGIC START ---
$records_per_page = 10; // Change this number to adjust rows per page
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

// Count total pending requests for pagination
$count_query = "SELECT COUNT(*) FROM requests WHERE request_status = 'Pending'";
$total_rows = $mysql->query($count_query)->fetch_row()[0];
$total_pages = ceil($total_rows / $records_per_page);
// --- PAGINATION LOGIC END ---

// Fetch Pending Requests (With LIMIT and OFFSET added)
$query = "SELECT r.request_id, r.student_id, s.full_name, i.item_name, i.item_id, r.request_date 
          FROM requests r
          JOIN students s ON r.student_id = s.student_id
          JOIN items i ON r.item_id = i.item_id
          WHERE r.request_status = 'Pending'
          ORDER BY r.request_date ASC
          LIMIT $records_per_page OFFSET $offset";

$result = $mysql->query($query);
$requests = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $requests[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EquipTrack | Manage Requests</title>
    
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
            overflow-y: auto; /* Allow content to scroll */
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
        .table tbody tr {
            transition: background-color 0.2s ease;
        }
        .table tbody tr:hover { 
            background-color: #f8fbfa; 
        }

        /* Action Buttons */
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

        @media (max-width: 768px) { .content-wrapper, .content-wrapper.expanded { width: 100%; } }
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
                            <span class="nav-link font-weight-bold text-light p-0" style="font-size: 1.1rem; letter-spacing: 0.5px;">Equipment Requests</span>
                        </li>
                    </ul>
                </div>
            </nav>

            <div class="container-fluid p-4">
                
                <div class="mb-4">
                    <h4 class="mb-0 text-dark fw-bold">Equipment Requests</h4>
                    <p class="text-muted small mb-0 mt-1">Review and approve student borrowing requests.</p>
                </div>

                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-header bg-white card-header-custom border-0 px-4">
                        <h5 class="mb-0 text-dark fw-bold">
                            <i class="bi bi-inbox-fill text-primary me-2" style="color: var(--brand-color)!important;"></i> Pending Approvals
                        </h5>
                    </div>
                    
                    <div class="card-body p-0">
                        <div class="table-responsive table-custom-wrapper m-3">
                            <table class="table align-middle mb-0 bg-white">
                                <thead>
                                    <tr class="text-uppercase" style="font-size: 0.80rem;">
                                        <th class="ps-4 py-3 border-0">Date Requested</th>
                                        <th class="py-3 border-0">Student Info</th>
                                        <th class="py-3 border-0">Equipment</th>
                                        <th class="text-center py-3 border-0">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($requests)): ?>
                                        <?php foreach ($requests as $row): ?>
                                            <tr>
                                                <td class="ps-4 py-3">
                                                    <div class="text-dark fw-bold" style="font-size: 0.95rem;">
                                                        <?php echo date('M d, Y', strtotime($row['request_date'])); ?>
                                                    </div>
                                                    <div class="text-muted small">
                                                        <i class="bi bi-clock me-1 text-secondary"></i><?php echo date('h:i A', strtotime($row['request_date'])); ?>
                                                    </div>
                                                </td>
                                                <td class="py-3">
                                                    <div class="fw-bold text-dark" style="font-size: 0.95rem;"><?php echo htmlspecialchars($row['full_name']); ?></div>
                                                    <span class="badge bg-light text-secondary border px-2 py-1 mt-1 font-monospace">ID: <?php echo htmlspecialchars($row['student_id']); ?></span>
                                                </td>
                                                <td class="py-3">
                                                    <span class="badge bg-light text-dark border py-2 px-3 rounded-pill fw-semibold shadow-sm">
                                                        <i class="bi bi-box-seam text-primary me-2" style="color: var(--brand-color)!important;"></i> <?php echo htmlspecialchars($row['item_name']); ?>
                                                    </span>
                                                </td>
                                                <td class="text-center py-3">
                                                    <a href="../ACTIONS/process_request.php?action=approve&id=<?php echo $row['request_id']; ?>" 
                                                       class="btn btn-sm btn-success px-3 py-1 fw-bold approve-btn shadow-sm action-btn"
                                                       data-student="<?php echo htmlspecialchars($row['full_name']); ?>"
                                                       data-item="<?php echo htmlspecialchars($row['item_name']); ?>">
                                                        <i class="bi bi-check-lg me-1"></i> Approve
                                                    </a>
                                                    
                                                    <a href="../ACTIONS/process_request.php?action=reject&id=<?php echo $row['request_id']; ?>" 
                                                       class="btn btn-sm btn-outline-danger px-3 py-1 ms-2 fw-bold reject-btn action-btn bg-white"
                                                       data-student="<?php echo htmlspecialchars($row['full_name']); ?>"
                                                       data-item="<?php echo htmlspecialchars($row['item_name']); ?>">
                                                        <i class="bi bi-x-lg me-1"></i> Reject
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="text-center py-5 text-muted">
                                                <div class="d-flex flex-column align-items-center">
                                                    <i class="bi bi-check2-circle fs-1 opacity-50 mb-2"></i>
                                                    <span>No pending requests at the moment. You're all caught up!</span>
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
                        <nav aria-label="Page navigation">
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Sidebar Toggle
            document.getElementById('sidebarToggle').addEventListener('click', function (e) {
                e.preventDefault();
                document.getElementById('mainContent').classList.toggle('expanded');
            });

            // --- APPROVE LOGIC ---
            const approveButtons = document.querySelectorAll('.approve-btn');
            approveButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault(); // Stop instant redirect
                    const url = this.getAttribute('href');
                    const student = this.getAttribute('data-student');
                    const item = this.getAttribute('data-item');

                    Swal.fire({
                        title: 'Approve Request?',
                        html: `Allow <strong>${student}</strong> to borrow the <strong>${item}</strong>?`,
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonColor: '#198754', // Success Green
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: 'Yes, Approve'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location.href = url; // Proceed to backend
                        }
                    });
                });
            });

            // --- REJECT LOGIC ---
            const rejectButtons = document.querySelectorAll('.reject-btn');
            rejectButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault(); // Stop instant redirect
                    const url = this.getAttribute('href');
                    const student = this.getAttribute('data-student');
                    const item = this.getAttribute('data-item');

                    Swal.fire({
                        title: 'Reject Request?',
                        html: `Are you sure you want to decline the request from <strong>${student}</strong> for the <strong>${item}</strong>?`,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#dc3545', // Danger Red
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: 'Yes, Reject'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location.href = url; // Proceed to backend
                        }
                    });
                });
            });
        });
    </script>

    <?php if (isset($_GET['status'])): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                <?php if ($_GET['status'] == 'approved'): ?>
                    Swal.fire('Approved!', 'The request has been approved and moved to active transactions.', 'success');
                <?php elseif ($_GET['status'] == 'rejected'): ?>
                    Swal.fire('Rejected', 'The student\'s request has been rejected.', 'info');
                <?php elseif ($_GET['status'] == 'unavailable'): ?>
                    Swal.fire('Cannot Approve', 'This item is currently out of stock or defective.', 'error');
                <?php endif; ?>
                
                // Clear the status from the URL but preserve the page number
                const url = new URL(window.location.href);
                url.searchParams.delete('status');
                window.history.replaceState(null, null, url.toString() || window.location.pathname);
            });
        </script>
    <?php endif; ?>

</body>
</html>