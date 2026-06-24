<?php
$pageTitle = "Admin Sign In";
require_once __DIR__ . '/includes/header.php';
?>

<!-- Client-side Redirect if already logged in -->
<script>
if (localStorage.getItem('ll_token')) {
    const user = JSON.parse(localStorage.getItem('ll_user') || '{}');
    if (user.role === 'admin' || user.role === 'updater') {
        window.location.href = 'dashboard.php';
    } else {
        window.location.href = '../home.php';
    }
}
</script>

<div class="hero-header">
    <h1 class="hero-title">Admin Access Portal</h1>
    <p class="hero-subtitle">Sign in to manage inventory, update users, or view logs.</p>
</div>

<div class="container overlap-container">
    <div class="row justify-content-center">
        <div class="col-lg-5 col-md-7">
            <div class="premium-card">
                <h3 class="font-heading text-dark mb-4 text-center border-bottom pb-2">
                    <i class="bi bi-shield-lock-fill text-danger me-2"></i>Staff & Admin Login
                </h3>
                <div id="login-alert" class="alert alert-danger" style="display: none;"></div>
                <form id="login-form">
                    <div class="mb-3">
                        <label for="login-email" class="form-label">Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="bi bi-envelope"></i></span>
                            <input type="email" class="form-control" id="login-email" required placeholder="admin@lifeline.com">
                        </div>
                    </div>
                    <div class="mb-4">
                        <label for="login-password" class="form-label">Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="bi bi-lock"></i></span>
                            <input type="password" class="form-control" id="login-password" required placeholder="Enter password">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-pill btn-crimson w-100 py-3" id="login-submit-btn">
                        <span class="spinner-border spinner-border-sm me-1" id="login-spinner" style="display: none;"></span>
                        Access Command Console
                    </button>
                    <div class="text-center mt-3">
                        <a href="../home.php" class="text-secondary small text-decoration-none"><i class="bi bi-droplet-fill text-danger me-1"></i> Back to Homepage</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('login-form');
    const loginAlert = document.getElementById('login-alert');
    const loginSpinner = document.getElementById('login-spinner');
    const loginBtn = document.getElementById('login-submit-btn');

    loginForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        loginAlert.style.display = 'none';
        loginSpinner.style.display = 'inline-block';
        loginBtn.setAttribute('disabled', 'true');

        const email = document.getElementById('login-email').value;
        const password = document.getElementById('login-password').value;

        try {
            const data = await apiFetch('api/auth.php?action=login', {
                method: 'POST',
                body: JSON.stringify({ email, password })
            });

            if (data.status === 'success') {
                const role = data.user.role;
                if (role === 'admin' || role === 'updater') {
                    localStorage.setItem('ll_token', data.token);
                    localStorage.setItem('ll_user', JSON.stringify(data.user));
                    window.location.href = 'dashboard.php';
                } else {
                    throw new Error('Access denied: You must be an admin or updater to log in here.');
                }
            } else {
                throw new Error(data.message || 'Login failed.');
            }
        } catch (error) {
            loginAlert.textContent = error.message;
            loginAlert.style.display = 'block';
            loginSpinner.style.display = 'none';
            loginBtn.removeAttribute('disabled');
        }
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
