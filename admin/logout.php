<?php
$pageTitle = "Admin Logging Out";
require_once __DIR__ . '/includes/header.php';
?>

<div class="hero-header">
    <h1 class="hero-title">Signing Out...</h1>
    <p class="hero-subtitle">Please wait while we secure your session details.</p>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    logout();
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
