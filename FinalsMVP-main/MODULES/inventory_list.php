
<?php
session_start(); // <-- CRUCIAL: Must be at the very top!
include '../INCLUDES/database.php';

// Security Check
if (!isset($_SESSION['role'])) {
    header("Location: ../PAGES/guest.php");
    exit();
}

// 1. DYNAMIC ROUTING & SIDEBAR LOGIC
if ($_SESSION['role'] === 'Admin') {
    $dashboard_link = '../PAGES/adminDashboard.php';
    $sidebar_file = '../INCLUDES/sidebarAdmin.php';
} else if ($_SESSION['role'] === 'Staff') {
    $dashboard_link = '../PAGES/staffDashboard.php';
    $sidebar_file = '../INCLUDES/sidebarStaff.php';
} else {
    // If somehow a non-Admin/Staff role got here, log them out for safety
    header("Location: ../PAGES/logout.php");
    exit();
}

$query = "SELECT item_id, item_name, serial_Number AS serial_num, status FROM items ORDER BY item_id DESC";
$result = $mysql->query($query);
?>

<?php if (isset($_GET['status'])): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            <?php if ($_GET['status'] == 'requested'): ?>
                Swal.fire('Requested!', 'Your request has been sent to the MIS faculty for approval.', 'success');
            <?php elseif ($_GET['status'] == 'unavailable'): ?>
                Swal.fire('Oops!', 'Sorry, this item was just borrowed or requested by someone else.', 'warning');
            <?php elseif ($_GET['status'] == 'error'): ?>
                Swal.fire('Error', 'There was a database error processing your request.', 'error');
            <?php endif; ?>
        });
    </script>
<?php endif; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Equipment Inventory - EquipTrack</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        body { background-color: #f8f9fa; padding-top: 40px; }
        .container { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        .table-hover tbody tr:hover { background-color: #f1f3f5; }
        .badge-status { font-size: 0.9em; padding: 0.5em 0.8em; min-width: 85px; }
    </style>
</head>
<body class="d-flex flex-column min-vh-100" style="background-color: #f8f9fa; padding-top: 40px;">

<div class="container flex mb-5">   
    
<div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0 text-dark">MIS Equipment Inventory</h2>
        <a href="<?php echo $dashboard_link; ?>" class="btn btn-outline-secondary">Back to Dashboard</a>
    </div>

    <div class="table-responsive">
        <table class="table table-hover table-bordered align-middle">
            <thead class="table-dark">
                <tr>
                    <th>Item Name</th>
                    <th>Serial Number</th>
                    <th class="text-center">Status</th>
                    <th class="text-center">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($row['item_name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($row['serial_num']); ?></td>
                            <td class="text-center">
                                <?php 
                                    $status = $row['status'];
                                    if ($status === 'Available') echo '<span class="badge bg-success badge-status">Available</span>';
                                    elseif ($status === 'Borrowed') echo '<span class="badge bg-info text-dark badge-status">Borrowed</span>';
                                    elseif ($status === 'Defective') echo '<span class="badge bg-warning text-dark badge-status">Defective</span>';
                                    elseif ($status === 'Lost') echo '<span class="badge bg-danger badge-status">Lost</span>';
                                    else echo '<span class="badge bg-secondary badge-status">Unknown</span>';
                                ?>
                            </td>
                            <td class="text-center">
                                <?php if ($status === 'Available'): ?>
                                    <button class="btn btn-primary btn-sm request-btn" 
                                            data-id="<?php echo htmlspecialchars($row['item_id']); ?>" 
                                            data-name="<?php echo htmlspecialchars($row['item_name']); ?>">
                                        Request Item
                                    </button>
                                <?php else: ?>
                                    <button class="btn btn-secondary btn-sm" disabled>Unavailable</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="5" class="text-center text-muted py-4">No equipment found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const requestButtons = document.querySelectorAll('.request-btn');
        requestButtons.forEach(button => {
            button.addEventListener('click', function() {
                const itemName = this.getAttribute('data-name');
                Swal.fire({
                    title: 'Request Equipment?',
                    html: `Are you sure you want to request the <strong>${itemName}</strong>?`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#0d6efd',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, Request it!',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.fire('Requested!', `${itemName} requested successfully.`, 'success');
                    }
                });
            });
        });
    });
</script>
<?php include '../INCLUDES/footer.php'; ?>
</body>
</html>