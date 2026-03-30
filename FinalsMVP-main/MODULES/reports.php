<?php
include '../INCLUDES/database.php';
session_start();

// Security check
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'Staff' && $_SESSION['role'] !== 'Admin')) {
    header("Location: ../PAGES/login.php");
    exit();
}

// DYNAMIC SIDEBAR LOGIC
if ($_SESSION['role'] === 'Admin') {
    $sidebar_file = '../INCLUDES/sidebarAdmin.php';
} else {
    $sidebar_file = '../INCLUDES/sidebarStaff.php';
}

// 1. Fetch Inventory Health Summary
$total_items = $mysql->query("SELECT COUNT(*) FROM items")->fetch_row()[0];
$available = $mysql->query("SELECT COUNT(*) FROM items WHERE status = 'Available'")->fetch_row()[0];
$borrowed = $mysql->query("SELECT COUNT(*) FROM items WHERE status = 'Borrowed'")->fetch_row()[0];
$defective = $mysql->query("SELECT COUNT(*) FROM items WHERE status = 'Defective'")->fetch_row()[0];

// --- SORTING LOGIC ---
$sort_filter = isset($_GET['range']) ? $_GET['range'] : 'all';
$where_clause = "";

switch ($sort_filter) {
    case 'day':
        $where_clause = "WHERE t.borrow_date >= CURDATE()";
        break;
    case 'week':
        $where_clause = "WHERE t.borrow_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
        break;
    case 'month':
        $where_clause = "WHERE t.borrow_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        break;
    default:
        $where_clause = ""; // Show all
        break;
}

// --- PAGINATION LOGIC START ---
$records_per_page = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

$count_query = "SELECT COUNT(*) FROM transactions t $where_clause";
$total_rows = $mysql->query($count_query)->fetch_row()[0];
$total_pages = ceil($total_rows / $records_per_page);
// --- PAGINATION LOGIC END ---

// 2. Fetch Transaction History (PAGINATED FOR WEB VIEW)
$query_paginated = "SELECT t.transaction_id, t.student_id, s.full_name, i.item_name, i.item_id, t.borrow_date, t.transaction_status 
          FROM transactions t
          JOIN items i ON t.item_id = i.item_id
          JOIN students s ON t.student_id = s.student_id
          $where_clause
          ORDER BY t.borrow_date DESC
          LIMIT $records_per_page OFFSET $offset";

$result_paginated = $mysql->query($query_paginated);
$transactions_paginated = [];
if ($result_paginated && $result_paginated->num_rows > 0) {
    while ($row = $result_paginated->fetch_assoc()) {
        $transactions_paginated[] = $row;
    }
}

// 3. Fetch ALL Transaction History (UNPAGINATED FOR PDF DOWNLOAD)
$query_all = "SELECT t.transaction_id, t.student_id, s.full_name, i.item_name, i.item_id, t.borrow_date, t.transaction_status 
          FROM transactions t
          JOIN items i ON t.item_id = i.item_id
          JOIN students s ON t.student_id = s.student_id
          $where_clause
          ORDER BY t.borrow_date DESC";

