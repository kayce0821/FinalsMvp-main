<?php
include '../INCLUDES/database.php';
session_start();

// ONLY check where to redirect if the user is actually logged in
if (isset($_SESSION['role'])) {
    // Send Admins and Staff to their secure dashboards
    if ($_SESSION['role'] === 'Admin') {
        header("Location: adminDashboard.php");
        exit();
    } elseif ($_SESSION['role'] === 'Staff') {
        header("Location: staffDashboard.php");
        exit();
    } elseif ($_SESSION['role'] === 'Super Admin') {
        header("Location: superAdminDashboard.php");
        exit();
    } elseif ($_SESSION['role'] === 'Student') {
         header("Location: studentDashboard.php");
        exit();
    }
    // If the role is 'Student', they do nothing and stay on this page.
}

// Fetch Live Counts
$available_count = $mysql->query("SELECT COUNT(*) FROM items WHERE status = 'Available'")->fetch_row()[0];
$borrowed_count  = $mysql->query("SELECT COUNT(*) FROM items WHERE status = 'Borrowed'")->fetch_row()[0];
$defective_count = $mysql->query("SELECT COUNT(*) FROM items WHERE status = 'Defective'")->fetch_row()[0];

// --- PAGINATION LOGIC START ---
$records_per_page = 10; // Change this number to adjust rows per page
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

// Count total items for pagination
$count_query = "SELECT COUNT(*) FROM items WHERE status != 'Archived'";
$total_rows = $mysql->query($count_query)->fetch_row()[0];
$total_pages = ceil($total_rows / $records_per_page);
// --- PAGINATION LOGIC END ---

