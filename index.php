<?php
$pageTitle = "Loading Portal...";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LifeLine | Redirecting...</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><path fill='%23e63946' d='M50,5 C50,5 90,45 90,65 C90,85 70,95 50,95 C30,95 10,85 10,65 C10,45 50,5 50,5 Z'/></svg>">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom Style CSS -->
    <link rel="stylesheet" href="css/style.css">
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
document.addEventListener('DOMContentLoaded', function() {
    const token = localStorage.getItem('ll_token');
    const userJson = localStorage.getItem('ll_user');

    if (!token || !userJson) {
        // Clear anything corrupted and redirect to login
        localStorage.removeItem('ll_token');
        localStorage.removeItem('ll_user');
        window.location.href = 'user/login.php';
        return;
    }

    try {
        const user = JSON.parse(userJson);
        const role = user.role;
        
        if (role === 'admin' || role === 'updater' || role === 'superadmin') {
            window.location.href = 'admin/dashboard.php';
        } else {
            window.location.href = 'user/dashboard.php';
        }
    } catch (e) {
        console.error(e);
        localStorage.removeItem('ll_token');
        localStorage.removeItem('ll_user');
        window.location.href = 'user/login.php';
    }
});
</script>

</body>
</html>