$result_all = $mysql->query($query_all);
$transactions_all = [];
if ($result_all && $result_all->num_rows > 0) {
    while ($row = $result_all->fetch_assoc()) {
        $transactions_all[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EquipTrack | Reports</title>
    
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,600,700&display=fallback">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

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

        /* Report Stat Cards */
        .stat-card { border-radius: 1rem; transition: transform 0.3s ease, box-shadow 0.3s ease; }
        .stat-card:hover { transform: translateY(-5px); box-shadow: 0 0.5rem 1.5rem rgba(0,0,0,0.08) !important; }
        .icon-box { width: 45px; height: 45px; display: flex; align-items: center; justify-content: center; }

        /* Buttons & Inputs */
        .btn-brand { background-color: var(--brand-color); color: white; border: none; transition: all 0.3s ease; }
        .btn-brand:hover { background-color: var(--brand-hover); color: white; transform: translateY(-1px); }

        /* Card & Table Styling */
        .card-header-custom { border-bottom: 1px solid #eaedf1 !important; padding: 1.25rem !important; }
        .table-custom-wrapper { border-radius: 1rem; overflow: hidden; border: 1px solid #eaedf1; }
        .table thead th { background-color: #f8f9fa; color: #6c757d; font-weight: 600; letter-spacing: 0.5px; border-bottom: 2px solid #eaedf1; }
        .table tbody tr:hover { background-color: #f8fbfa; }

        /* Pagination custom colors */
        .pagination-custom .page-item .page-link { border: none; color: #6c757d; border-radius: 0.5rem; margin: 0 0.2rem; transition: all 0.2s; }
        .pagination-custom .page-item.active .page-link { background-color: var(--brand-color); color: white; box-shadow: 0 4px 6px rgba(58, 90, 64, 0.2); }
        .pagination-custom .page-item .page-link:hover:not(.active) { background-color: #eaedf1; color: var(--brand-color); }

        @media (max-width: 768px) {
            .content-wrapper, .content-wrapper.expanded { width: 100%; }
        }

        /* --- PDF SPECIFIC STYLES --- */
        /* This ensures rows do not split across pages */
        .pdf-table-row {
            page-break-inside: avoid !important;
            break-inside: avoid !important;
        }
        #pdf-view {
            background-color: white;
            font-family: 'Source Sans Pro', sans-serif;
            color: #000;
        }
    </style>
</head>
<body>

    <div class="wrapper">
        <?php include $sidebar_file; ?>

        <div class="content-wrapper" id="mainContent">
            <nav class="main-header navbar navbar-expand navbar-dark border-bottom-0 shadow-sm w-100 m-0">
                <div class="container-fluid">
                    <ul class="navbar-nav align-items-center">
                        <li class="nav-item"><a class="nav-link" href="#" id="sidebarToggle" role="button"><i class="fas fa-bars"></i></a></li>
                        <li class="nav-item d-none d-sm-inline-block ms-2"><span class="nav-link font-weight-bold text-light p-0" style="font-size: 1.1rem; letter-spacing: 0.5px;">System Reports</span></li>
                    </ul>
                </div>
            </nav>

            <div class="container-fluid p-4" id="web-view">
                
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
                    <div>
                        <h4 class="fw-bold text-dark m-0">Inventory & Transaction Report</h4>
                        <p class="text-muted small mb-0 mt-1">Overview of all system assets and borrowing history.</p>
                    </div>
                    
                    <div class="d-flex flex-wrap gap-2 filter-section">
                        <form method="GET" class="d-flex align-items-center bg-white border rounded-3 px-2 py-1 shadow-sm">
                            <i class="bi bi-calendar3 text-muted mx-2"></i>
                            <select name="range" class="form-select form-select-sm border-0 shadow-none text-dark fw-semibold" style="width: 140px; cursor: pointer;" onchange="this.form.submit()">
                                <option value="all" <?php echo $sort_filter == 'all' ? 'selected' : ''; ?>>All History</option>
                                <option value="day" <?php echo $sort_filter == 'day' ? 'selected' : ''; ?>>Today</option>
                                <option value="week" <?php echo $sort_filter == 'week' ? 'selected' : ''; ?>>Last 7 Days</option>
                                <option value="month" <?php echo $sort_filter == 'month' ? 'selected' : ''; ?>>Last 30 Days</option>
                            </select>
                        </form>

                        <button onclick="downloadPDF()" class="btn btn-brand shadow-sm px-3 fw-semibold rounded-3">
                            <i class="bi bi-file-earmark-pdf me-2"></i> Download Full PDF
                        </button>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-md-3 mb-3 mb-md-0">
                        <div class="card stat-card border-0 shadow-sm h-100 bg-white border-success border border-primary-subtle">
                            <div class="card-body p-3 p-xl-4 d-flex align-items-center">
                                <div class="icon-box bg-white rounded-circle text-primary me-3 shadow-sm"><i class="bi bi-boxes fs-4"></i></div>
                                <div>
                                    <h6 class="text-primary-emphasis text-uppercase small fw-bold mb-1 tracking-wide">Total Equipments</h6>
                                    <h3 class="mb-0 fw-bold text-dark"><?php echo $total_items; ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3 mb-md-0">
                        <div class="card stat-card border-0 shadow-sm h-100 bg-white border-success border border-success-subtle">
                            <div class="card-body p-3 p-xl-4 d-flex align-items-center">
                                <div class="icon-box bg-white rounded-circle text-success me-3 shadow-sm"><i class="bi bi-check2-circle fs-4"></i></div>
                                <div>
                                    <h6 class="text-success-emphasis text-uppercase small fw-bold mb-1 tracking-wide">Available</h6>
                                    <h3 class="mb-0 fw-bold text-success-emphasis"><?php echo $available; ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3 mb-md-0">
                        <div class="card stat-card border-0 shadow-sm h-100 bg-white border-success border border-info-subtle">
                            <div class="card-body p-3 p-xl-4 d-flex align-items-center">
                                <div class="icon-box bg-white rounded-circle text-info me-3 shadow-sm"><i class="bi bi-arrow-left-right fs-4"></i></div>
                                <div>
                                    <h6 class="text-info-emphasis text-uppercase small fw-bold mb-1 tracking-wide">Borrowed</h6>
                                    <h3 class="mb-0 fw-bold text-info-emphasis"><?php echo $borrowed; ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card border-0 shadow-sm h-100 bg-white border-success border border-warning-subtle">
                            <div class="card-body p-3 p-xl-4 d-flex align-items-center">
                                <div class="icon-box bg-white rounded-circle text-warning me-3 shadow-sm"><i class="bi bi-exclamation-triangle fs-4"></i></div>
                                <div>
                                    <h6 class="text-warning-emphasis text-uppercase small fw-bold mb-1 tracking-wide">Defective</h6>
                                    <h3 class="mb-0 fw-bold text-warning-emphasis"><?php echo $defective; ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm rounded-4 mb-5">
                    <div class="card-header bg-white card-header-custom border-0 px-4">
                        <h5 class="mb-0 text-dark fw-bold d-flex align-items-center">
                            <i class="bi bi-table text-primary me-2" style="color: var(--brand-color)!important;"></i> Transaction Masterlist 
                            <span class="badge bg-transparent border border-secondary text-secondary fw-normal ms-3 align-text-bottom" style="font-size: 0.8rem;">Filter: <?php echo ucfirst($sort_filter); ?></span>
                        </h5>
                    </div>
                    
                    <div class="card-body p-0">
                        <div class="table-responsive table-custom-wrapper m-3">
                            <table class="table align-middle mb-0 bg-white">
                                <thead>
                                    <tr class="text-uppercase" style="font-size: 0.80rem;">
                                        <th class="ps-4 py-3 border-0">Student Name</th>
                                        <th class="py-3 border-0">Equipment</th>
                                        <th class="py-3 border-0">Date Borrowed</th>
                                        <th class="text-center py-3 border-0">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($transactions_paginated)): ?>
                                        <?php foreach ($transactions_paginated as $row): ?>
                                        <tr>   
                                            <td class="ps-4 py-3">
                                                <div class="fw-bold text-dark" style="font-size: 0.95rem;"><?php echo htmlspecialchars($row['full_name']); ?></div>
                                                <div class="small text-muted font-monospace mt-1">ID: <?php echo htmlspecialchars($row['student_id']); ?></div>
                                            </td>
                                            <td class="py-3 text-dark fw-semibold">
                                                <?php echo htmlspecialchars($row['item_name']); ?> 
                                                <span class="text-muted fw-normal ms-1 fs-6">(#<?php echo htmlspecialchars($row['item_id']); ?>)</span>
                                            </td>
                                            <td class="py-3 text-muted small">
                                                <i class="bi bi-calendar2-event text-secondary me-1"></i> <?php echo date('M d, Y', strtotime($row['borrow_date'])); ?>
                                                <span class="text-black-50 ms-1 d-block mt-1 d-sm-inline mt-sm-0"><i class="bi bi-clock me-1 d-none d-sm-inline"></i><?php echo date('h:i A', strtotime($row['borrow_date'])); ?></span>
                                            </td>
                                            <td class="text-center py-3">
                                                <?php if ($row['transaction_status'] === 'Active'): ?>
                                                    <span class="badge bg-transparent text-danger border border-danger px-3 py-1 rounded-pill">Active</span>
                                                <?php else: ?>
                                                    <span class="badge bg-transparent text-success border border-success px-3 py-1 rounded-pill">Completed</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="text-center py-5 text-muted">
                                                <div class="d-flex flex-column align-items-center">
                                                    <i class="bi bi-clipboard-x fs-1 text-light mb-2"></i>
                                                    <span>No transaction history found for this period.</span>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <?php if ($total_pages > 1): ?>
                    <div class="card-footer bg-white border-top-0 py-3 px-4 d-flex justify-content-end pagination-container">
                        <nav aria-label="Transaction page navigation">
                            <ul class="pagination pagination-custom mb-0">
                                <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                                    <a class="page-link shadow-sm" href="?range=<?php echo $sort_filter; ?>&page=<?php echo $page - 1; ?>"><i class="bi bi-chevron-left"></i></a>
                                </li>
                                <?php for($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                                        <a class="page-link shadow-sm fw-bold" href="?range=<?php echo $sort_filter; ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                    </li>
                                <?php endfor; ?>
                                <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                                    <a class="page-link shadow-sm" href="?range=<?php echo $sort_filter; ?>&page=<?php echo $page + 1; ?>"><i class="bi bi-chevron-right"></i></a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
                <div id="pdf-view" style="display: none; width: 100%; max-width: 800px; margin: 0 auto; padding-top: 20px;">                <div class="text-center mb-4">
                    <h2 class="fw-bold mb-1" style="color: var(--brand-color);">EquipTrack</h2>
                    <h4 class="text-dark mb-1">Inventory & Transaction Report</h4>
                    <p class="text-muted">Generated on: <?php echo date('F d, Y h:i A'); ?> | Filter: <?php echo ucfirst($sort_filter); ?></p>
                </div>

                <div class="row text-center mb-4 pb-3 border-bottom">
                    <div class="col-3">
                        <h6 class="text-muted text-uppercase mb-1 small">Total Equipments</h6>
                        <h4 class="fw-bold"><?php echo $total_items; ?></h4>
                    </div>
                    <div class="col-3">
                        <h6 class="text-muted text-uppercase mb-1 small">Available</h6>
                        <h4 class="fw-bold text-success"><?php echo $available; ?></h4>
                    </div>
                    <div class="col-3">
                        <h6 class="text-muted text-uppercase mb-1 small">Borrowed</h6>
                        <h4 class="fw-bold text-info"><?php echo $borrowed; ?></h4>
                    </div>
                    <div class="col-3">
                        <h6 class="text-muted text-uppercase mb-1 small">Defective</h6>
                        <h4 class="fw-bold text-danger"><?php echo $defective; ?></h4>
                    </div>
                </div>

                <table class="table table-bordered table-sm">
                    <thead class="table-light">
                        <tr>
                            <th>Student ID</th>
                            <th>Student Name</th>
                            <th>Equipment (ID)</th>
                            <th>Date Borrowed</th>
                            <th class="text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($transactions_all)): ?>
                            <?php foreach ($transactions_all as $row): ?>
                            <tr class="pdf-table-row">   
                                <td><?php echo htmlspecialchars($row['student_id']); ?></td>
                                <td class="fw-bold"><?php echo htmlspecialchars($row['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['item_name']); ?> (#<?php echo htmlspecialchars($row['item_id']); ?>)</td>
                                <td><?php echo date('M d, Y h:i A', strtotime($row['borrow_date'])); ?></td>
                                <td class="text-center">
                                    <?php if ($row['transaction_status'] === 'Active'): ?>
                                        <span class="text-danger fw-bold">Active</span>
                                    <?php else: ?>
                                        <span class="text-success fw-bold">Completed</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">No transaction history found for this period.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
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

        // PDF Generation Script
      function downloadPDF() {
            const webView = document.getElementById('web-view');
            const pdfView = document.getElementById('pdf-view');
            
            // 1. Hide the Web UI and Show the PDF container
            webView.style.display = 'none';
            pdfView.style.display = 'block';
            
            // 2. Setup options (Fixed scaling and margins)
            const opt = {
                margin:       [0.5, 0.5, 0.5, 0.5], // Top, Right, Bottom, Left margins
                filename:     'EquipTrack_Full_Report.pdf',
                image:        { type: 'jpeg', quality: 0.98 },
                html2canvas:  { scale: 2, useCORS: true }, // Removed windowWidth
                jsPDF:        { unit: 'in', format: 'letter', orientation: 'portrait' },
                pagebreak:    { mode: ['css', 'legacy'] }
            };

            // 3. Generate PDF, then put the screen back to normal
            html2pdf().set(opt).from(pdfView).save().then(() => {
                pdfView.style.display = 'none';
                webView.style.display = 'block';
            });
        }
    </script> 
</body>
</html>