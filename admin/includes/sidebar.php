<?php
$activeKey = $activePage ?? '';
?>
<div class="premium-card mb-4">
    <div class="text-center mb-4 pb-3 border-bottom">
        <h4 class="font-heading text-dark mb-1">Admin Panel</h4>
        <span class="badge bg-danger rounded-pill px-3 py-1 text-uppercase" style="font-size: 10px;" id="sidebar-role-badge">Admin</span>
    </div>
    <div class="d-flex flex-column gap-2" id="sidebar-menu">
        <a href="dashboard.php" class="btn btn-pill text-start py-2 w-100 <?php echo $activeKey === 'dashboard' ? 'btn-crimson text-white' : 'btn-outline-light text-dark border-0'; ?>">
            <i class="bi bi-speedometer2 me-2"></i> Dashboard
        </a>
        <a href="users.php" class="btn btn-pill text-start py-2 w-100 <?php echo $activeKey === 'users' ? 'btn-crimson text-white' : 'btn-outline-light text-dark border-0'; ?>">
            <i class="bi bi-people-fill me-2"></i> Users Management
        </a>
        <a href="updaters.php" id="sidebar-updaters-link" class="btn btn-pill text-start py-2 w-100 <?php echo $activeKey === 'updaters' ? 'btn-crimson text-white' : 'btn-outline-light text-dark border-0'; ?>" style="display: none;">
            <i class="bi bi-hospital me-2"></i> Updater Management
        </a>
        <a href="superadmin.php" id="sidebar-superadmin-link" class="btn btn-pill text-start py-2 w-100 <?php echo $activeKey === 'superadmin' ? 'btn-crimson text-white' : 'btn-outline-light text-dark border-0'; ?>" style="display: none;">
            <i class="bi bi-shield-fill-check me-2"></i> Super Admin Panel
        </a>
        <a href="settings.php" class="btn btn-pill text-start py-2 w-100 <?php echo $activeKey === 'settings' ? 'btn-crimson text-white' : 'btn-outline-light text-dark border-0'; ?>">
            <i class="bi bi-sliders me-2"></i> Command / Settings
        </a>
        <a href="profile.php" class="btn btn-pill text-start py-2 w-100 <?php echo $activeKey === 'profile' ? 'btn-crimson text-white' : 'btn-outline-light text-dark border-0'; ?>">
            <i class="bi bi-person-fill me-2"></i> My Profile
        </a>
        <hr class="my-2">
        <a href="../home.php" class="btn btn-pill text-start py-2 w-100 btn-outline-light text-dark border-0">
            <i class="bi bi-house-door-fill me-2"></i> View Homepage
        </a>
        <button onclick="logout()" class="btn btn-pill text-start py-2 w-100 btn-outline-danger border-0">
            <i class="bi bi-box-arrow-right me-2"></i> Sign Out
        </button>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Dynamically set user role label in sidebar
    const userJson = localStorage.getItem('ll_user');
    if (userJson) {
        try {
            const user = JSON.parse(userJson);
            const badge = document.getElementById('sidebar-role-badge');
            if (badge) {
                badge.textContent = user.role === 'superadmin' ? 'SUPER ADMIN' : user.role.toUpperCase();
                if (user.role === 'updater') {
                    badge.className = 'badge bg-warning text-dark rounded-pill px-3 py-1 text-uppercase';
                } else if (user.role === 'superadmin') {
                    badge.className = 'badge bg-dark border border-light text-white rounded-pill px-3 py-1 text-uppercase';
                } else {
                    badge.className = 'badge bg-danger rounded-pill px-3 py-1 text-uppercase';
                }
            }
            
            // Show/hide menu items based on role authority
            const isSuperadmin = (user.role === 'superadmin');
            const isAdmin = (user.role === 'admin');
            
            if (isAdmin || isSuperadmin) {
                const updatersLink = document.getElementById('sidebar-updaters-link');
                if (updatersLink) updatersLink.style.display = 'block';
            }
            if (isSuperadmin) {
                const superadminLink = document.getElementById('sidebar-superadmin-link');
                if (superadminLink) superadminLink.style.display = 'block';
            }

            // Hide users management link for non-management roles
            if (user.role !== 'admin' && user.role !== 'superadmin' && user.role !== 'updater') {
                const usersLink = document.querySelector('#sidebar-menu a[href="users.php"]');
                if (usersLink) {
                    usersLink.style.display = 'none';
                }
            }
        } catch (e) {
            console.error(e);
        }
    }
});
</script>
