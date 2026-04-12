<?php
session_start();
include '../INCLUDES/database.php';
$message = "";

// Security check: ONLY Students
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Student') {
    header("Location: login.php");
    exit();
}

$sidebar_file = '../INCLUDES/sidebarStudent.php';

// 1. Get the Student's actual ID from the students table by linking via email
$student_id = "";
$stmt = $mysql->prepare("SELECT s.student_id FROM students s JOIN user u ON s.email = u.email WHERE u.user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();
if ($res) {
    $student_id = $res['student_id'];
}
$stmt->close();

// 2. Handle Borrow Request Submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['request_item'])) {
    $item_id = $_POST['item_id'];
    $duration = (int)$_POST['duration_hours']; // NEW: Catch the duration
    
    // Make sure they don't already have a pending request for this exact item
    $check_req = $mysql->prepare("SELECT * FROM requests WHERE student_id = ? AND item_id = ? AND request_status = 'Pending'");
    $check_req->bind_param("si", $student_id, $item_id);
    $check_req->execute();
    
    if ($check_req->get_result()->num_rows > 0) {
        $message = "<script>document.addEventListener('DOMContentLoaded', function() { Swal.fire({title: 'Already Requested', text: 'You already have a pending request for this item. Please wait for staff approval.', icon: 'info', confirmButtonColor: '#3a5a40'}); });</script>";
    } else {
        // Insert into the requests table with the duration!
        $ins = $mysql->prepare("INSERT INTO requests (student_id, item_id, duration_hours, request_status) VALUES (?, ?, ?, 'Pending')");
        $ins->bind_param("sii", $student_id, $item_id, $duration);
        if ($ins->execute()) {
            $message = "<script>document.addEventListener('DOMContentLoaded', function() { Swal.fire({title: 'Request Sent!', text: 'Your borrow request for $duration hours has been forwarded to the MIS faculty.', icon: 'success', confirmButtonColor: '#3a5a40'}); });</script>";
        }
    }
}

// Fetch ONLY Available Items for them to borrow
$query = "SELECT item_id, item_name, serial_Number FROM items WHERE status = 'Available' ORDER BY item_id DESC";
$result = $mysql->query($query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Available Items - EquipTrack</title>
    
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

        /* Custom Button */
        .btn-borrow { 
            border: 1.5px solid var(--brand-color); 
            color: var(--brand-color); 
            font-weight: 600; 
            transition: all 0.2s ease; 
        }
        .btn-borrow:hover { 
            background-color: var(--brand-color); 
            color: white; 
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(58, 90, 64, 0.2);
        }

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
<?php if(!empty($message)) echo $message; ?>

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
                            <span class="nav-link font-weight-bold text-light p-0" style="font-size: 1.1rem; letter-spacing: 0.5px;">Browse Equipment</span>
                        </li>
                    </ul>
                </div>
            </nav>

            <div class="container-fluid p-4">
                
                <div class="mb-4">
                    <h4 class="mb-0 text-dark fw-bold">Available Equipment</h4>
                    <p class="text-muted small mb-0 mt-1">Select an item below to request a borrow.</p>
                </div>

                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white card-header-custom border-0 px-4">
                        <h5 class="mb-0 text-dark fw-bold">
                            <i class="bi bi-box-seam text-primary me-2" style="color: var(--brand-color)!important;"></i> Ready to Borrow
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-custom-wrapper m-3">
                            <div class="table-responsive">
                                <table class="table align-middle mb-0 bg-white">
                                    <thead>
                                        <tr class="text-uppercase" style="font-size: 0.80rem;">
                                            <th class="ps-4 py-3 border-0">Equipment Name</th>
                                            <th class="py-3 border-0">Serial Number</th>
                                            <th class="py-3 text-center pe-4 border-0">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($result && $result->num_rows > 0): ?>
                                            <?php while ($row = $result->fetch_assoc()): ?>
                                                <tr>
                                                    <td class="ps-4 py-3 fw-bold text-dark" style="font-size: 0.95rem;">
                                                        <?php echo htmlspecialchars($row['item_name']); ?>
                                                    </td>
                                                    <td class="py-3 text-muted fw-semibold font-monospace" style="font-size: 0.9rem;">
                                                        <?php echo htmlspecialchars($row['serial_Number']); ?>
                                                    </td>
                                                    <td class="text-center pe-4 py-3">
                                                        <button type="button" class="btn btn-sm btn-borrow rounded-pill px-4 py-1 request-btn"
                                                                data-id="<?php echo htmlspecialchars($row['item_id']); ?>"
                                                                data-name="<?php echo htmlspecialchars($row['item_name']); ?>">
                                                            <i class="bi bi-hand-index-thumb me-1"></i> Request
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="3" class="text-center py-5 text-muted">
                                                    <div class="d-flex flex-column align-items-center">
                                                        <i class="bi bi-inbox fs-1 text-light mb-2"></i>
                                                        <span>No equipment is currently available.</span>
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

   <form id="requestForm" method="POST" style="display: none;">
        <input type="hidden" name="item_id" id="request_item_id">
        <input type="hidden" name="duration_hours" id="request_duration">
        <input type="hidden" name="request_item" value="1">
    </form>

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

            // Borrow Request Logic with Dropdown
            const requestButtons = document.querySelectorAll('.request-btn');
            requestButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const itemId = this.getAttribute('data-id');
                    const itemName = this.getAttribute('data-name');

                    Swal.fire({
                        title: 'Request Item',
                        html: `
                            <p class="mb-3">Requesting <strong>${itemName}</strong>.</p>
                            <label class="form-label text-muted small fw-bold">How long do you need it?</label>
                            <select id="swal-duration" class="form-select mx-auto w-75 border-success">
                                <option value="3">3 Hours (Standard)</option>
                                <option value="4">4 Hours (Extended)</option>
                                <option value="5">5 Hours (Max)</option>
                            </select>
                        `,
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonColor: '#3a5a40',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: 'Send Request',
                        preConfirm: () => {
                            return document.getElementById('swal-duration').value;
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            document.getElementById('request_item_id').value = itemId;
                            document.getElementById('request_duration').value = result.value;
                            document.getElementById('requestForm').submit();
                        }
                    });
                });
            });
        });
    </script>
</body>
</html>