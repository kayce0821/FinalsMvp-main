<?php
session_start();
include '../INCLUDES/database.php';

// Security check: ONLY Students
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Student') {
    header("Location: ../PAGES/login.php");
    exit();
}

$sidebar_file = '../INCLUDES/sidebarStudent.php';

// Get the Student's actual ID
$student_id = "";
$stmt = $mysql->prepare("SELECT student_id FROM students WHERE full_name = ?");
$stmt->bind_param("s", $_SESSION['full_name']);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();
if ($res) {
    $student_id = $res['student_id'];
}
$stmt->close();

// Fetch their Actual Borrowing Transactions (Active/Completed)
$trans_query = "SELECT t.borrow_date, i.item_name, t.transaction_status 
                FROM transactions t 
                JOIN items i ON t.item_id = i.item_id 
                WHERE t.student_id = ? ORDER BY t.borrow_date DESC";
$trans_stmt = $mysql->prepare($trans_query);
$trans_stmt->bind_param("s", $student_id);
$trans_stmt->execute();
$transactions = $trans_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaction History - EquipTrack</title>
    
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
                            <span class="nav-link font-weight-bold text-light p-0" style="font-size: 1.1rem; letter-spacing: 0.5px;">Transaction History</span>
                        </li>
                    </ul>
                </div>
            </nav>

            <div class="container-fluid p-4">
                
                <div class="mb-4">
                    <h4 class="mb-0 text-dark fw-bold">My Borrowing History</h4>
                    <p class="text-muted small mb-0 mt-1">A complete record of equipment you have borrowed and returned.</p>
                </div>

                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white card-header-custom border-0 px-4">
                        <h5 class="mb-0 text-dark fw-bold">
                            <i class="bi bi-clock-history text-primary me-2" style="color: var(--brand-color)!important;"></i> Transaction Masterlist
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive table-custom-wrapper m-3">
                            <table class="table align-middle mb-0 bg-white">
                                <thead>
                                    <tr class="text-uppercase" style="font-size: 0.80rem;">
                                        <th class="ps-4 py-3 border-0">Date Borrowed</th>
                                        <th class="py-3 border-0">Equipment</th>
                                        <th class="text-center py-3 border-0 pe-4">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($transactions && $transactions->num_rows > 0): ?>
                                        <?php while ($row = $transactions->fetch_assoc()): ?>
                                            <tr>
                                                <td class="ps-4 py-3">
                                                    <div class="text-dark fw-bold" style="font-size: 0.95rem;">
                                                        <?php echo date('M d, Y', strtotime($row['borrow_date'])); ?>
                                                    </div>
                                                    <div class="text-muted small">
                                                        <i class="bi bi-clock me-1 text-secondary"></i><?php echo date('h:i A', strtotime($row['borrow_date'])); ?>
                                                    </div>
                                                </td>
                                                <td class="py-3 text-dark fw-semibold" style="font-size: 0.95rem;">
                                                    <?php echo htmlspecialchars($row['item_name']); ?>
                                                </td>
                                                <td class="text-center py-3 pe-4">
                                                    <?php if ($row['transaction_status'] === 'Active'): ?>
                                                        <span class="badge bg-transparent text-danger border border-danger px-3 py-1 rounded-pill">Active</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-transparent text-success border border-success px-3 py-1 rounded-pill">Returned</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="3" class="text-center py-5 text-muted">
                                                <div class="d-flex flex-column align-items-center">
                                                    <i class="bi bi-inbox fs-1 text-light mb-2 opacity-50"></i>
                                                    <span>You have no borrowing history yet.</span>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Sidebar Toggle
            const sidebarToggle = document.getElementById('sidebarToggle');
            if (sidebarToggle) {
                sidebarToggle.addEventListener('click', function (e) {
                    e.preventDefault();
                    document.getElementById('mainContent').classList.toggle('expanded');
                });
            }
        });
    </script>
</body>
</html>