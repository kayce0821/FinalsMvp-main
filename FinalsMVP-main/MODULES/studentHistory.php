<?php
session_start();
include '../INCLUDES/database.php';

// Security check: ONLY Students
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Student') {
    header("Location: login.php");
    exit();
}

$sidebar_file = '../INCLUDES/sidebarStudent.php';

// 1. Get the Student's actual ID from the students table by linking via email
$user_id = $_SESSION['user_id'];
$student_id = null;

$stmt_student = $mysql->prepare("
    SELECT s.student_id 
    FROM user u 
    JOIN students s ON u.email = s.email 
    WHERE u.user_id = ?
");
$stmt_student->bind_param("i", $user_id);
$stmt_student->execute();
$result_student = $stmt_student->get_result();

if ($row = $result_student->fetch_assoc()) {
    $student_id = $row['student_id'];
}
$stmt_student->close();

// 2. Fetch the Student's Transaction History
$transactions = [];
if ($student_id) {
    $query = "
        SELECT 
            t.transaction_id, 
            i.item_name, 
            i.item_id, 
            t.borrow_date, 
            t.expected_return_time, 
            t.transaction_status,
            t.return_condition, /* NEW: Fetching the condition */
            CONCAT(u1.first_name, ' ', u1.last_name) AS issued_by_name,
            CONCAT(u2.first_name, ' ', u2.last_name) AS received_by_name
        FROM transactions t
        JOIN items i ON t.item_id = i.item_id
        LEFT JOIN user u1 ON t.issued_by = u1.user_id   
        LEFT JOIN user u2 ON t.received_by = u2.user_id 
        WHERE t.student_id = ?
        ORDER BY t.borrow_date DESC
    ";
    
    $stmt_trans = $mysql->prepare($query);
    $stmt_trans->bind_param("s", $student_id);
    $stmt_trans->execute();
    $result_trans = $stmt_trans->get_result();
    
    if ($result_trans && $result_trans->num_rows > 0) {
        while ($row = $result_trans->fetch_assoc()) {
            $transactions[] = $row;
        }
    }
    $stmt_trans->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EquipTrack | My History</title>
    
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
            overflow: hidden; 
            font-family: 'Source Sans Pro', sans-serif; 
            color: #333;
        }
        
        .wrapper { display: flex; width: 100%; height: 100vh; position: relative; overflow: hidden; }
        .content-wrapper { flex-grow: 1; display: flex; flex-direction: column; width: calc(100% - 250px); height: 100vh; overflow-y: auto; overflow-x: hidden; transition: width 0.3s ease; }
        .content-wrapper.expanded { width: calc(100% - 70px); }
        .main-header { background-color: var(--brand-color); padding: 12px 20px; }
        
        .card-header-custom { border-bottom: 1px solid #eaedf1 !important; padding-bottom: 1.25rem !important; padding-top: 1.25rem !important; }
        .table-custom-wrapper { border-radius: 1rem; overflow: hidden; border: 1px solid #eaedf1; }
        /* Fix for scrollable table inner border-radius */
        .table-custom-wrapper .table-responsive { border-radius: 1rem; }
        .table thead th { background-color: #f8f9fa; color: #6c757d; font-weight: 600; letter-spacing: 0.5px; border-bottom: 2px solid #eaedf1; }
        .table tbody tr { transition: background-color 0.2s ease; }
        .table tbody tr:hover { background-color: #f8fbfa; }

        .staff-badge { font-size: 0.80rem; padding: 0.4em 0.6em; border-radius: 0.4rem; display: inline-block; border: 1px solid #dee2e6; background-color: #fff; }

        /* Responsive Mobile Adjustments */
        @media (max-width: 768px) { 
            .content-wrapper, .content-wrapper.expanded { 
                width: 100%; 
            } 
            .container-fluid.p-4 {
                padding: 1.5rem 1rem !important;
            }
            .table th, .table td {
                white-space: nowrap;
                padding: 0.75rem 0.5rem !important;
            }
        }
    </style>
</head>
<body>

    <div class="wrapper">
        <?php include '../INCLUDES/sidebarStudent.php'; ?>

        <div class="content-wrapper" id="mainContent">
            
            <nav class="main-header navbar navbar-expand navbar-dark border-bottom-0 shadow-sm w-100 m-0">
                <div class="container-fluid">
                    <ul class="navbar-nav align-items-center">
                        <li class="nav-item">
                            <a class="nav-link" href="#" id="sidebarToggle" role="button"><i class="fas fa-bars"></i></a>
                        </li>
                        <li class="nav-item d-none d-sm-inline-block ms-2">
                            <span class="nav-link font-weight-bold text-light p-0" style="font-size: 1.1rem; letter-spacing: 0.5px;">My Borrowing History</span>
                        </li>
                    </ul>
                </div>
            </nav>

            <div class="container-fluid p-4">
                
                <div class="mb-4">
                    <h4 class="mb-0 text-dark fw-bold">My Transactions</h4>
                    <p class="text-muted small mb-0 mt-1">Review all the equipment you have borrowed and returned.</p>
                </div>

                <div class="card border-0 shadow-sm rounded-4 mb-5">
                    <div class="card-header bg-white card-header-custom border-0 px-4">
                        <h5 class="mb-0 text-dark fw-bold d-flex align-items-center">
                            <i class="bi bi-clock-history text-primary me-2" style="color: var(--brand-color)!important;"></i> Transaction Masterlist
                        </h5>
                    </div>
                    
                    <div class="card-body p-0">
                        <div class="table-custom-wrapper m-3">
                            <div class="table-responsive">
                                <table class="table align-middle mb-0 bg-white">
                                    <thead>
                                        <tr class="text-uppercase" style="font-size: 0.80rem;">
                                            <th class="ps-4 py-3 border-0">Equipment</th>
                                            <th class="py-3 border-0">Date Borrowed</th>
                                            <th class="py-3 border-0">Expected Return</th>
                                            <th class="py-3 border-0">Issued By</th>
                                            <th class="py-3 border-0">Received By</th>
                                            <th class="text-center py-3 border-0">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($transactions)): ?>
                                            <?php foreach ($transactions as $row): ?>
                                            <tr>   
                                                <td class="ps-4 py-3 text-dark fw-semibold">
                                                    <div style="font-size: 0.95rem;"><?php echo htmlspecialchars($row['item_name']); ?></div>
                                                    <div class="small text-muted font-monospace mt-1">Item #<?php echo htmlspecialchars($row['item_id']); ?></div>
                                                </td>
                                                
                                                <td class="py-3 text-muted small">
                                                    <i class="bi bi-calendar2-event text-secondary me-1"></i> <?php echo date('M d, Y', strtotime($row['borrow_date'])); ?>
                                                    <span class="text-black-50 ms-1 d-block mt-1"><i class="bi bi-clock me-1"></i><?php echo date('h:i A', strtotime($row['borrow_date'])); ?></span>
                                                </td>
                                                
                                                <td class="py-3">
                                                    <?php if (!empty($row['expected_return_time'])): ?>
                                                        <span class="badge bg-light text-dark border px-2 py-1 font-monospace shadow-sm">
                                                            <i class="bi bi-hourglass-split text-warning me-1"></i> 
                                                            <?php echo date('h:i A', strtotime($row['expected_return_time'])); ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="badge bg-light text-secondary border px-2 py-1">N/A</span>
                                                    <?php endif; ?>
                                                </td>

                                                <td class="py-3">
                                                    <div class="staff-badge text-dark shadow-sm">
                                                        <i class="bi bi-box-arrow-right text-primary me-1" style="color: var(--brand-color)!important;"></i>
                                                        <span class="fw-bold"><?php echo !empty($row['issued_by_name']) ? htmlspecialchars($row['issued_by_name']) : 'MIS Staff'; ?></span>
                                                    </div>
                                                </td>

                                                <td class="py-3">
                                                    <?php if ($row['transaction_status'] === 'Completed'): ?>
                                                        <div class="staff-badge text-dark shadow-sm">
                                                            <i class="bi bi-box-arrow-in-left text-success me-1"></i>
                                                            <span class="fw-bold"><?php echo !empty($row['received_by_name']) ? htmlspecialchars($row['received_by_name']) : 'MIS Staff'; ?></span>
                                                        </div>
                                                    <?php else: ?>
                                                        <span class="text-muted small"><i class="bi bi-arrow-repeat text-warning me-1"></i> Pending...</span>
                                                    <?php endif; ?>
                                                </td>

                                                <td class="text-center py-3">
                                                    <?php if ($row['transaction_status'] === 'Active'): ?>
                                                        <span class="badge bg-transparent text-primary border border-primary px-3 py-1 rounded-pill shadow-sm">Currently Borrowed</span>
                                                    <?php else: ?>
                                                        <?php if ($row['return_condition'] === 'Defective'): ?>
                                                            <span class="badge bg-transparent text-warning border border-warning px-3 py-1 rounded-pill shadow-sm">Returned (Defective)</span>
                                                        <?php elseif ($row['return_condition'] === 'Lost'): ?>
                                                            <span class="badge bg-transparent text-danger border border-danger px-3 py-1 rounded-pill shadow-sm">Lost</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-transparent text-success border border-success px-3 py-1 rounded-pill shadow-sm">Returned (Good)</span>
                                                        <?php endif; ?>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="6" class="text-center py-5 text-muted">
                                                    <div class="d-flex flex-column align-items-center">
                                                        <i class="bi bi-clipboard-x fs-1 text-light mb-2"></i>
                                                        <span>You haven't borrowed any equipment yet.</span>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('sidebarToggle').addEventListener('click', function (e) {
            e.preventDefault();
            document.getElementById('mainContent').classList.toggle('expanded');
        });

        const logoutBtn = document.getElementById('sidebarLogout');
        if (logoutBtn) {
            logoutBtn.addEventListener('click', function(e) {
                e.preventDefault();
                const url = this.getAttribute('href');
                Swal.fire({
                    title: 'Confirm Logout',
                    text: "Are you sure you want to end your session?",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3a5a40',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, Logout'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = url;
                    }
                });
            });
        }
    </script> 
</body>
</html>