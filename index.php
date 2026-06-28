<?php
require_once __DIR__ . '/includes/env.php';
$pageTitle = "Loading Portal...";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LifeLine | Redirecting...</title>
    
    <script>
        // Immediately set theme from localStorage to avoid flashing
        const currentTheme = localStorage.getItem('theme') || 'light';
        document.documentElement.setAttribute('data-theme', currentTheme);
    </script>
    
    <!-- Favicon -->
    <link rel="icon" href="<?php echo APP_URL; ?>/images/favicon.ico?v=2.0" sizes="any">
    <link rel="icon" href="<?php echo APP_URL; ?>/images/favicon.png?v=2.0" type="image/png" sizes="32x32">
    <link rel="icon" href="<?php echo APP_URL; ?>/images/favicon-192.png?v=2.0" type="image/png" sizes="192x192">
    <link rel="apple-touch-icon" href="<?php echo APP_URL; ?>/images/apple-touch-icon.png?v=2.0">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom Style CSS -->
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/css/style.css?v=1.5">
</head>
<body class="bg-dark text-white d-flex align-items-center justify-content-center" style="min-height: 100vh;">

<div class="text-center">
    <div class="spinner-border text-danger mb-3" role="status" style="width: 3rem; height: 3rem;">
        <span class="visually-hidden">Loading...</span>
    </div>
    <h2 class="font-heading text-white">LifeLine Access Portal</h2>
    <p class="text-secondary small">Authenticating session and routing to dashboard...</p>
</div>

<script>
const APP_URL = "<?php echo APP_URL; ?>";

document.addEventListener('DOMContentLoaded', function() {
    const token = localStorage.getItem('ll_token');
    const userJson = localStorage.getItem('ll_user');

    if (!token || !userJson) {
        // Clear anything corrupted and redirect to login
        localStorage.removeItem('ll_token');
        localStorage.removeItem('ll_user');
        window.location.href = APP_URL + '/user/login.php';
        return;
    }

    try {
        const user = JSON.parse(userJson);
        const role = user.role;
        
        if (role === 'admin' || role === 'updater' || role === 'superadmin') {
            window.location.href = APP_URL + '/admin/dashboard.php';
        } else {
            window.location.href = APP_URL + '/user/dashboard.php';
        }
    } catch (e) {
        console.error(e);
        localStorage.removeItem('ll_token');
        localStorage.removeItem('ll_user');
        window.location.href = APP_URL + '/user/login.php';
    }
});
</script>

</body>
</html>
