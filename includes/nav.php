<?php
// Single source of truth for Navigation Bar
// Driven by a PHP array of nav items

$navItems = [
    'home' => [
        'label' => 'Home',
        'url' => 'home.php',
        'visibility' => 'public' // Visible to everyone
    ],
    'dashboard' => [
        'label' => 'Dashboard',
        'url' => 'donor.php',
        'visibility' => 'auth', // Logged in only
        'roles' => ['donor', 'updater', 'admin']
    ],
    'camps' => [
        'label' => 'Blood Camps',
        'url' => 'donation-camps.php',
        'visibility' => 'public'
    ],
    'command' => [
        'label' => 'Command',
        'url' => 'updater.php',
        'visibility' => 'auth', // Logged in only
        'roles' => ['updater', 'admin']
    ],
    'analytics' => [
        'label' => 'Analytics',
        'url' => 'analytics.php',
        'visibility' => 'public'
    ],
    'admin' => [
        'label' => 'Admin',
        'url' => 'admin.php',
        'visibility' => 'auth', // Logged in only
        'roles' => ['admin']
    ],
    'contact' => [
        'label' => 'Contact',
        'url' => 'contact-us.php',
        'visibility' => 'public'
    ]
];

$activeKey = $activePage ?? '';
?>

<div class="navbar-container">
    <nav class="navbar navbar-expand-lg glass-nav">
        <div class="container-fluid">
            <!-- Brand -->
            <a class="navbar-brand" href="home.php">
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
                        
                        // Set attributes for client-side visibility control
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
                    <a href="index.php" class="btn btn-pill btn-outline-crimson text-white border-white-50 btn-sm" id="nav-btn-signin" style="display: none;">
                        Sign In
                    </a>
                    
                    <!-- Logged In Options -->
                    <span class="nav-user-badge" id="nav-user-badge" style="display: none;">
                        <i class="bi bi-person-fill me-1"></i> <span id="nav-username">Name</span> (<span id="nav-userrole">Role</span>)
                    </span>
                    <button onclick="logout()" class="btn btn-pill btn-crimson btn-sm" id="nav-btn-signout" style="display: none;">
                        <i class="bi bi-box-arrow-right me-1"></i> Sign Out
                    </button>
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
                userName = user.fullName.split(' ')[0]; // first name
            } else {
                // Token expired
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

    // Toggle Auth Buttons
    const btnSignIn = document.getElementById('nav-btn-signin');
    const badgeUser = document.getElementById('nav-user-badge');
    const btnSignOut = document.getElementById('nav-btn-signout');
    const spanName = document.getElementById('nav-username');
    const spanRole = document.getElementById('nav-userrole');
    const authSection = document.getElementById('nav-auth-section');

    if (isLoggedIn) {
        if (spanName) spanName.textContent = userName;
        if (spanRole) spanRole.textContent = userRole.toUpperCase();
        if (badgeUser) badgeUser.style.display = 'inline-block';
        if (btnSignOut) btnSignOut.style.display = 'inline-block';
        if (btnSignIn) btnSignIn.style.display = 'none';
        if (authSection) authSection.classList.add('logged-in');
    } else {
        if (badgeUser) badgeUser.style.display = 'none';
        if (btnSignOut) btnSignOut.style.display = 'none';
        if (btnSignIn) btnSignIn.style.display = 'inline-block';
        if (authSection) authSection.classList.remove('logged-in');
    }
});
</script>
