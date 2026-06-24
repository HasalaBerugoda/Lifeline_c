<?php
// Subdirectory-aware navigation bar for user-facing modules

$navItems = [
    'home' => [
        'label' => 'Home',
        'url' => '../home.php',
        'visibility' => 'public'
    ],
    'dashboard' => [
        'label' => 'Dashboard',
        'url' => 'dashboard.php',
        'visibility' => 'auth',
        'roles' => ['donor', 'updater', 'admin', 'superadmin']
    ],
    'camps' => [
        'label' => 'Blood Camps',
        'url' => '../donation-camps.php',
        'visibility' => 'public'
    ],
    'command' => [
        'label' => 'Command',
        'url' => '../admin/settings.php',
        'visibility' => 'auth',
        'roles' => ['updater', 'admin', 'superadmin']
    ],
    'analytics' => [
        'label' => 'Analytics',
        'url' => '../analytics.php',
        'visibility' => 'public'
    ],
    'admin' => [
        'label' => 'Admin',
        'url' => '../admin/dashboard.php',
        'visibility' => 'auth',
        'roles' => ['admin', 'superadmin']
    ],
    'contact' => [
        'label' => 'Contact',
        'url' => '../contact-us.php',
        'visibility' => 'public'
    ]
];

$activeKey = $activePage ?? '';
?>

<style>
.custom-nav-dropdown .dropdown-item {
    transition: all 0.2s ease;
}
.custom-nav-dropdown .dropdown-item:hover {
    background-color: rgba(230, 57, 70, 0.1) !important;
    color: #ffffff !important;
}
.custom-nav-dropdown .dropdown-item:active {
    background-color: #e63946 !important;
}
</style>

<div class="navbar-container">
    <nav class="navbar navbar-expand-lg glass-nav">
        <div class="container-fluid">
            <!-- Brand -->
            <a class="navbar-brand" href="../home.php">
                <i class="bi bi-droplet-fill text-danger"></i>
                Life<span>Line</span>
            </a>
            
            <!-- Mobile Toggle -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <!-- Links -->
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0" id="nav-links-container">
                    <?php foreach ($navItems as $key => $item): 
                        $activeClass = ($activeKey === $key) ? 'active' : '';
                        
                        $dataAttrs = "data-visibility='{$item['visibility']}'";
                        if (isset($item['roles'])) {
                            $dataAttrs .= " data-roles='" . json_encode($item['roles']) . "'";
                        }
                    ?>
                        <li class="nav-item" <?php echo $dataAttrs; ?> style="display: none;">
                            <a class="nav-link <?php echo $activeClass; ?>" href="<?php echo $item['url']; ?>">
                                <?php echo $item['label']; ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
                
                <!-- Auth Section (Controlled client side) -->
                <div class="d-flex align-items-center gap-3" id="nav-auth-section">
                    <!-- Guest Options -->
                    <a href="login.php" class="btn btn-pill btn-outline-crimson text-white border-white-50 btn-sm" id="nav-btn-signin" style="display: none;">
                        Sign In
                    </a>
                    
                    <!-- Logged In Options: Dropdown menu -->
                    <div class="dropdown" id="nav-user-dropdown" style="display: none;">
                        <button class="btn btn-pill btn-outline-light dropdown-toggle d-flex align-items-center gap-2 text-white border-white-50 btn-sm" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false" style="background: rgba(255, 255, 255, 0.05);">
                            <i class="bi bi-person-circle"></i>
                            <span id="nav-username">Name</span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0 custom-nav-dropdown" aria-labelledby="userDropdown" style="background: #111827; border-radius: 12px; margin-top: 10px;">
                            <li>
                                <a class="dropdown-item text-white d-flex align-items-center gap-2 py-2 px-3" href="profile.php">
                                    <i class="bi bi-person-gear text-danger"></i> Edit Profile
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item text-white d-flex align-items-center gap-2 py-2 px-3" href="dashboard.php">
                                    <i class="bi bi-speedometer2 text-danger"></i> Dashboard
                                </a>
                            </li>
                            <li><hr class="dropdown-divider border-secondary opacity-25"></li>
                            <li>
                                <button onclick="logout()" class="dropdown-item text-danger d-flex align-items-center gap-2 py-2 px-3 w-100 border-0 bg-transparent text-start">
                                    <i class="bi bi-box-arrow-right"></i> Sign Out
                                </button>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </nav>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const token = localStorage.getItem('ll_token');
    const userJson = localStorage.getItem('ll_user');
    
    let isLoggedIn = false;
    let userRole = null;
    let userName = '';

    if (token && userJson) {
        try {
            const payload = parseJWT(token);
            if (payload && (!payload.exp || payload.exp >= Date.now() / 1000)) {
                isLoggedIn = true;
                const user = JSON.parse(userJson);
                userRole = user.role;
                userName = user.fullName.split(' ')[0];
            } else {
                localStorage.removeItem('ll_token');
                localStorage.removeItem('ll_user');
            }
        } catch (e) {
            console.error('Error parsing token in navbar', e);
        }
    }

    // Toggle nav link visibility
    const navItems = document.querySelectorAll('#nav-links-container .nav-item');
    navItems.forEach(item => {
        const visibility = item.getAttribute('data-visibility');
        
        if (visibility === 'public') {
            item.style.display = 'block';
        } else if (visibility === 'auth') {
            if (isLoggedIn) {
                const rolesAttr = item.getAttribute('data-roles');
                if (rolesAttr) {
                    const roles = JSON.parse(rolesAttr);
                    if (roles.includes(userRole)) {
                        item.style.display = 'block';
                    } else {
                        item.style.display = 'none';
                    }
                } else {
                    item.style.display = 'block';
                }
            } else {
                item.style.display = 'none';
            }
        }
    });

    // Toggle Auth Buttons / Dropdown
    const btnSignIn = document.getElementById('nav-btn-signin');
    const dropdownUser = document.getElementById('nav-user-dropdown');
    const spanName = document.getElementById('nav-username');
    const authSection = document.getElementById('nav-auth-section');

    if (isLoggedIn) {
        if (spanName) spanName.textContent = userName;
        
        // Dynamically update dropdown links based on role (relative to /user/ folder)
        const profileLink = dropdownUser.querySelector('a[href*="profile.php"]');
        const dashboardLink = dropdownUser.querySelector('a[href*="dashboard.php"]');
        
        if (userRole === 'admin' || userRole === 'updater' || userRole === 'superadmin') {
            if (profileLink) profileLink.href = '../admin/profile.php';
            if (dashboardLink) dashboardLink.href = '../admin/dashboard.php';
        } else {
            if (profileLink) profileLink.href = 'profile.php';
            if (dashboardLink) dashboardLink.href = 'dashboard.php';
        }
        
        if (dropdownUser) dropdownUser.style.display = 'block';
        if (btnSignIn) btnSignIn.style.display = 'none';
        if (authSection) authSection.classList.add('logged-in');
    } else {
        if (dropdownUser) dropdownUser.style.display = 'none';
        if (btnSignIn) btnSignIn.style.display = 'inline-block';
        if (authSection) authSection.classList.remove('logged-in');
    }
});
</script>
