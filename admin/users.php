<?php
$pageTitle = "User Management";
$activePage = "users";
require_once __DIR__ . '/includes/header.php';
?>

<script>
// Exclusively restricted to admin role
const auth = checkAuth(['admin']);
</script>

<div class="hero-header">
    <h1 class="hero-title">User Management</h1>
    <p class="hero-subtitle">Administer system accounts, assign roles, set up facility permissions, or delete accounts.</p>
</div>

<div class="container overlap-container">
    <div class="row g-4">
        <!-- Left Column: Sidebar Menu -->
        <div class="col-lg-3 col-md-4">
            <?php require_once __DIR__ . '/includes/sidebar.php'; ?>
        </div>

        <!-- Right Column: Main Content -->
        <div class="col-lg-9 col-md-8">
            <div class="premium-card">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3 class="font-heading mb-0 text-dark">System Users Registry</h3>
                    <span class="badge bg-crimson rounded-pill px-3 py-1 text-uppercase" id="users-count-badge">0 Users</span>
                </div>

                <div id="users-alert" class="alert alert-success" style="display: none;"></div>

                <div class="table-responsive">
                    <table class="table table-striped align-middle">
                        <thead>
                            <tr>
                                <th>Donor Num / Name</th>
                                <th>Contact Email / Phone</th>
                                <th>Blood</th>
                                <th>Role Authority</th>
                                <th>Facility (Updaters Only)</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="users-table-body">
                            <tr>
                                <td colspan="6" class="text-center py-4 text-secondary">
                                    <div class="spinner-border spinner-border-sm text-danger me-1"></div> Loading user registry...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', async function() {
    const adminUser = auth.user;

    // Load initial users
    await loadUsers();

    // ----------------------------------------------------
    // Load All Users
    // ----------------------------------------------------
    async function loadUsers() {
        const tbody = document.getElementById('users-table-body');
        const badge = document.getElementById('users-count-badge');
        try {
            const result = await apiFetch('api/api.php?endpoint=users');
            if (result.status === 'success') {
                tbody.innerHTML = '';
                const users = result.data;

                if (badge) {
                    badge.textContent = `${users.length} Users`;
                }

                // Helper to parse integer
                function toInt(val) {
                    return parseInt(val, 10) || 0;
                }

                if (users.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="6" class="text-center py-4 text-secondary">No user records found.</td></tr>`;
                    return;
                }

                users.forEach(u => {
                    const tr = document.createElement('tr');
                    
                    const isSelf = toInt(u.id) === toInt(adminUser.id);
                    const isUpdater = u.role === 'updater';
                    
                    tr.innerHTML = `
                        <td>
                            <strong>${u.donor_number || 'Pending'}</strong><br>
                            <span class="text-dark fw-bold">${u.fullName}</span>
                            ${isSelf ? '<span class="badge bg-secondary ms-1">You</span>' : ''}
                        </td>
                        <td>
                            <small>${u.email}</small><br>
                            <small class="text-secondary">${u.phone}</small>
                        </td>
                        <td><span class="badge bg-danger rounded-pill">${u.bloodType}</span></td>
                        <td>
                            <select onchange="onRoleChange(this, ${u.id})" class="form-select form-select-sm" style="width: 120px;">
                                <option value="donor" ${u.role === 'donor' ? 'selected' : ''}>Donor</option>
                                <option value="updater" ${u.role === 'updater' ? 'selected' : ''}>Updater</option>
                                <option value="admin" ${u.role === 'admin' ? 'selected' : ''}>Admin</option>
                                <option value="revoked" ${u.role === 'revoked' ? 'selected' : ''}>Revoked</option>
                            </select>
                        </td>
                        <td>
                            <input type="text" 
                                   id="facility-${u.id}" 
                                   value="${u.facility_name || ''}" 
                                   class="form-control form-control-sm" 
                                   placeholder="Hospital name" 
                                   style="width: 180px;"
                                   ${isUpdater ? '' : 'disabled'}>
                        </td>
                        <td class="text-end">
                            <div class="d-flex gap-1 justify-content-end">
                                <button onclick="saveUserRole(${u.id}, this)" class="btn btn-sm btn-crimson btn-pill" title="Save role modifications">
                                    <i class="bi bi-save"></i>
                                </button>
                                <button onclick="deleteUser(${u.id})" class="btn btn-sm btn-outline-danger btn-pill" ${isSelf ? 'disabled title="You cannot delete yourself"' : ''}>
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </td>
                    `;
                    tbody.appendChild(tr);
                });
            }
        } catch (e) {
            console.error('Failed to load users', e);
            tbody.innerHTML = `<tr><td colspan="6" class="text-center text-danger py-4">Failed to load user records.</td></tr>`;
        }
    }

    // Dropdown change handler to toggle facility input box
    window.onRoleChange = function(selectEl, userId) {
        const facilityInput = document.getElementById(`facility-${userId}`);
        if (selectEl.value === 'updater') {
            facilityInput.removeAttribute('disabled');
        } else {
            facilityInput.value = '';
            facilityInput.setAttribute('disabled', 'true');
        }
    };

    // Save User Role Details API
    window.saveUserRole = async function(userId, btn) {
        const selectEl = btn.closest('tr').querySelector('select');
        const facilityInput = document.getElementById(`facility-${userId}`);
        const alertBox = document.getElementById('users-alert');
        alertBox.style.display = 'none';

        const role = selectEl.value;
        const facility_name = facilityInput.value;

        btn.setAttribute('disabled', 'true');

        try {
            const result = await apiFetch('api/api.php?endpoint=users', {
                method: 'PUT',
                body: JSON.stringify({ id: userId, role, facility_name })
            });

            if (result.status === 'success') {
                alertBox.className = "alert alert-success";
                alertBox.textContent = result.message;
                alertBox.style.display = 'block';
                
                await loadUsers(); // Reload to refresh states
            }
        } catch (error) {
            alert(error.message || "Failed to update user authority.");
        } finally {
            btn.removeAttribute('disabled');
        }
    };

    // Delete User account API
    window.deleteUser = async function(userId) {
        if (!confirm("Are you sure you want to permanently delete this user account? This will delete all their donation logs and registrations. This cannot be undone.")) {
            return;
        }

        const alertBox = document.getElementById('users-alert');
        alertBox.style.display = 'none';

        try {
            const result = await apiFetch(`api/api.php?endpoint=users&id=${userId}`, {
                method: 'DELETE'
            });

            if (result.status === 'success') {
                alertBox.className = "alert alert-success";
                alertBox.textContent = result.message;
                alertBox.style.display = 'block';

                await loadUsers();
            }
        } catch (error) {
            alertBox.className = "alert alert-danger";
            alertBox.textContent = error.message || "Failed to delete user.";
            alertBox.style.display = 'block';
        }
    };
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
