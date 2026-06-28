<?php
$pageTitle = "Sign Out";
require_once __DIR__ . '/includes/header.php';
?>

<div class="hero-header">
    <h1 class="hero-title">Signing Out</h1>
    <p class="hero-subtitle">Please wait while we secure your session...</p>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Clear storage and redirect
    localStorage.removeItem('ll_token');
    localStorage.removeItem('ll_user');
    window.location.href = 'login.php';
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
