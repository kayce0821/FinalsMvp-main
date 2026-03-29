<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EquipTrack | Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
</head>
<body class="bg-light">

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
        <div class="container">
            <a class="navbar-brand" href="#"><i class="bi bi-pc-display"></i> EquipTrack</a>
            <span class="navbar-text ms-auto">Logged in as: <strong>John Benedict Jamora</strong></span>
        </div>
    </nav>

    <div class="container">
        
        <div class="row mb-4 text-center">
            <div class="col-md-4">
                <div class="card bg-success text-white shadow-sm">
                    <div class="card-body">
                        <h5>Available Items</h5>
                        <h2 class="display-6">15</h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-danger text-white shadow-sm">
                    <div class="card-body">
                        <h5>Borrowed Items</h5>
                        <h2 class="display-6">5</h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-warning text-dark shadow-sm">
                    <div class="card-body">
                        <h5>Defective</h5>
                        <h2 class="display-6">2</h2>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white font-weight-bold">Process New Borrowing</div>
            <div class="card-body">
                <form action="ACTIONS\process_borrow.php" method="POST" class="row g-3">
                    <div class="col-md-5">
                        <label class="form-label">Student ID</label>
                        <input type="text" name="student_id" class="form-control" placeholder="e.g. 2024-1001" required>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label">Equipment ID</label>
                        <input type="text" name="item_id" class="form-control" placeholder="e.g. M001" required>
                    </div>
                    <div class="col-md-2 d-grid">
                        <label class="form-label">&nbsp;</label>
                        <button type="submit" class="btn btn-primary">Submit</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-white">Currently Borrowed Equipment</div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Student ID</th>
                            <th>Equipment</th>
                            <th>Borrow Time</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>2024-0015</td>
                            <td>Logitech Mouse (M001)</td>
                            <td>10:30 AM</td>
                            <td class="text-center">
                                <a href="process_return.php?id=1" class="btn btn-sm btn-outline-primary">One-Click Return</a>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>