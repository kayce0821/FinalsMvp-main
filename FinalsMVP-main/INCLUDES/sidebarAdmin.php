<?php 
// Get the current filename to automatically highlight the active menu item
$current_page = basename($_SERVER['PHP_SELF']); 

// Grab the user's name from the session (default to Admin if not set)
$user_name = isset($_SESSION['full_name']) ? $_SESSION['full_name'] : 'Admin';
?>

<style>
    /* Sidebar Styling */
    .sidebar {
        width: 250px; background-color: #3a5a40; color: white;
        display: flex; flex-direction: column; flex-shrink: 0; min-height: 100vh;
        transition: margin-left 0.3s ease, left 0.3s ease;
        z-index: 1050;
    }
    
    .sidebar.collapsed { margin-left: -250px; }
    
    .sidebar .brand-link {
        padding: 15px 20px; font-size: 1.25rem; color: white;
        text-decoration: none; border-bottom: 1px solid rgba(255,255,255,0.1); display: block;
    }
    
    .sidebar .user-panel {
        padding: 15px 20px;
        border-bottom: 1px solid rgba(255,255,255,0.1);
        display: flex; align-items: center;
    }

    .nav-sidebar { 
        padding: 10px 0; 
        list-style: none; 
        margin: 0; 
        display: flex; 
        flex-direction: column; 
        flex-grow: 1; 
    }

    .nav-sidebar .nav-link {
        color: rgba(255,255,255,0.8); padding: 12px 20px; text-decoration: none;
        display: flex; align-items: center; transition: 0.2s; cursor: pointer;
    }

    /* Sub-menu styling */
    .nav-sidebar .sub-menu {
        list-style: none;
        padding: 0;
        background-color: rgba(0, 0, 0, 0.15);
        display: none; /* Hidden by default */
        overflow: hidden;
    }

    .nav-sidebar .sub-menu.show { display: block; }
    
    .nav-sidebar .sub-menu .nav-link { padding-left: 50px; font-size: 0.9rem; }

    .nav-sidebar .nav-link:hover { color: white; background-color: rgba(255,255,255,0.1); }
    .nav-sidebar .nav-link.active { background-color: #007bff; color: white; border-radius: 0; }
    .nav-sidebar .nav-link i { margin-right: 15px; width: 20px; text-align: center; }

    /* Rotate arrow logic */
    .rotate-icon { transition: transform 0.3s; }
    .nav-link[aria-expanded="true"] .rotate-icon { transform: rotate(90deg); }

    @media (max-width: 768px) {
        .sidebar { position: fixed; left: -250px; margin-left: 0 !important; }
        .sidebar.show-mobile { left: 0; }
    }
</style>

<aside class="sidebar shadow" id="mainSidebar">
    <a href="../PAGES/adminDashboard.php" class="brand-link">
        <span class="fw-light"><strong>EQUIP</strong>TRACK</span>
    </a>

    <div class="user-panel">
        <i class="bi bi-person-circle text-white me-3" style="font-size: 2.2rem;"></i>
        <div class="overflow-hidden">
            <small class="text-white-50 d-block" style="font-size: 0.75rem; line-height: 1;">Welcome back,</small>
            <span class="text-white fw-bold text-truncate d-block" style="max-width: 160px;">
                <?php echo htmlspecialchars($user_name); ?>
            </span>
        </div>
    </div>

    <ul class="nav-sidebar mt-2">
        <li>
            <a href="../PAGES/adminDashboard.php" class="nav-link <?php echo ($current_page == 'adminDashboard.php') ? 'active' : ''; ?>">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>
        </li>
        <li>
            <a href="../MODULES/manage_users.php" class="nav-link <?php echo ($current_page == 'manage_users.php') ? 'active' : ''; ?>">
                <i class="bi bi-people"></i> Manage Users
            </a>
        </li>
        <li>
            <a href="../MODULES/manage_equipment.php" class="nav-link <?php echo ($current_page == 'manage_equipment.php') ? 'active' : ''; ?>">
                <i class="bi bi-bag"></i> Manage Equipments
            </a>
        </li>
        <li>
            <a href="../MODULES/requests.php" class="nav-link <?php echo ($current_page == 'requests.php') ? 'active' : ''; ?>">
                <i class="bi bi-inbox"></i> Requests
            </a>
        </li>
        <li>
            <a href="../MODULES/reports.php" class="nav-link <?php echo ($current_page == 'reports.php') ? 'active' : ''; ?>">
                <i class="bi bi-file-earmark-bar-graph"></i> Reports
            </a>
        </li>
        
        <li class="px-4 mt-3 mb-1 text-uppercase fw-bold text-white-50" style="font-size: 0.7rem; letter-spacing: 1px;">
            Archives
        </li>

        <li>
            <a href="../MODULES/archived.php" class="nav-link <?php echo ($current_page == 'archived.php') ? 'active' : ''; ?>">
                <i class="bi bi-person-dash"></i> Archived Users
            </a>
        </li>
        <li>
            <a href="../MODULES/archived_equipment.php" class="nav-link <?php echo ($current_page == 'archived_equipment.php') ? 'active' : ''; ?>">
                <i class="bi bi-box-seam"></i> Archived Equipment
            </a>
        </li>

        <li class="mt-auto border-top border-secondary">
            <a href="../MODULES/userSettings.php" class="nav-link <?php echo ($current_page == 'userSettings.php') ? 'active' : ''; ?>">
                <i class="bi bi-gear"></i> 
                <span>Account Settings</span>
            </a>
        </li>

        <li>
            <a href="../PAGES/logout.php" class="nav-link" id="sidebarLogout">
                <i class="bi bi-box-arrow-left"></i> Logout
            </a>
        </li>
    </ul>
</aside>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const sidebar = document.getElementById('mainSidebar');
        const mainContent = document.getElementById('mainContent');
        const toggleBtn = document.getElementById('sidebarToggle');
        const logoutBtn = document.getElementById('sidebarLogout');
        
        // 2. Sidebar Toggle Logic (Desktop & Mobile)
        if (toggleBtn) {
            const icon = toggleBtn.querySelector('i');
            function updateToggleIcon() {
                const isMobile = window.innerWidth <= 768;
                let isHidden = isMobile ? !sidebar.classList.contains('show-mobile') : sidebar.classList.contains('collapsed');
                icon.className = isHidden ? 'fas fa-bars' : 'fas fa-arrow-left';
            }
            updateToggleIcon();
            window.addEventListener('resize', updateToggleIcon);
            toggleBtn.addEventListener('click', function(e) {
                e.preventDefault();
                if (window.innerWidth <= 768) {
                    sidebar.classList.toggle('show-mobile');
                } else {
                    sidebar.classList.toggle('collapsed');
                    if (mainContent) mainContent.classList.toggle('expanded');
                }
                updateToggleIcon();
            });
        }

        // 3. SWAL Logout
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
    });
</script>