// Fetch Inventory List (With LIMIT and OFFSET added)
$query = "SELECT item_id, item_name, serial_Number, status FROM items WHERE status != 'Archived' ORDER BY item_id DESC LIMIT $records_per_page OFFSET $offset";
$result = $mysql->query($query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EquipTrack | Guest Dashboard</title>
    
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
        :root {
            --primary-green: #3a5a40;
            --primary-light: #588157;
            --light-bg: #f4f6f9;
        }
        
        body {
            background-color: var(--light-bg);
            font-family: 'Source Sans Pro', sans-serif;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        
        .main-header { background-color: var(--primary-green) !important; padding: 0.75rem 0; }
        .brand-logo-text { font-size: 1.5rem; letter-spacing: 1px; color: white; text-decoration: none; }
        .login-btn { 
            border: 1px solid rgba(255,255,255,0.5); 
            border-radius: 8px; padding: 8px 20px !important;
            color: white !important; font-weight: 600; transition: all 0.3s ease;
        }
        .login-btn:hover { background-color: white !important; color: var(--primary-green) !important; }

        .hero-section {
            background: linear-gradient(135deg, var(--primary-green) 0%, var(--primary-light) 100%);
            color: white; padding: 60px 0; margin-bottom: 40px; box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .hero-title { font-weight: 700; font-size: 3.5rem; }

        .stat-card { border-radius: 12px; transition: transform 0.2s; border: none; }
        .stat-card:hover { transform: translateY(-5px); }
        
        .inventory-card { border-radius: 12px; overflow: hidden; }
        .badge-status { padding: 0.5em 1em; border-radius: 6px; font-weight: 600; font-size: 0.8rem; }

        /* Custom style for the borrow button */
        .btn-borrow {
            border: 1.5px solid var(--primary-green);
            color: var(--primary-green);
            font-weight: 600;
            transition: all 0.3s;
        }
        .btn-borrow:hover {
            background-color: var(--primary-green);
            color: white;
        }
        .hero-section {
            padding: 200px 0; /* Adjust the 100px to increase/decrease height */
            position: relative;
            background-color: linear-gradient(#a3b18a, #344e41);/* Your primary color */
            background-repeat: no-repeat;
            background-position: center;    
            background-size: 100%; /* Adjust size as needed */
            background-blend-mode: overlay; /* Blends the logo with the background color */
            overflow: hidden; /* Keeps the logo inside the section */
        }
        .hero-bg-logo {
            position: absolute;
            right: 1%;
            bottom: 15%;
            width: 400px;
            opacity: 0.5; /* Makes it very faint */
            z-index: 0; /* Puts it behind the text */
            pointer-events: none; /* Allows users to click through the image */
        }

        .hero-section .container {
            position: relative;
            z-index: 1; /* Keeps text above the logo */
        }

        .hero-cta-btn {
            background-color: white;
            color: var(--primary-green) !important;
            font-weight: 700;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }

        .hero-cta-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.15);
            background-color: #f8f9fa;
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
            background-color: var(--primary-green); 
            color: white; 
            box-shadow: 0 4px 6px rgba(58, 90, 64, 0.2);
        }
        .pagination-custom .page-item .page-link:hover:not(.active) {
            background-color: #eaedf1;
            color: var(--primary-green);
        }
    </style>
</head>
<body>

    <nav class="main-header navbar navbar-expand navbar-dark shadow-sm">
        <div class="container">
            <a href="#" class="brand-logo-text d-flex align-items-center">
                <i class="bi bi-box-seam me-2"></i><strong>EQUIP</strong>TRACK
            </a>
            <div class="d-flex align-items-center">
                <span class="text-white-50 d-none d-md-inline me-4">Guest View</span>
                <a class="nav-link login-btn" href="login.php">
                    <i class="fas fa-sign-in-alt me-2"></i>Login
                </a>
            </div>
        </div>
    </nav>

    <div class="hero-section">
        <img src="logo.png" class="hero-bg-logo" alt="">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8 text-center text-lg-start mb-4 mb-lg-0">
                    <h1 class="hero-title mb-3">Welcome to EquipTrack</h1>
                    <p class="hero-subtitle mb-4">
                     Instantly check the status of projectors, monitors, keyboards, and other essential computer peripherals. 
                    <br>
                    <span class="opacity-75">Please log in to your account to process equipment reservations and track your borrowing history.</span>
                    </p>                    
                    <a href="login.php" class="btn btn-lg px-4 py-2 hero-cta-btn">
                        <i class="fas fa-sign-in-alt me-2"></i>Get Started to Borrow
                    </a>
                </div>
                <div class="col-lg-4 text-center text-lg-end">
                </div>
            </div>  
        </div>
    </div>

    <div class="container flex-grow-1 mb-5">
        <div class="row mb-5">
            <div class="col-md-4 mb-3 mb-md-0">
                <div class="card stat-card shadow-sm h-100">
                    <div class="card-body d-flex align-items-center p-4">
                        <div class="flex-shrink-0 bg-success-subtle p-3 rounded-3 text-success me-4">
                            <i class="bi bi-check-circle-fill fs-2"></i>
                        </div>
                        <div>
                            <h6 class="mb-1 text-muted text-uppercase fw-bold" style="font-size: 0.8rem;">Available</h6>
                            <h2 class="mb-0 fw-bold text-dark"><?php echo $available_count; ?></h2>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3 mb-md-0">
                <div class="card stat-card shadow-sm h-100">
                    <div class="card-body d-flex align-items-center p-4">
                        <div class="flex-shrink-0 bg-danger-subtle p-3 rounded-3 text-danger me-4">
                            <i class="bi bi-arrow-left-right fs-2"></i>
                        </div>
                        <div>
                            <h6 class="mb-1 text-muted text-uppercase fw-bold" style="font-size: 0.8rem;">Borrowed</h6>
                            <h2 class="mb-0 fw-bold text-dark"><?php echo $borrowed_count; ?></h2>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stat-card shadow-sm h-100">
                    <div class="card-body d-flex align-items-center p-4">
                        <div class="flex-shrink-0 bg-warning-subtle p-3 rounded-3 text-warning me-4">
                            <i class="bi bi-exclamation-triangle-fill fs-2"></i>
                        </div>
                        <div>
                            <h6 class="mb-1 text-muted text-uppercase fw-bold" style="font-size: 0.8rem;">Defective</h6>
                            <h2 class="mb-0 fw-bold text-dark"><?php echo $defective_count; ?></h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card inventory-card shadow-sm border-0">
            <div class="card-header bg-white p-4 border-bottom-0 d-flex flex-column flex-md-row justify-content-between align-items-md-center">
                <div class="mb-3 mb-md-0">
                    <h5 class="mb-0 fw-bold text-dark">Equipment Inventory</h5>
                    <small class="text-muted">Live view of all registered items.</small>
                </div>
                <div class="input-group" style="max-width: 300px;">
                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                    <input type="text" id="searchInput" class="form-control border-start-0 bg-light" placeholder="Search...">
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 bg-white" id="inventoryTable">
                    <thead class="table-light">
                        <tr class="text-uppercase text-muted" style="font-size: 0.75rem;">
                            <th class="ps-4 py-3">Equipment Name</th>
                            <th class="py-3">Serial Number</th>
                            <th class="py-3">Status</th>
                            <th class="py-3 text-center pe-4">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td class="ps-4"><strong><?php echo htmlspecialchars($row['item_name']); ?></strong></td>
                                    <td class="text-muted small"><?php echo htmlspecialchars($row['serial_Number']); ?></td>
                                    <td>
                                        <?php 
                                            $status = $row['status'];
                                            if ($status === 'Available') echo '<span class="badge bg-success-subtle text-success border border-success-subtle badge-status">AVAILABLE</span>';
                                            elseif ($status === 'Borrowed') echo '<span class="badge bg-danger-subtle text-danger border border-danger-subtle badge-status">BORROWED</span>';
                                            elseif ($status === 'Defective') echo '<span class="badge bg-warning-subtle text-warning border border-warning-subtle badge-status">DEFECTIVE</span>';
                                            else echo '<span class="badge bg-secondary-subtle text-secondary badge-status">'.strtoupper(htmlspecialchars($status)).'</span>';
                                        ?>
                                    </td>
                                    <td class="text-center pe-4">
                                        <button type="button" class="btn btn-sm btn-borrow rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#loginPromptModal">
                                            <i class="bi bi-hand-index-thumb me-1"></i> Borrow
                                        </button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="4" class="text-center py-5 text-muted">No equipment found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
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

    <div class="modal fade" id="loginPromptModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header border-bottom-0 pt-4 px-4">
                    <h5 class="modal-title fw-bold">Login Required</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body px-4 pb-4">
                    <div class="d-flex align-items-center">
                        <div class="bg-warning-subtle text-warning p-3 rounded-circle me-3">
                            <i class="bi bi-person-lock fs-3"></i>
                        </div>
                        <div>
                            <p class="mb-0 text-dark fw-semibold">Please log in to continue.</p>
                            <small class="text-muted">You must have an account to request or borrow equipment from the inventory.</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top-0 px-4 pb-4">
                    <button type="button" class="btn btn-light rounded-3 px-4" data-bs-dismiss="modal">Cancel</button>
                    <a href="login.php" class="btn btn-primary rounded-3 px-4" style="background-color: var(--primary-green); border: none;">
                        Go to Login
                    </a>
                </div>
            </div>
        </div>
    </div>

    <?php include '../INCLUDES/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('searchInput').addEventListener('keyup', function() {
            let filter = this.value.toLowerCase();
            let rows = document.querySelectorAll('#inventoryTable tbody tr');
            rows.forEach(row => {
                let text = row.textContent.toLowerCase();
                row.style.display = text.includes(filter) ? '' : 'none';
            });
        });
    </script>

    <script>
    // Save scroll position before the page unloads/reloads
    window.addEventListener('beforeunload', function() {
        sessionStorage.setItem('scrollPosition', window.scrollY);
    });

    // Restore the scroll position when the new page loads
    window.addEventListener('DOMContentLoaded', function() {
        const scrollPosition = sessionStorage.getItem('scrollPosition');
        if (scrollPosition !== null) {
            window.scrollTo(0, parseInt(scrollPosition, 10)); // Changed from 15 to base 10
            sessionStorage.removeItem('scrollPosition'); // Clean up
        }
    });
</script>
</body>
</html>