<?php
include '../INCLUDES/database.php';
session_start();

// Redirect to login if user isn't logged in
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'Staff' && $_SESSION['role'] !== 'Admin')) {
    header("Location: login.php");
    exit();
}

// DYNAMIC SIDEBAR LOGIC
if ($_SESSION['role'] === 'Admin') {
    $sidebar_file = '../INCLUDES/sidebarAdmin.php';
} else {
    $sidebar_file = '../INCLUDES/sidebarStaff.php';
}

// 1. Fetch Live Counts for the top cards
$available_count = $mysql->query("SELECT COUNT(*) FROM items WHERE status = 'Available'")->fetch_row()[0];
$borrowed_count  = $mysql->query("SELECT COUNT(*) FROM items WHERE status = 'Borrowed'")->fetch_row()[0];
// Fetching the total count excluding 'Lost', 'Defective', and 'Archived' items
$total_count     = $mysql->query("SELECT COUNT(*) FROM items WHERE status NOT IN ('Lost', 'Defective', 'Archived')")->fetch_row()[0];

// 2. Fetch Available Items and store in an array for the Dropdown AND Modal
$avail_items_query = "SELECT item_id, item_name, serial_number FROM items WHERE status = 'Available' ORDER BY item_name ASC";
$avail_items_result = $mysql->query($avail_items_query);

$available_items_list = [];
if ($avail_items_result && $avail_items_result->num_rows > 0) {
    while ($row = $avail_items_result->fetch_assoc()) {
        $available_items_list[] = $row;
    }
}

// 3. Fetch Active Transactions
$query = "SELECT t.transaction_id, t.student_id, i.item_name, i.item_id, t.borrow_date, t.expected_return_time 
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

// --- CHART DATA FETCHING ---
// Bar Chart Data
$chart_active = $mysql->query("SELECT COUNT(*) FROM transactions WHERE transaction_status = 'Active'")->fetch_row()[0];
$chart_completed = $mysql->query("SELECT COUNT(*) FROM transactions WHERE transaction_status = 'Completed'")->fetch_row()[0];

// Doughnut Chart Data (Top 5 Items)
$top_items_query = "SELECT i.item_name, COUNT(t.transaction_id) as borrow_count 
                    FROM transactions t
                    JOIN items i ON t.item_id = i.item_id
                    GROUP BY i.item_name
                    ORDER BY borrow_count DESC
                    LIMIT 5";

$top_items_result = $mysql->query($top_items_query);
$top_labels = [];
$top_counts = [];

if ($top_items_result) {
    while ($row = $top_items_result->fetch_assoc()) {
        $top_labels[] = $row['item_name'];
        $top_counts[] = $row['borrow_count'];
    }
}

// Pie Chart Data (Health)
$defective_count_result = $mysql->query("SELECT COUNT(*) FROM items WHERE status = 'Defective'");
$defective_count = $defective_count_result ? $defective_count_result->fetch_row()[0] : 0;

