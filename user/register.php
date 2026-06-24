<?php
$pageTitle = "Donor Registration";
require_once __DIR__ . '/includes/header.php';
?>

<!-- Client-side Redirect if already logged in -->
<script>
if (localStorage.getItem('ll_token')) {
    const user = JSON.parse(localStorage.getItem('ll_user') || '{}');
    if (user.role === 'admin' || user.role === 'updater' || user.role === 'superadmin') {
        window.location.href = '../admin/dashboard.php';
    } else {
        window.location.href = 'dashboard.php';
    }
}
</script>

<div class="hero-header">
    <?php require_once __DIR__ . '/includes/nav.php'; ?>
    <h1 class="hero-title">Donor Registration</h1>
    <p class="hero-subtitle">Create your donor account to start tracking contributions and save lives.</p>
</div>

<div class="container overlap-container">
    <div class="row justify-content-center">
        <div class="col-lg-6 col-md-8">
            <div class="premium-card">
                <h3 class="font-heading text-dark mb-4 text-center border-bottom pb-2">
                    <i class="bi bi-person-plus text-danger me-2"></i>Create Donor Account
                </h3>

                <div id="register-alert" class="alert alert-danger" style="display: none;"></div>

                <form id="register-form">
                    <div class="mb-3">
                        <label for="reg-name" class="form-label">Full Name</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="bi bi-person"></i></span>
                            <input type="text" class="form-control" id="reg-name" required placeholder="John Doe">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="reg-email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="reg-email" required placeholder="johndoe@email.com">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="reg-phone" class="form-label">Phone Number</label>
                            <input type="text" class="form-control" id="reg-phone" required placeholder="+94 77 123 4567">
                        </div>
                    </div>
                    
                    <!-- Location Dropdowns (Sri Lanka Cascade) -->
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="reg-province" class="form-label">Province</label>
                            <select class="form-select" id="reg-province" required>
                                <option value="" disabled selected>Select Province</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="reg-district" class="form-label">District</label>
                            <select class="form-select" id="reg-district" required disabled>
                                <option value="" disabled selected>Select District</option>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="reg-town" class="form-label">Town / City</label>
                            <input type="text" class="form-control" id="reg-town" required placeholder="e.g. Nugegoda">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="reg-blood" class="form-label">Blood Type</label>
                            <select class="form-select" id="reg-blood" required>
                                <option value="" disabled selected>Select Blood Group</option>
                                <option value="O+">O Positive (O+)</option>
                                <option value="A+">A Positive (A+)</option>
                                <option value="B+">B Positive (B+)</option>
                                <option value="AB+">AB Positive (AB+)</option>
                                <option value="O-">O Negative (O-)</option>
                                <option value="A-">A Negative (A-)</option>
                                <option value="B-">B Negative (B-)</option>
                                <option value="AB-">AB Negative (AB-)</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="reg-password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="reg-password" required placeholder="Min 6 characters">
                    </div>

                    <button type="submit" class="btn btn-pill btn-crimson w-100 py-3" id="register-submit-btn">
                        <span class="spinner-border spinner-border-sm me-1" id="register-spinner" style="display: none;"></span>
                        Create Donor Account
                    </button>
                    
                    <div class="text-center mt-3">
                        <span class="text-secondary small">Already have a donor account? </span>
                        <a href="login.php" class="text-danger small fw-bold text-decoration-none">Sign In here</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Sri Lanka Location Cascading Dropdowns
    const locationData = {
        'Western': ['Colombo', 'Gampaha', 'Kalutara'],
        'Central': ['Kandy', 'Matale', 'Nuwara Eliya'],
        'Southern': ['Galle', 'Matara', 'Hambantota'],
        'Northern': ['Jaffna', 'Kilinochchi', 'Mannar', 'Vavuniya', 'Mullaitivu'],
        'Eastern': ['Trincomalee', 'Batticaloa', 'Ampara'],
        'North Western': ['Kurunegala', 'Puttalam'],
        'North Central': ['Anuradhapura', 'Polonnaruwa'],
        'Uva': ['Badulla', 'Monaragala'],
        'Sabagamuwa': ['Ratnapura', 'Kegalle']
    };

    const provinceSelect = document.getElementById('reg-province');
    const districtSelect = document.getElementById('reg-district');

    // Populate Provinces
    for (let province in locationData) {
        let opt = document.createElement('option');
        opt.value = province;
        opt.textContent = province;
        provinceSelect.appendChild(opt);
    }

    // Handle province change event
    provinceSelect.addEventListener('change', function() {
        const selectedProvince = this.value;
        districtSelect.innerHTML = '<option value="" disabled selected>Select District</option>';
        
        if (selectedProvince && locationData[selectedProvince]) {
            districtSelect.removeAttribute('disabled');
            locationData[selectedProvince].forEach(district => {
                let opt = document.createElement('option');
                opt.value = district;
                opt.textContent = district;
                districtSelect.appendChild(opt);
            });
        } else {
            districtSelect.setAttribute('disabled', 'true');
        }
    });

    // Register Form Submission
    const registerForm = document.getElementById('register-form');
    const registerAlert = document.getElementById('register-alert');
    const registerSpinner = document.getElementById('register-spinner');
    const registerBtn = document.getElementById('register-submit-btn');

    registerForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        registerAlert.style.display = 'none';
        registerSpinner.style.display = 'inline-block';
        registerBtn.setAttribute('disabled', 'true');

        const fullName  = document.getElementById('reg-name').value;
        const email     = document.getElementById('reg-email').value;
        const phone     = document.getElementById('reg-phone').value;
        const province  = document.getElementById('reg-province').value;
        const district  = document.getElementById('reg-district').value;
        const town      = document.getElementById('reg-town').value;
        const bloodType = document.getElementById('reg-blood').value;
        const password  = document.getElementById('reg-password').value;

        try {
            const data = await apiFetch('api/auth.php?action=register', {
                method: 'POST',
                body: JSON.stringify({
                    fullName, email, phone, province, district, town, bloodType, password
                })
            });

            if (data.status === 'success') {
                localStorage.setItem('ll_token', data.token);
                localStorage.setItem('ll_user', JSON.stringify(data.user));
                window.location.href = 'dashboard.php';
            } else {
                throw new Error(data.message || 'Registration failed.');
            }
        } catch (error) {
            registerAlert.textContent = error.message;
            registerAlert.style.display = 'block';
            registerSpinner.style.display = 'none';
            registerBtn.removeAttribute('disabled');
        }
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
