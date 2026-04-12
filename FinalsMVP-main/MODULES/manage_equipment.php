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

// ADD EQUIPMENT
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_equipment'])) {
    $item_name = $_POST['item_name'];
    $serial_number = $_POST['serial_Number']; 
    $status = $_POST['status'];

    $sql = "INSERT INTO items (item_name, serial_Number, status) VALUES (?, ?, ?)";
    
    if ($stmt = $mysql->prepare($sql)) {
        $stmt->bind_param("sss", $item_name, $serial_number, $status);
        if ($stmt->execute()) {
            $message = "<script>document.addEventListener('DOMContentLoaded', function() { Swal.fire('Success!', 'Equipment Added.', 'success'); });</script>";
        } else {
            // Prints the exact database error to help you debug
            $error_msg = addslashes($stmt->error);
            $message = "<script>document.addEventListener('DOMContentLoaded', function() { Swal.fire('Database Error', 'Failed to add: {$error_msg}', 'error'); });</script>";
        }
        $stmt->close();
    } else {
        // Prints an error if the SQL syntax fails to prepare
        $error_msg = addslashes($mysql->error);
        $message = "<script>document.addEventListener('DOMContentLoaded', function() { Swal.fire('Query Error', 'SQL Error: {$error_msg}', 'error'); });</script>";
    }
}

// EDIT EQUIPMENT
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_equipment'])) {
    $item_id = $_POST['item_id'];
    $item_name = $_POST['item_name'];
    $serial_number = $_POST['serial_Number']; 
    $status = $_POST['status'];

    $sql = "UPDATE items SET item_name = ?, serial_Number = ?, status = ? WHERE item_id = ?";
    if ($stmt = $mysql->prepare($sql)) {
        $stmt->bind_param("sssi", $item_name, $serial_number, $status, $item_id);
        if ($stmt->execute()) {
            $message = "<script>document.addEventListener('DOMContentLoaded', function() { Swal.fire('Updated!', 'Equipment has been updated.', 'success'); });</script>";
        } else {
            $error_msg = addslashes($stmt->error);
            $message = "<script>document.addEventListener('DOMContentLoaded', function() { Swal.fire('Error', 'Failed to update: {$error_msg}', 'error'); });</script>";
        }
        $stmt->close();
    }
}

// ARCHIVE EQUIPMENT (Instead of Hard Delete)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_equipment'])) {
    $item_id = $_POST['item_id'];

    // We set the status to 'Archived' instead of deleting
    $sql = "UPDATE items SET status = 'Archived' WHERE item_id = ?";
    if ($stmt = $mysql->prepare($sql)) {
        $stmt->bind_param("i", $item_id);
        if ($stmt->execute()) {
            $message = "<script>document.addEventListener('DOMContentLoaded', function() { Swal.fire('Archived!', 'Equipment moved to archives.', 'success'); });</script>";
        } else {
            $error_msg = addslashes($stmt->error);
            $message = "<script>document.addEventListener('DOMContentLoaded', function() { Swal.fire('Error', 'Failed to archive: {$error_msg}', 'error'); });</script>";
        }
        $stmt->close();
    }
}

// --- PAGINATION LOGIC START ---
$records_per_page = 10; // Change this number to adjust rows per page
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

// Count total items for pagination
$count_query = "SELECT COUNT(*) FROM items WHERE status != 'Archived'";
$total_rows = $mysql->query($count_query)->fetch_row()[0];
$total_pages = ceil($total_rows / $records_per_page);
// --- PAGINATION LOGIC END ---