$lost_count_result = $mysql->query("SELECT COUNT(*) FROM items WHERE status = 'Lost'");
$lost_count = $lost_count_result ? $lost_count_result->fetch_row()[0] : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EquipTrack | Staff Dashboard</title>
    
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,600,700&display=fallback">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        :root { --brand-color: #3a5a40; --brand-hover: #2c4430; --bg-body: #f4f7f6; }
        body { background-color: var(--bg-body); margin: 0; font-family: 'Source Sans Pro', sans-serif; color: #333; }
        .wrapper { display: flex; width: 100%; height: 100vh; position: relative; overflow: hidden; }
        .content-wrapper { flex-grow: 1; display: flex; flex-direction: column; width: calc(100% - 250px); transition: width 0.3s ease; height: 100vh; overflow-y: auto; }
        .content-wrapper.expanded { width: calc(100% - 70px); }
        .main-header { background-color: var(--brand-color); padding: 12px 20px; }
        
        .stat-card { border-radius: 1rem; transition: transform 0.3s ease, box-shadow 0.3s ease; }
        .stat-card:hover { transform: translateY(-5px); box-shadow: 0 0.5rem 1.5rem rgba(0,0,0,0.08) !important; }
        .icon-box { width: 60px; height: 60px; display: flex; align-items: center; justify-content: center; }
        
        .custom-input, .ts-control { border-radius: 0.5rem; padding: 0.6rem 1rem; border: 1px solid #dee2e6; background-color: #f8f9fa; transition: all 0.2s ease-in-out; }
        .btn-brand { background-color: var(--brand-color); color: white; border-radius: 0.5rem; padding: 0.6rem 1.5rem; font-weight: 600; border: none; }
        .btn-brand:hover { background-color: var(--brand-hover); color: white; }
        
        .table-custom-wrapper { 
            border-radius: 1rem; 
            border: 1px solid #eaedf1; 
            min-height: 280px; 
        }
        .table-custom-wrapper .table-responsive {
            border-radius: 1rem;
        }
        .table thead th { background-color: #f8f9fa; color: #6c757d; font-weight: 600; letter-spacing: 0.5px; border-bottom: 2px solid #eaedf1; }
        .table tbody tr { transition: background-color 0.2s ease; }
        .table tbody tr:hover { background-color: #f8fbfa; }
        .card-header-custom { border-bottom: 1px solid #eaedf1 !important; padding-bottom: 1.25rem !important; padding-top: 1.25rem !important; }

        .dropdown-item { transition: all 0.2s; }
        .dropdown-item:hover { background-color: #f8f9fa; transform: translateX(3px); }

      
        .line-chart-container {
            position: relative;
            height: 300px;
            width: 100%; /* Changed to 100% so it fills the column */
        }

        /* Mobile Adjustments */
        @media (max-width: 768px) {
            .content-wrapper, .content-wrapper.expanded { 
                width: 100%; 
            }
            .container-fluid.p-4 {
                padding: 1.5rem 1rem !important;
            }
            .line-chart-container {
                height: 220px; /* Reduced height specifically for mobile */
            }
            .chart-card-body {
                padding: 1rem !important; /* Less padding on small screens to give chart more room */
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
        <?php include $sidebar_file; ?>

        <div class="content-wrapper" id="mainContent">
            
            <nav class="main-header navbar navbar-expand navbar-dark border-bottom-0 shadow-sm w-100 m-0">
                <div class="container-fluid">
                    <ul class="navbar-nav align-items-center">
                        <li class="nav-item"><a class="nav-link" href="#" id="sidebarToggle"><i class="fas fa-bars"></i></a></li>
                        <li class="nav-item d-none d-sm-inline-block ms-2"><span class="nav-link font-weight-bold text-light p-0" style="font-size: 1.1rem;">Staff Dashboard</span></li>
                    </ul>
                </div>
            </nav>

            <div class="container-fluid p-4">
                
                <div class="row mb-4">
                    <div class="col-md-4 mb-3 mb-md-0">
                        <div class="card stat-card border-0 shadow-sm h-100" data-bs-toggle="modal" data-bs-target="#availableItemsModal" style="cursor: pointer;" title="Click to view available items">
                            <div class="card-body d-flex align-items-center p-4">
                                <div class="bg-success-subtle icon-box rounded-circle text-success me-4"><i class="bi bi-check-circle-fill fs-3"></i></div>
                                <div><h6 class="text-muted text-uppercase small fw-bold">Available</h6><h2 class="mb-0 fw-bold"><?php echo $available_count; ?></h2></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3 mb-md-0">
                        <div class="card stat-card border-0 shadow-sm h-100">
                            <div class="card-body d-flex align-items-center p-4">
                                <div class="icon-box rounded-circle me-4" style="background-color: rgba(135, 206, 235, 0.25); color: #87ceeb;">
                                    <i class="bi bi-cart-x-fill fs-3"></i>
                                </div>
                                <div>
                                    <h6 class="text-muted text-uppercase small fw-bold">Borrowed</h6>
                                    <h2 class="mb-0 fw-bold"><?php echo $borrowed_count; ?></h2>
                                </div>
                            </div>
                        </div>
                    </div>
                   <div class="col-md-4">
                        <div class="card stat-card border-0 shadow-sm h-100">
                            <div class="card-body d-flex align-items-center p-4">
                                <div class="icon-box rounded-circle me-4" style="background-color: rgba(227, 183, 240, 0.38); color: #5d0bf5;">
                                    <i class="bi bi-boxes fs-3"></i>
                                </div>
                                <div>
                                    <h6 class="text-muted text-uppercase small fw-bold">Total Equipments</h6>
                                    <h2 class="mb-0 fw-bold"><?php echo $total_count; ?></h2>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mb-4 align-items-stretch">
                    
                    <div class="col-lg-4 mb-4 mb-lg-0">
                        <div class="card border-0 shadow-sm rounded-4 h-100">
                            <div class="card-header bg-white card-header-custom border-0 px-4">
                                <h5 class="mb-0 text-dark fw-bold" style="font-size: 1.1rem;">
                                    <i class="bi bi-graph-up-arrow text-primary me-2" style="color: var(--brand-color)!important;"></i> Transaction Status
                                </h5>
                            </div>
                            <div class="card-body p-4 chart-card-body d-flex flex-column justify-content-center">
                                <div class="line-chart-container">
                                    <canvas id="transactionChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4 mb-4 mb-lg-0">
                        <div class="card border-0 shadow-sm rounded-4 h-100">
                            <div class="card-header bg-white card-header-custom border-0 px-4">
                                <h5 class="mb-0 text-dark fw-bold" style="font-size: 1.1rem;">
                                    <i class="bi bi-fire text-warning me-2"></i> Most Popular Equipment
                                </h5>
                            </div>
                            <div class="card-body p-4 d-flex flex-column justify-content-center">
                                <div style="height: 300px; width: 100%;">
                                    <canvas id="topItemsChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="card border-0 shadow-sm rounded-4 h-100">
                            <div class="card-header bg-white card-header-custom border-0 px-4">
                                <h5 class="mb-0 text-dark fw-bold" style="font-size: 1.1rem;">
                                    <i class="bi bi-exclamation-octagon text-danger me-2"></i> Inventory Health Status
                                </h5>
                            </div>
                            <div class="card-body p-4 d-flex flex-column justify-content-center">
                                <div style="height: 300px; width: 100%;">
                                    <canvas id="lossChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                </div>

                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white border-0 px-4 py-3 border-bottom">
                        <h5 class="mb-0 text-dark fw-bold"><i class="bi bi-plus-circle-fill text-primary me-2" style="color: var(--brand-color)!important;"></i> Process New Borrowing</h5>
                    </div>
                    <div class="card-body p-4">
                        <form id="borrowForm" action="../ACTIONS/process_borrow.php" method="POST" class="row g-3 align-items-end">
                            <div class="col-md-3">
                                <label class="form-label fw-bold small text-muted">Student ID</label>
                                <input type="text" name="student_id" class="form-control custom-input" placeholder="e.g. 2024-1001" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold small text-muted">Search Equipment Name</label>
                                <select name="item_id" id="equipmentSelect" class="form-select" required>
                                    <option value="">Search item...</option>
                                    <?php foreach ($available_items_list as $item): ?>
                                        <option value="<?php echo htmlspecialchars($item['item_id']); ?>">
                                            <?php echo htmlspecialchars($item['item_name']); ?> 
                                            <?php echo !empty($item['serial_number']) ? '('.htmlspecialchars($item['serial_number']).')' : ''; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold small text-muted">Expected Return Time</label>
                                <input type="time" name="expected_return_time" class="form-control custom-input" required>
                            </div>
                            <div class="col-md-2 d-grid">
                                <button type="submit" class="btn btn-brand shadow-sm">Process <i class="bi bi-arrow-right ms-1"></i></button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-header bg-white border-0 px-4 py-3 border-bottom">
                        <h5 class="mb-0 text-dark fw-bold"><i class="bi bi-list-task text-primary me-2" style="color: var(--brand-color)!important;"></i> Currently Borrowed Equipment</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-custom-wrapper m-3">
                            <div class="table-responsive">
                                <table class="table align-middle mb-0 bg-white">
                                    <thead>
                                        <tr class="text-uppercase" style="font-size: 0.8rem;">
                                            <th class="ps-4 py-3 border-0">Student ID</th>
                                            <th class="py-3 border-0">Equipment</th>
                                            <th class="py-3 border-0">Date Borrowed</th>
                                            <th class="py-3 border-0">Expected Return</th>
                                            <th class="text-center py-3 border-0">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($transactions as $row): ?>
                                        <tr>
                                            <td class="ps-4 py-3 fw-bold text-dark">
                                                <span class="badge bg-light text-dark border px-2 py-1"><?php echo htmlspecialchars($row['student_id']); ?></span>
                                            </td>
                                            <td class="py-3 fw-semibold text-dark">
                                                <?php echo htmlspecialchars($row['item_name']); ?> 
                                                <span class="text-muted small">(#<?php echo $row['item_id']; ?>)</span>
                                            </td>
                                            <td class="py-3 text-muted small">
                                                <i class="bi bi-calendar2-event text-secondary me-1"></i> <?php echo date('M d, Y h:i A', strtotime($row['borrow_date'])); ?>
                                            </td>
                                            <td class="py-3">
                                                <?php if (!empty($row['expected_return_time'])): ?>
                                                    <span class="badge bg-light text-dark border px-2 py-1 font-monospace shadow-sm">
                                                        <i class="bi bi-clock-history text-warning me-1"></i> 
                                                        <?php echo date('h:i A', strtotime($row['expected_return_time'])); ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-light text-secondary border px-2 py-1">N/A</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center py-3">
                                                <div class="dropdown">
                                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle px-3 fw-bold border-1" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                        Manage
                                                    </button>
                                                    <ul class="dropdown-menu shadow border-0 py-2" style="border-radius: 0.8rem;">
                                                        <li>
                                                            <a class="dropdown-item text-success fw-bold return-btn" href="../ACTIONS/process_return.php?tid=<?php echo $row['transaction_id']; ?>&status=Returned">
                                                                <i class="bi bi-check2-circle me-2"></i> Good Return
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a class="dropdown-item text-warning fw-bold defective-btn" href="../ACTIONS/process_return.php?tid=<?php echo $row['transaction_id']; ?>&status=Defective">
                                                                <i class="bi bi-exclamation-triangle me-2"></i> Mark Defective
                                                            </a>
                                                        </li>
                                                        <li><hr class="dropdown-divider opacity-25"></li>
                                                        <li>
                                                            <a class="dropdown-item text-danger fw-bold lost-btn" href="../ACTIONS/process_return.php?tid=<?php echo $row['transaction_id']; ?>&status=Lost">
                                                                <i class="bi bi-x-circle me-2"></i> Mark as Lost
                                                            </a>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                        <?php if (empty($transactions)): ?>
                                            <tr><td colspan="5" class="text-center py-5 text-muted"><i class="bi bi-inbox fs-1 opacity-50 mb-2 d-block"></i>No active transactions.</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php include '../INCLUDES/footer.php'; ?>
        </div> 
    </div> 

    <div class="modal fade" id="availableItemsModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content" style="border-radius: 1rem;">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title fw-bold">Available Equipment</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-0">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light"><tr><th class="ps-4">Item Name</th><th>Serial No.</th></tr></thead>
                        <tbody>
                            <?php foreach ($available_items_list as $item): ?>
                                <tr>
                                    <td class="ps-4 fw-bold"><?php echo htmlspecialchars($item['item_name']); ?></td>
                                    <td class="small font-monospace"><?php echo htmlspecialchars($item['serial_number']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            
            // --- CHARTS INITIALIZATION ---
            
            // Most Popular Equipment Chart
            const ctxTop = document.getElementById('topItemsChart').getContext('2d');
            new Chart(ctxTop, {
                type: 'doughnut',
                data: {
                    labels: <?php echo json_encode($top_labels); ?>,
                    datasets: [{
                        data: <?php echo json_encode($top_counts); ?>,
                        backgroundColor: ['#3a5a40', '#588157', '#a3b18a', '#dad7cd', '#344e41'],
                        hoverOffset: 15,
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom', labels: { padding: 20, usePointStyle: true, font: { size: 12 } } },
                        tooltip: { callbacks: { label: function(context) { return ` ${context.label}: ${context.raw} Borrows`; } } }
                    },
                    cutout: '70%'
                }
            });

            // Inventory Health Status (Pie Chart)
            const ctxLoss = document.getElementById('lossChart').getContext('2d');
            new Chart(ctxLoss, {
                type: 'pie',
                data: {
                    labels: ['Defective', 'Lost'],
                    datasets: [{
                        data: [<?php echo $defective_count; ?>, <?php echo $lost_count; ?>],
                        backgroundColor: ['#FF6700', '#dc3545'],
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom', labels: { padding: 20, usePointStyle: true } }
                    }
                }
            });

           // Transaction Status Bar Chart
            const ctx = document.getElementById('transactionChart').getContext('2d');
            new Chart(ctx, {
                type: 'bar',
                data: {
                    // Using arrays wraps the text to a second line instead of tilting it
                    labels: [['Active', 'Borrowing'], ['Completed', 'Transactions']],
                    datasets: [{
                        label: 'Count',
                        data: [<?php echo $chart_active; ?>, <?php echo $chart_completed; ?>],
                        backgroundColor: ['#87ceeb', '#5AA27C'],
                        borderRadius: 8,
                        maxBarThickness: 70 // Prevents the bars from becoming too wide
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: { 
                            beginAtZero: true, 
                            ticks: { stepSize: 1 } 
                        },
                        x: { 
                            grid: { display: false },
                            ticks: {
                                maxRotation: 0,
                                minRotation: 0,
                                font: {
                                    size: 11 
                                }
                            }
                        }
                    }
                }
            });
            
            // TomSelect Initialization
            new TomSelect("#equipmentSelect", { create: false, sortField: { field: "text", direction: "asc" } });
            
            // Sidebar Toggle
            document.getElementById('sidebarToggle').addEventListener('click', () => document.getElementById('mainContent').classList.toggle('expanded'));
            
            // Borrow Confirmation
            const borrowForm = document.getElementById('borrowForm');
            if (borrowForm) {
                borrowForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    Swal.fire({
                        title: 'Process Borrowing?',
                        text: 'Assign this equipment to the student?',
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonColor: '#3a5a40',
                        confirmButtonText: 'Yes, Process!'
                    }).then((r) => { if (r.isConfirmed) borrowForm.submit(); });
                });
            }

            // Dropdown action listeners
            document.querySelectorAll('.return-btn').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const url = this.getAttribute('href');
                    Swal.fire({
                        title: 'Good Return?',
                        text: 'Equipment returned in good condition?',
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonColor: '#198754',
                        confirmButtonText: 'Yes, Return'
                    }).then((r) => { if (r.isConfirmed) window.location.href = url; });
                });
            });

            document.querySelectorAll('.defective-btn').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const url = this.getAttribute('href');
                    Swal.fire({
                        title: 'Mark Defective?',
                        text: 'Was this item returned broken or damaged?',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#ffc107',
                        confirmButtonText: 'Yes, Defective'
                    }).then((r) => { if (r.isConfirmed) window.location.href = url; });
                });
            });

            document.querySelectorAll('.lost-btn').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const url = this.getAttribute('href');
                    Swal.fire({
                        title: 'Mark as Lost?',
                        text: 'Did the student report this item as missing? It will be removed from available stock.',
                        icon: 'error',
                        showCancelButton: true,
                        confirmButtonColor: '#dc3545',
                        confirmButtonText: 'Yes, Mark Lost'
                    }).then((r) => { if (r.isConfirmed) window.location.href = url; });
                });
            });
            
            // Logout logic
            const logoutButtons = document.querySelectorAll('a[href*="logout.php"]');
            logoutButtons.forEach(button => {
                button.addEventListener('click', function(e) {
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
            });
        });
    </script> 

    <?php if (isset($_GET['status'])): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                <?php if ($_GET['status'] == 'success'): ?>
                    Swal.fire('Success!', 'Equipment successfully borrowed.', 'success');
                <?php elseif ($_GET['status'] == 'returned'): ?>
                    Swal.fire('Returned!', 'Equipment returned in good condition.', 'success');
                <?php elseif ($_GET['status'] == 'defective'): ?>
                    Swal.fire('Marked Defective', 'Equipment has been marked as defective.', 'warning');
                <?php elseif ($_GET['status'] == 'lost'): ?>
                    Swal.fire('Marked Lost', 'Equipment has been marked as lost.', 'error');
                <?php elseif ($_GET['status'] == 'unavailable'): ?>
                    Swal.fire('Item Not Available', 'This equipment is currently Borrowed, Defective, or Lost.', 'warning');
                <?php elseif ($_GET['status'] == 'error'): ?>
                    Swal.fire('Error', 'An unexpected error occurred.', 'error');
                <?php endif; ?>
                
                window.history.replaceState(null, null, window.location.pathname);
            });
        </script>
    <?php endif; ?>

</body>
</html>
