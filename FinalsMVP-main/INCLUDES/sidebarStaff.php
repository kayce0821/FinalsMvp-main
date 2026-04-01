<?php 
// Get the current filename to automatically highlight the active menu item
$current_page = basename($_SERVER['PHP_SELF']); 

// Grab the user's name from the session (default to Staff if not set)
$user_name = isset($_SESSION['full_name']) ? $_SESSION['full_name'] : 'Staff Member';
?>

<style>
    /* Sidebar Styling */
    .sidebar {
        width: 250px; background-color: #3a5a40; color: white;
        display: flex; flex-direction: column; flex-shrink: 0; min-height: 100vh;
        transition: margin-left 0.3s ease;
    }
    /* Class added via JS to hide sidebar on desktop */
    .sidebar.collapsed {
        margin-left: -250px;
    }
    
    .sidebar .brand-link {
        padding: 15px 20px; font-size: 1.25rem; color: white;
        text-decoration: none; border-bottom: 1px solid rgba(255,255,255,0.1); display: block;
    }

    /* User Profile Section */
    .sidebar .user-panel {
        padding: 15px 20px;
        border-bottom: 1px solid rgba(255,255,255,0.1);
        display: flex;
        align-items: center;
    }

    .nav-sidebar { 
        padding: 10px 0; 
        list-style: none; 
        margin: 0; 
        display: flex; 
        flex-direction: column; 
        flex-grow: 1; /* This makes the list take up all vertical space */
    }
    .nav-sidebar .nav-link {
        color: rgba(255,255,255,0.8); padding: 12px 20px; text-decoration: none;
        display: flex; align-items: center; transition: 0.2s;
    }
    .nav-sidebar .nav-link:hover { color: white; background-color: rgba(255,255,255,0.1); }
    .nav-sidebar .nav-link.active { background-color: #007bff; color: white; border-radius: 0; }
    .nav-sidebar .nav-link i { margin-right: 15px; width: 20px; text-align: center; }

    /* Mobile Responsive adjustments */
    @media (max-width: 768px) {
        .sidebar {
            position: absolute;
            z-index: 1050;
            margin-left: -250px; /* Hidden by default on mobile */
        }
        .sidebar.show-mobile {
            margin-left: 0; /* Shows when toggled */
        }
    }
</style>

<aside class="sidebar shadow" id="mainSidebar">
    <a href="../PAGES/staffDashboard.php" class="brand-link">
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
            <a href="../PAGES/staffDashboard.php" class="nav-link <?php echo ($current_page == 'staffDashboard.php') ? 'active' : ''; ?>">
                <i class="bi bi-speedometer2"></i> Dashboard
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

        // SWAL Logout
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