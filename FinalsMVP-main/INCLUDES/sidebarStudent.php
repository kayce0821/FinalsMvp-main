<?php 
$current_page = basename($_SERVER['PHP_SELF']); 
$user_name = isset($_SESSION['full_name']) ? $_SESSION['full_name'] : 'Student';
?>

<style>
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
        padding: 15px 20px; border-bottom: 1px solid rgba(255,255,255,0.1);
        display: flex; align-items: center;
    }

    .nav-sidebar { padding: 10px 0; list-style: none; margin: 0; display: flex; flex-direction: column; flex-grow: 1; }
    .nav-sidebar .nav-link { color: rgba(255,255,255,0.8); padding: 12px 20px; text-decoration: none; display: flex; align-items: center; transition: 0.2s; }
    .nav-sidebar .nav-link:hover { color: white; background-color: rgba(255,255,255,0.1); }
    .nav-sidebar .nav-link.active { background-color: #007bff; color: white; border-radius: 0; }
    .nav-sidebar .nav-link i { margin-right: 15px; width: 20px; text-align: center; }

    @media (max-width: 768px) {
        .sidebar { position: fixed; left: -250px; margin-left: 0 !important; }
        .sidebar.show-mobile { left: 0; }
    }
</style>

<aside class="sidebar shadow" id="mainSidebar">
    <a href="../PAGES/studentDashboard.php" class="brand-link">
        <span class="fw-light"><strong>EQUIP</strong>TRACK</span>
    </a>

    <div class="user-panel">
        <i class="bi bi-person-badge text-white me-3" style="font-size: 2.2rem;"></i>
        <div class="overflow-hidden">
            <small class="text-white-50 d-block" style="font-size: 0.75rem; line-height: 1;">Student Portal,</small>
            <span class="text-white fw-bold text-truncate d-block" style="max-width: 160px;">
                <?php echo htmlspecialchars($user_name); ?>
            </span>
        </div>
    </div>

    <ul class="nav-sidebar mt-2">
        <li>
            <a href="../PAGES/studentDashboard.php" class="nav-link <?php echo ($current_page == 'studentDashboard.php') ? 'active' : ''; ?>">
                <i class="bi bi-box-seam"></i> Available Items
            </a>
        </li>
        <li>
            <a href="../MODULES/studentRequests.php" class="nav-link <?php echo ($current_page == 'studentRequests.php') ? 'active' : ''; ?>">
                <i class="bi bi-envelope-paper"></i> My Requests
            </a>
        </li>
        <li>
            <a href="../MODULES/studentHistory.php" class="nav-link <?php echo ($current_page == 'studentHistory.php') ? 'active' : ''; ?>">
                <i class="bi bi-clock-history"></i> Transaction History
            </a>
        </li>

         <li class="mt-auto border-top border-secondary">
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