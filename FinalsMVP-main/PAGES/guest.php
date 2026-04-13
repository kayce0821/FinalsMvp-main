<?php
include '../INCLUDES/database.php';
session_start();

// ONLY check where to redirect if the user is actually logged in
if (isset($_SESSION['role'])) {
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
}

// Fetch Live Counts
$available_count = $mysql->query("SELECT COUNT(*) FROM items WHERE status = 'Available'")->fetch_row()[0];
$borrowed_count  = $mysql->query("SELECT COUNT(*) FROM items WHERE status = 'Borrowed'")->fetch_row()[0];
$defective_count = $mysql->query("SELECT COUNT(*) FROM items WHERE status = 'Defective'")->fetch_row()[0];

// --- PAGINATION LOGIC START ---
$records_per_page = 12; 
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

// 1. Updated count query to only count 'Available' items
$count_query = "SELECT COUNT(*) FROM items WHERE status = 'Available'";
$total_rows = $mysql->query($count_query)->fetch_row()[0];
$total_pages = ceil($total_rows / $records_per_page);
// --- PAGINATION LOGIC END ---

// 2. Updated main query to only fetch 'Available' items
$query = "SELECT item_id, item_name, serial_Number, status FROM items WHERE status = 'Available' ORDER BY item_id DESC LIMIT $records_per_page OFFSET $offset";
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
        .hero-cta-btn {
            background-color: white;
            color: var(--primary-green) !important;
            font-weight: 700;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            border: 1px solid white; 
        }

        .hero-cta-btn:hover {
            background-color: white !important; 
            color: var(--primary-green) !important;
            box-shadow: 0 8px 25px rgba(0,0,0,0.3); 
            transform: translateY(-2px); 
        }
        .login-btn:hover { background-color: white !important; color: var(--primary-green) !important; }

        /* UPDATED HERO SECTION CSS */
        .hero-section {
            padding: 150px 0;
            position: relative;
            /* Added background image with a color overlay to keep text readable */
            background: linear-gradient(130deg, rgb(88, 129, 87) 0%, rgba(88, 129, 87, 0.26) 100%), url('bg1.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            color: white;
            overflow: hidden;
        }
        .hero-title { font-weight: 700; font-size: 3.5rem; }
        .hero-section .container { position: relative; z-index: 1; }

        /* SEARCH BAR SECTION */
        .search-container {
            margin-top: -35px;
            position: relative;
            z-index: 10;
        }
        .search-wrapper {
            background: white;
            padding: 15px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .search-input-lg {
            border: 2px solid #eee;
            border-radius: 10px;
            padding: 12px 20px;
            font-size: 1.1rem;
        }
        .search-input-lg:focus { border-color: var(--primary-light); box-shadow: none; }

        /* CARD STYLES */
        .stat-card { border-radius: 12px; transition: transform 0.2s; border: none; }
        .stat-card:hover { transform: translateY(-5px); }
        
        .item-card {
            border: none;
            border-radius: 15px;
            overflow: hidden;
            transition: all 0.3s ease;
            background: white;
        }
        .item-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 25px rgba(0,0,0,0.1) !important;
        }
        .item-img-container {
            height: 180px;
            background-color: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }
        .item-img-container i {
            font-size: 4rem;
            color: #dee2e6;
        }
        .badge-floating {
            position: absolute;
            top: 15px;
            right: 15px;
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
        }

        .pagination-custom .page-item .page-link {
            border: none; color: #6c757d; border-radius: 0.5rem; margin: 0 0.2rem; transition: all 0.2s;
        }
        .pagination-custom .page-item.active .page-link { 
            background-color: var(--primary-green); color: white; box-shadow: 0 4px 6px rgba(58, 90, 64, 0.2);
        }
        .pagination-custom .page-item .page-link:hover:not(.active) {
            background-color: #eaedf1; color: var(--primary-green);
        }

        /* Responsive Mobile Adjustments */
        @media (max-width: 768px) {
            .hero-section {
                padding: 80px 0; 
                background-position: center top;
            }
            .hero-title {
                font-size: 2.2rem; 
            }
            .hero-subtitle {
                font-size: 1rem !important; 
            }
            .search-container {
                margin-top: -25px; 
            }
            .search-wrapper {
                margin: 0 15px; 
                padding: 10px;
            }
            .search-input-lg {
                font-size: 1rem;
                padding: 10px 15px;
            }
            .pagination-custom {
                flex-wrap: wrap;
                justify-content: center;
                gap: 0.25rem;
            }
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

        <div class="container text-center text-lg-start">
            <h1 class="hero-title mb-3">Welcome to EquipTrack!</h1>
            <p class="hero-subtitle mb-4 fs-5 opacity-90">
                This system is designed to make borrowing equipment from <strong>UCC Congressional</strong><br class="d-none d-md-block">
                <strong>MIS</strong> easier and more efficient, By replacing manual logging with a digital interface <br class="d-none d-md-block">
                it ensures that every hardware asset from desktop units to peripheral devices is <br class="d-none d-md-block"> accounted for in real-time!<br>
            </p>                    
            <a href="login.php" class="btn btn-lg px-5 py-3 hero-cta-btn">
                <i class="fas fa-sign-in-alt me-2"></i>Borrow Equipment!
            </a>
        </div>
    </div>

    <div class="container search-container">
        <div class="row justify-content-center mt-5 pt-5">
            <div class="col-md-8">
                <div class="search-wrapper">
                    <div class="input-group">
                        <span class="input-group-text bg-transparent border-0 fs-4 text-muted"><i class="bi bi-search"></i></span>
                        <input type="text" id="searchInput" class="form-control search-input-lg border-0" placeholder="Search for equipment (e.g. Projector, Keyboard)...">
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="container my-5">

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="fw-bold text-dark mb-0">Equipment Inventory</h4>
        </div>

        <div class="row g-4" id="inventoryGrid">
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): 
                    $status = $row['status'];
                    $badgeClass = '';
                    if ($status === 'Available') $badgeClass = 'bg-success text-white';
                    elseif ($status === 'Borrowed') $badgeClass = 'bg-danger text-white';
                    elseif ($status === 'Defective') $badgeClass = 'bg-warning text-dark';
                    else $badgeClass = 'bg-secondary text-white';
                ?>
                <div class="col-12 col-md-6 col-lg-3 inventory-item">
                    <div class="card h-100 item-card shadow-sm">
                        <div class="item-img-container">
                            <span class="badge badge-floating <?php echo $badgeClass; ?>">
                                <?php echo htmlspecialchars($status); ?>
                            </span>
                            <i class="bi bi-device-ssd"></i>
                        </div>
                        <div class="card-body p-4 text-center">
                            <h6 class="text-muted small mb-1"><?php echo htmlspecialchars($row['serial_Number']); ?></h6>
                            <h5 class="card-title fw-bold mb-0"><?php echo htmlspecialchars($row['item_name']); ?></h5>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12 text-center py-5">
                    <i class="bi bi-search fs-1 text-muted mb-3"></i>
                    <p class="text-muted">No equipment found in the registry.</p>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($total_pages > 1): ?>
        <div class="d-flex justify-content-center mt-5">
            <nav aria-label="Page navigation">
                <ul class="pagination pagination-custom">
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

    <?php include '../INCLUDES/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('searchInput').addEventListener('keyup', function() {
            let filter = this.value.toLowerCase();
            let cards = document.querySelectorAll('.inventory-item');
            cards.forEach(card => {
                let text = card.querySelector('.card-body').textContent.toLowerCase();
                card.style.display = text.includes(filter) ? '' : 'none';
            });
        });

        window.addEventListener('beforeunload', function() {
            sessionStorage.setItem('scrollPosition', window.scrollY);
        });
        window.addEventListener('DOMContentLoaded', function() {
            const scrollPosition = sessionStorage.getItem('scrollPosition');
            if (scrollPosition !== null) {
                window.scrollTo(0, parseInt(scrollPosition, 10));
                sessionStorage.removeItem('scrollPosition');
            }
        });
    </script>
</body>
</html>