// FETCH TABLE (With LIMIT and OFFSET added)
$query = "SELECT item_id, item_name, serial_Number, status FROM items WHERE status != 'Archived' ORDER BY item_id DESC LIMIT $records_per_page OFFSET $offset";
$result = $mysql->query($query); 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Equipment - EquipTrack</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
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

        /* Custom Form Inputs */
        .custom-input {
            border-radius: 0.5rem;
            padding: 0.6rem 1rem;
            border: 1px solid #dee2e6;
            background-color: #f8f9fa;
            transition: all 0.2s ease-in-out;
        }
        .custom-input:focus {
            background-color: #fff;
            border-color: var(--brand-color);
            box-shadow: 0 0 0 0.25rem rgba(58, 90, 64, 0.15);
        }

        /* Custom Buttons */
        .btn-brand {
            background-color: var(--brand-color);
            color: white;
            border: none;
            transition: background-color 0.3s ease, transform 0.2s ease;
        }
        .btn-brand:hover {
            background-color: var(--brand-hover);
            color: white;
            transform: translateY(-1px);
        }
        .action-btn {
            border-radius: 0.4rem;
            transition: all 0.2s;
        }
        .action-btn:hover {
            transform: translateY(-2px);
        }
        
        /* Table Styling */
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
        .table tbody tr {
            transition: background-color 0.2s ease;
        }
        .table tbody tr:hover {
            background-color: #f8fbfa;
        }
        .badge-status { 
            font-size: 0.80em; 
            padding: 0.5em 1em; 
            min-width: 90px; 
            letter-spacing: 0.5px; 
            font-weight: 600;
        }

        /* Card Customization */
        .card-header-custom {
            border-bottom: 1px solid #eaedf1 !important;
            padding-bottom: 1.25rem !important;
            padding-top: 1.25rem !important;
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

        /* Modals */
        .modal-content {
            border: none;
            border-radius: 1rem;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        /* Responsive Mobile Adjustments */
        @media (max-width: 768px) {
            .content-wrapper, .content-wrapper.expanded { 
                width: 100%; 
            }
            
            /* Add padding adjustments for mobile screen */
            .container-fluid.p-4 {
                padding: 1.5rem 1rem !important;
            }

            /* Stack header content and center */
            .d-flex.flex-column.flex-md-row {
                align-items: stretch !important;
            }
            .d-flex.flex-column.flex-md-row > div {
                text-align: center;
                margin-bottom: 1rem;
            }

            /* Make the "Add Equipment" button full width on mobile */
            .btn-brand {
                width: 100%;
                padding: 0.75rem !important;
            }

            /* Prevent table data from squishing too much, allowing scroll */
            .table th, .table td {
                white-space: nowrap;
                padding: 0.75rem 0.5rem !important;
            }

            .badge-status {
                min-width: 75px;
                padding: 0.4em 0.8em;
                font-size: 0.75em;
            }

            /* Better mobile pagination wrapping */
            .pagination-custom {
                flex-wrap: wrap;
                justify-content: center;
                gap: 0.25rem;
            }
            
            .card-footer.d-flex {
                justify-content: center !important;
            }
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
                            <span class="nav-link font-weight-bold text-light p-0" style="font-size: 1.1rem; letter-spacing: 0.5px;">Manage Equipment</span>
                        </li>
                    </ul>
                </div>
            </nav>

            <div class="container-fluid p-4">
                
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
                    <div>
                        <h4 class="mb-0 text-dark fw-bold">Equipment Inventory</h4>
                        <p class="text-muted small mb-0 mt-1">Manage, update, and track all system equipments.</p>
                    </div>
                    
                    <button type="button" class="btn btn-brand px-4 py-2 fw-semibold shadow-sm rounded-pill" data-bs-toggle="modal" data-bs-target="#addEquipmentModal">
                        <i class="bi bi-plus-lg me-2"></i> Add New Equipment
                    </button>
                </div>

                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-header bg-white card-header-custom border-0 px-4">
                        <h5 class="mb-0 text-dark fw-bold">
                            <i class="bi bi-pc-display-horizontal text-primary me-2" style="color: var(--brand-color)!important;"></i> Current Tech Stock
                        </h5>
                    </div>
                    
                    <div class="card-body p-0">
                        <div class="table-custom-wrapper m-3">
                            <div class="table-responsive">
                                <table class="table align-middle mb-0 bg-white">
                                    <thead>
                                        <tr class="text-uppercase" style="font-size: 0.80rem;">
                                            <th class="ps-4 py-3 border-0">Item Name</th>
                                            <th class="py-3 border-0">Serial Number</th>
                                            <th class="text-center py-3 border-0">Status</th>
                                            <th class="text-center py-3 border-0">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($result && $result->num_rows > 0): ?>
                                            <?php while ($row = $result->fetch_assoc()): ?>
                                                <tr>
                                                    <td class="ps-4 py-3 fw-bold text-dark"><?php echo htmlspecialchars($row['item_name']); ?></td>
                                                    <td class="py-3 text-muted fw-semibold font-monospace" style="font-size: 0.9rem;">
                                                        <?php echo htmlspecialchars($row['serial_Number']); ?>
                                                    </td>
                                                    <td class="text-center py-3">
                                                        <?php 
                                                            $status = $row['status'];
                                                            if ($status === 'Available') echo '<span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill badge-status text-uppercase">Available</span>';
                                                            elseif ($status === 'Borrowed') echo '<span class="badge bg-info-subtle text-info-emphasis border border-info-subtle rounded-pill badge-status text-uppercase">Borrowed</span>';
                                                            elseif ($status === 'Defective') echo '<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill badge-status text-uppercase">Defective</span>';
                                                            elseif ($status === 'Lost') echo '<span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill badge-status text-uppercase">Lost</span>';
                                                            else echo '<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle rounded-pill badge-status">Unknown</span>';
                                                        ?>
                                                    </td>
                                                    <td class="text-center py-3">
                                                        <button class="btn btn-sm btn-light border action-btn px-2 py-1 text-primary edit-btn"
                                                                data-bs-toggle="tooltip" title="Edit Item"
                                                                data-id="<?php echo htmlspecialchars($row['item_id']); ?>"
                                                                data-name="<?php echo htmlspecialchars($row['item_name']); ?>"
                                                                data-serial="<?php echo htmlspecialchars($row['serial_Number']); ?>"
                                                                data-status="<?php echo htmlspecialchars($row['status']); ?>">
                                                            <i class="bi bi-pencil-square fs-6"></i>
                                                        </button>
                                                        
                                                        <button class="btn btn-sm btn-light border action-btn px-2 py-1 text-danger ms-1 delete-btn"
                                                                data-bs-toggle="tooltip" title="Archive Item"
                                                                data-id="<?php echo htmlspecialchars($row['item_id']); ?>"
                                                                data-name="<?php echo htmlspecialchars($row['item_name']); ?>">
                                                            <i class="bi bi-trash3 fs-6"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="4" class="text-center py-5 text-muted">
                                                    <div class="d-flex flex-column align-items-center">
                                                        <i class="bi bi-box-seam fs-1 text-light mb-2"></i>
                                                        <span>No equipment found in inventory.</span>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
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

<div class="modal fade" id="addEquipmentModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-white border-bottom pt-4 px-4 pb-3">
        <h5 class="modal-title fw-bold text-dark"><i class="bi bi-plus-circle-fill text-success me-2"></i>Add New Equipment</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST">
          <div class="modal-body p-4">
            <div class="mb-3">
                <label class="form-label text-dark fw-semibold small">Item Name</label>
                <input type="text" class="form-control custom-input" name="item_name" placeholder="e.g. Dell Monitor" required>
            </div>
            <div class="mb-3">
                <label class="form-label text-dark fw-semibold small">Serial Number</label>
                <input type="text" class="form-control custom-input font-monospace" name="serial_Number" placeholder="e.g. SN-12345" required>
            </div>
            <div class="mb-2">
                <label class="form-label text-dark fw-semibold small">Status</label>
                <select class="form-select custom-input" name="status">
                    <option value="Available">Available</option>
                </select>
            </div>
          </div>
          <div class="modal-footer bg-light border-top-0 px-4 py-3">
            <button type="button" class="btn btn-light border px-4" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" name="add_equipment" class="btn btn-success fw-bold px-4 shadow-sm">Save Equipment</button>
          </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="editEquipmentModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-white border-bottom pt-4 px-4 pb-3">
        <h5 class="modal-title fw-bold text-dark"><i class="bi bi-pencil-square text-primary me-2"></i>Edit Equipment</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST">
          <div class="modal-body p-4">
            <input type="hidden" name="item_id" id="edit_item_id">
            <div class="mb-3">
                <label class="form-label text-dark fw-semibold small">Item Name</label>
                <input type="text" class="form-control custom-input" name="item_name" id="edit_item_name" required>
            </div>
            <div class="mb-3">
                <label class="form-label text-dark fw-semibold small">Serial Number</label>
                <input type="text" class="form-control custom-input font-monospace" name="serial_Number" id="edit_serial_Number" required>
            </div>
            <div class="mb-2">
                <label class="form-label text-dark fw-semibold small">Status</label>
                <select class="form-select custom-input" name="status" id="edit_status" required>
                    <option value="Available">Available</option>
                    <option value="Borrowed">Borrowed</option>
                    <option value="Defective">Defective</option>
                    <option value="Lost">Lost</option>
                </select>
            </div>
          </div>
          <div class="modal-footer bg-light border-top-0 px-4 py-3">
            <button type="button" class="btn btn-light border px-4" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" name="edit_equipment" class="btn btn-primary fw-bold px-4 shadow-sm">Update Details</button>
          </div>
      </form>
    </div>
  </div>
</div>

<form id="deleteForm" method="POST" style="display: none;">
    <input type="hidden" name="item_id" id="delete_item_id">
    <input type="hidden" name="delete_equipment" value="1">
</form>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        
        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
          return new bootstrap.Tooltip(tooltipTriggerEl)
        });

        // Sidebar Toggle Logic
        document.getElementById('sidebarToggle').addEventListener('click', function(e) {
            e.preventDefault();
            document.getElementById('mainContent').classList.toggle('expanded');
        });

        // Edit Button Logic
        const editButtons = document.querySelectorAll('.edit-btn');
        const editModal = new bootstrap.Modal(document.getElementById('editEquipmentModal'));
        
        editButtons.forEach(button => {
            button.addEventListener('click', function() {
                // Hide tooltip when clicking to avoid it getting stuck
                const tooltip = bootstrap.Tooltip.getInstance(this);
                if (tooltip) tooltip.hide();

                document.getElementById('edit_item_id').value = this.getAttribute('data-id');
                document.getElementById('edit_item_name').value = this.getAttribute('data-name');
                document.getElementById('edit_serial_Number').value = this.getAttribute('data-serial');
                document.getElementById('edit_status').value = this.getAttribute('data-status');
                editModal.show();
            });
        });

        // Delete Button Logic
        const deleteButtons = document.querySelectorAll('.delete-btn');
        deleteButtons.forEach(button => {
            button.addEventListener('click', function() {
                // Hide tooltip
                const tooltip = bootstrap.Tooltip.getInstance(this);
                if (tooltip) tooltip.hide();

                const itemId = this.getAttribute('data-id');
                const itemName = this.getAttribute('data-name');

                Swal.fire({
                    title: 'Archive Equipment?',
                    html: `Are you sure you want to move <strong>${itemName}</strong> to the archives?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, archive it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        document.getElementById('delete_item_id').value = itemId;
                        document.getElementById('deleteForm').submit();
                    }
                });
            });
        });
    });
</script>

</body>
</html>