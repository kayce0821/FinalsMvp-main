<?php
include '../INCLUDES/database.php';
session_start();

// Redirect to login if user isn't logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: login.php");
    exit();
}

// 1. Fetch Live Counts for the top cards
$available_count = $mysql->query("SELECT COUNT(*) FROM items WHERE status = 'Available'")->fetch_row()[0];
$borrowed_count  = $mysql->query("SELECT COUNT(*) FROM items WHERE status = 'Borrowed'")->fetch_row()[0];
// CHANGED: Now fetching the total count of all items
$total_count     = $mysql->query("SELECT COUNT(*) FROM items")->fetch_row()[0];

// 2. Fetch Active Transactions
$query = "SELECT t.transaction_id, t.student_id, i.item_name, i.item_id, t.borrow_date 
          FROM transactions t
          JOIN items i ON t.item_id = i.item_id
          WHERE t.transaction_status = 'Active'
          ORDER BY t.borrow_date DESC";

$result = $mysql->query($query);
$transactions = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $transactions[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EquipTrack | Admin Dashboard</title>
    
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
        .main-header { background-color: var(--brand-color); padding: 12px 20px; }

        /* --- UI Enhancements --- */
        
        /* Stat Cards */
        .stat-card {
            border-radius: 1rem;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 0.5rem 1.5rem rgba(0,0,0,0.08) !important;
        }
        .icon-box {
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Custom Buttons */
        .btn-return {
            border-color: var(--brand-color);
            color: var(--brand-color);
            border-radius: 50rem; /* Pill shape */
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-return:hover {
            background-color: var(--brand-color);
            color: white;
            box-shadow: 0 4px 6px rgba(58, 90, 64, 0.2);
        }

        /* Table Styling */
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

        /* Card Header Layout */
        .card-header-custom {
            border-bottom: 1px solid #eaedf1 !important;
            padding-bottom: 1.25rem !important;
            padding-top: 1.25rem !important;
        }

        @media (max-width: 768px) {
            .content-wrapper, .content-wrapper.expanded { width: 100%; }
        }
    </style>
</head>
<body>

    <div class="wrapper">
        <?php include '../INCLUDES/sidebarAdmin.php'; ?>

        <div class="content-wrapper" id="mainContent">
            
            <nav class="main-header navbar navbar-expand navbar-dark border-bottom-0 shadow-sm w-100 m-0">
                <div class="container-fluid">
                    <ul class="navbar-nav align-items-center">
                        <li class="nav-item">
                            <a class="nav-link" href="#" id="sidebarToggle" role="button"><i class="fas fa-bars"></i></a>
                        </li>
                        <li class="nav-item d-none d-sm-inline-block ms-2">
                            <span class="nav-link font-weight-bold text-light p-0" style="font-size: 1.1rem; letter-spacing: 0.5px;">Admin Dashboard</span>
                        </li>
                    </ul>
                </div>
            </nav>

            <div class="container-fluid p-4">
                
                <div class="row mb-4">
                    <div class="col-md-4 mb-3 mb-md-0">
                        <div class="card stat-card border-0 shadow-sm h-100">
                            <div class="card-body d-flex align-items-center p-4">
                                <div class="flex-shrink-0 bg-success-subtle icon-box rounded-circle text-success me-4">
                                    <i class="bi bi-check-circle-fill fs-3"></i>
                                </div>
                                <div>
                                    <h6 class="mb-1 text-muted text-uppercase small fw-bold tracking-wide">Available Items</h6>
                                    <h2 class="mb-0 fw-bold text-dark"><?php echo $available_count; ?></h2>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3 mb-md-0">
                        <div class="card stat-card border-0 shadow-sm h-100">
                            <div class="card-body d-flex align-items-center p-4">
                                <div class="flex-shrink-0 bg-danger-subtle icon-box rounded-circle text-danger me-4">
                                    <i class="bi bi-cart-x-fill fs-3"></i>
                                </div>
                                <div>
                                    <h6 class="mb-1 text-muted text-uppercase small fw-bold tracking-wide">Borrowed Items</h6>
                                    <h2 class="mb-0 fw-bold text-dark"><?php echo $borrowed_count; ?></h2>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card stat-card border-0 shadow-sm h-100">
                            <div class="card-body d-flex align-items-center p-4">
                                <div class="flex-shrink-0 bg-primary-subtle icon-box rounded-circle text-primary me-4">
                                    <i class="bi bi-boxes fs-3"></i>
                                </div>
                                <div>
                                    <h6 class="mb-1 text-muted text-uppercase small fw-bold tracking-wide">Total Equipments</h6>
                                    <h2 class="mb-0 fw-bold text-dark"><?php echo $total_count; ?></h2>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-header bg-white card-header-custom border-0 px-4">
                        <h5 class="mb-0 text-dark fw-bold">
                            <i class="bi bi-list-task text-primary me-2" style="color: var(--brand-color)!important;"></i> Currently Borrowed Equipment
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive table-custom-wrapper m-3">
                            <table class="table align-middle mb-0 bg-white">
                                <thead>
                                    <tr class="text-uppercase" style="font-size: 0.80rem;">
                                        <th class="ps-4 py-3 border-0">Student ID</th>
                                        <th class="py-3 border-0">Equipment</th>
                                        <th class="py-3 border-0">Borrow Date</th>
                                        <th class="text-center py-3 border-0">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($transactions as $row): ?>
                                    <tr>
                                        <td class="ps-4 py-3 fw-bold text-dark">
                                            <span class="badge bg-light text-dark border px-2 py-1"><?php echo htmlspecialchars($row['student_id']); ?></span>
                                        </td>
                                        
                                        <td class="py-3 text-dark fw-semibold">
                                            <?php echo htmlspecialchars($row['item_name']); ?> 
                                            <span class="text-muted fw-normal fs-6 ms-1">(#<?php echo htmlspecialchars($row['item_id']); ?>)</span>
                                        </td>
                                        
                                        <td class="py-3 text-muted small">
                                            <i class="bi bi-clock text-secondary me-1"></i> <?php echo date('M d, Y', strtotime($row['borrow_date'])); ?> 
                                            <span class="text-black-50 ms-1"><?php echo date('h:i A', strtotime($row['borrow_date'])); ?></span>
                                        </td>
                                        <td class="text-center py-3">
                                            <a href="../ACTIONS/process_return.php?tid=<?php echo $row['transaction_id']; ?>" class="btn btn-sm btn-outline-primary border-1 px-4 btn-return return-btn">Return Item</a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    
                                    <?php if (empty($transactions)): ?>
                                    <tr>
                                        <td colspan="4" class="text-center py-5 text-muted">
                                            <div class="d-flex flex-column align-items-center">
                                                <i class="bi bi-inbox fs-1 opacity-50 mb-2"></i>
                                                <span>No active borrowing transactions found.</span>
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
            
            <?php include '../INCLUDES/footer.php'; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Sidebar Toggle
        document.getElementById('sidebarToggle').addEventListener('click', function () {
            document.getElementById('mainContent').classList.toggle('expanded');
        });
    </script>

    <?php if (isset($_GET['status'])): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const status = "<?php echo $_GET['status']; ?>";
                
                if (status === 'success') {
                    Swal.fire({
                        title: 'Success!',
                        text: 'Equipment successfully borrowed.',
                        icon: 'success',
                        confirmButtonColor: '#3a5a40'
                    });
                } else if (status === 'unavailable') {
                    Swal.fire({
                        title: 'Item Not Available',
                        text: 'This equipment is currently Borrowed, Defective, or Lost.',
                        icon: 'warning',
                        confirmButtonColor: '#f39c12'
                    });
                } else if (status === 'error') {
                    Swal.fire({
                        title: 'Notice',
                        text: 'Item does not exist in the database.',
                        icon: 'error',
                        confirmButtonColor: '#d33'
                    });
                } else if (status === 'returned') {
                    Swal.fire({
                        title: 'Returned!',
                        text: 'Equipment has been returned successfully.',
                        icon: 'success',
                        confirmButtonColor: '#3a5a40'
                    });
                }
                
                // Clean the URL
                window.history.replaceState(null, null, window.location.pathname);
            });
            
    // --- SWEETALERT CONFIRMATIONS ---

    // 1. Borrowing Confirmation (if form exists on this page)
    const borrowForm = document.getElementById('borrowForm');
    if (borrowForm) {
        borrowForm.addEventListener('submit', function(e) {
            e.preventDefault(); // Stop the form from submitting instantly
            
            const studentId = this.querySelector('input[name="student_id"]').value;
            const itemId = this.querySelector('input[name="item_id"]').value;

            Swal.fire({
                title: 'Process Borrowing?',
                html: `Assign Equipment ID <strong>${itemId}</strong> to Student <strong>${studentId}</strong>?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3a5a40',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, Process it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    borrowForm.submit(); // Submit the form to the backend
                }
            });
        });
    }

    // 2. Return Confirmation
    const returnButtons = document.querySelectorAll('.return-btn');
    returnButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault(); // Stop the link from redirecting instantly
            const url = this.getAttribute('href');

            Swal.fire({
                title: 'Confirm Return',
                text: 'Has this equipment been returned in good condition?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3a5a40',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, Return it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = url; // Proceed to the backend
                }
            });
        });
    });
        </script>
    <?php endif; ?>
</body>
</html>