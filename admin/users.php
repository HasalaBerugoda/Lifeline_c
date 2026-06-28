<?php
$pageTitle = "User Management";
$activePage = "users";
require_once __DIR__ . '/includes/header.php';
?>

<script>
// Restricted to management roles
const auth = checkAuth(['updater', 'admin', 'superadmin']);
</script>

<div class="hero-header">
    <h1 class="hero-title">User Management</h1>
    <p class="hero-subtitle">Administer system donor accounts, assign roles, or suspend/delete user profiles.</p>
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
                <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
                    <h3 class="font-heading mb-0 text-dark">Donors / Users Registry</h3>
                    <span class="badge bg-crimson rounded-pill px-3 py-1 text-uppercase" id="users-count-badge">0 Accounts</span>
                </div>

                <div id="users-alert" class="alert alert-success" style="display: none;"></div>

                <div class="table-responsive">
                    <table class="table table-striped align-middle">
                        <thead>
                            <tr>
                                <th>Donor Num / Name</th>
                                <th>Contact Email / Phone</th>
                                <th>Blood Group</th>
                                <th>Role Authority</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="donors-table-body">
                            <tr>
                                <td colspan="5" class="text-center py-4 text-secondary">
                                    <div class="spinner-border spinner-border-sm text-danger me-1"></div> Loading donors...
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
    const currentUserRole = adminUser.role;
    
    const roleLevels = {
        'superadmin': 4,
        'admin': 3,
        'updater': 2,
        'donor': 1,
        'revoked': 1
    };
    
    const currentUserLevel = roleLevels[currentUserRole] ?? 1;

    // Load initial users
    await loadUsers();

    async function loadUsers() {
        const badge = document.getElementById('users-count-badge');
        try {
            const result = await apiFetch('api/api.php?endpoint=users');
            if (result.status === 'success') {
                const users = result.data;
                // Filter users to only donor / revoked roles
                const donors = users.filter(u => u.role === 'donor' || u.role === 'revoked');
                
                if (badge) {
                    badge.textContent = `${donors.length} Donors`;
                }

                renderDonorsTable(donors);
            }
        } catch (e) {
            console.error('Failed to load users', e);
            const tbody = document.getElementById('donors-table-body');
            if (tbody) {
                tbody.innerHTML = `<tr><td colspan="5" class="text-center text-danger py-4">Failed to load donor records.</td></tr>`;
            }
        }
    }

    // Helper to generate role select options dynamically based on hierarchy
    function getRoleSelectHTML(user, isEditable) {
        if (!isEditable) {
            const roleName = user.role === 'revoked' ? 'Revoked' : 'Donor';
            return `<span class="badge bg-secondary px-3 py-2 text-uppercase" style="font-size: 11px;">${roleName}</span>`;
        }
        
        const allRoles = [
            { value: 'donor', label: 'Donor', level: 1 },
            { value: 'revoked', label: 'Revoked', level: 1 },
            { value: 'updater', label: 'Updater', level: 2 },
            { value: 'admin', label: 'Admin', level: 3 }
        ];

        let html = `<select class="form-select form-select-sm" style="width: 130px;">`;
        allRoles.forEach(r => {
            // Show option if level < currentUserLevel OR it's the user's current role
            if (r.level < currentUserLevel || r.value === user.role) {
                const selected = user.role === r.value ? 'selected' : '';
                html += `<option value="${r.value}" ${selected}>${r.label}</option>`;
            }
        });
        html += `</select>`;
        return html;
    }

    // Render Donors
    function renderDonorsTable(list) {
        const tbody = document.getElementById('donors-table-body');
        if (!tbody) return;
        tbody.innerHTML = '';
        
        if (list.length === 0) {
            tbody.innerHTML = `<tr><td colspan="5" class="text-center py-4 text-secondary">No donors registered.</td></tr>`;
            return;
        }
        
        list.forEach(u => {
            const tr = document.createElement('tr');
            const targetLevel = roleLevels[u.role] ?? 1;
            
            // Admins and Superadmins can edit lower ranks; Updaters can edit donors/revoked.
            const isEditable = (currentUserRole === 'admin' || currentUserRole === 'superadmin' && targetLevel < currentUserLevel) ||
                               (currentUserRole === 'updater' && (u.role === 'donor' || u.role === 'revoked'));
            
            // Only admin and superadmin can delete accounts
            const isDeletable = (currentUserRole === 'admin' || currentUserRole === 'superadmin') && (targetLevel < currentUserLevel);
            
            tr.innerHTML = `
                <td>
                    <strong>${u.donor_number || 'Pending'}</strong><br>
                    <span class="text-dark fw-bold">${u.fullName}</span>
                </td>
                <td>
                    <small>${u.email}</small><br>
                    <small class="text-secondary">${u.phone}</small>
                </td>
                <td><span class="badge bg-danger rounded-pill">${u.bloodType}</span></td>
                <td>${getRoleSelectHTML(u, isEditable)}</td>
                <td class="text-end">
                    <div class="d-flex gap-1 justify-content-end" style="white-space: nowrap;">
                        <button onclick="saveUserRole(${u.id}, this)" class="btn btn-sm btn-crimson rounded-circle" style="width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center;" title="Save modifications" ${isEditable ? '' : 'disabled'}>
                            <i class="bi bi-save"></i>
                        </button>
                        <button onclick="deleteUser(${u.id})" class="btn btn-sm btn-outline-danger rounded-circle" style="width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center;" title="Delete account" ${isDeletable ? '' : 'disabled'}>
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </td>
            `;
            tbody.appendChild(tr);
        });
    }

    // Save User Role Details API
    window.saveUserRole = async function(userId, btn) {
        const selectEl = btn.closest('tr').querySelector('select');
        const alertBox = document.getElementById('users-alert');
        alertBox.style.display = 'none';

        const role = selectEl.value;

        btn.setAttribute('disabled', 'true');

        try {
            const result = await apiFetch('api/api.php?endpoint=users', {
                method: 'PUT',
                body: JSON.stringify({ id: userId, role })
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
