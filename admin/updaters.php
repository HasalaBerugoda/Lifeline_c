<?php
$pageTitle = "Updater Management";
$activePage = "updaters";
require_once __DIR__ . '/includes/header.php';
?>

<script>
// Restricted to admin and superadmin roles
const auth = checkAuth(['admin', 'superadmin']);
</script>

<div class="hero-header">
    <h1 class="hero-title">Updater Management</h1>
    <p class="hero-subtitle">Administer staff updater accounts, assign hospital facility permissions, or update roles.</p>
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
                    <h3 class="font-heading mb-0 text-dark">Staff Updaters Registry</h3>
                    <span class="badge bg-crimson rounded-pill px-3 py-1 text-uppercase" id="updaters-count-badge">0 Accounts</span>
                </div>

                <div id="updaters-alert" class="alert alert-success" style="display: none;"></div>

                <div class="table-responsive">
                    <table class="table table-striped align-middle">
                        <thead>
                            <tr>
                                <th>Staff Name</th>
                                <th>Contact Email / Phone</th>
                                <th>Role Authority</th>
                                <th>Assigned Facility</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="updaters-table-body">
                            <tr>
                                <td colspan="5" class="text-center py-4 text-secondary">
                                    <div class="spinner-border spinner-border-sm text-danger me-1"></div> Loading updaters...
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

    // Load initial updaters
    await loadUpdaters();

    async function loadUpdaters() {
        const badge = document.getElementById('updaters-count-badge');
        try {
            const result = await apiFetch('api/api.php?endpoint=users');
            if (result.status === 'success') {
                const users = result.data;
                const updaters = users.filter(u => u.role === 'updater');
                
                if (badge) {
                    badge.textContent = `${updaters.length} Staff`;
                }

                renderUpdatersTable(updaters);
            }
        } catch (e) {
            console.error('Failed to load updaters', e);
            const tbody = document.getElementById('updaters-table-body');
            if (tbody) {
                tbody.innerHTML = `<tr><td colspan="5" class="text-center text-danger py-4">Failed to load updater records.</td></tr>`;
            }
        }
    }

    // Helper to generate role select options dynamically based on hierarchy
    function getRoleSelectHTML(user, isEditable) {
        if (!isEditable) {
            const roleName = user.role.charAt(0).toUpperCase() + user.role.slice(1);
            return `<span class="badge bg-secondary px-3 py-2 text-uppercase" style="font-size: 11px;">${roleName}</span>`;
        }
        
        const allRoles = [
            { value: 'donor', label: 'Donor', level: 1 },
            { value: 'revoked', label: 'Revoked', level: 1 },
            { value: 'updater', label: 'Updater', level: 2 },
            { value: 'admin', label: 'Admin', level: 3 }
        ];

        let html = `<select onchange="onRoleChange(this, ${user.id})" class="form-select form-select-sm" style="width: 130px;">`;
        allRoles.forEach(r => {
            if (r.level < currentUserLevel || r.value === user.role) {
                const selected = user.role === r.value ? 'selected' : '';
                html += `<option value="${r.value}" ${selected}>${r.label}</option>`;
            }
        });
        html += `</select>`;
        return html;
    }

    function renderUpdatersTable(list) {
        const tbody = document.getElementById('updaters-table-body');
        if (!tbody) return;
        tbody.innerHTML = '';
        
        if (list.length === 0) {
            tbody.innerHTML = `<tr><td colspan="5" class="text-center py-4 text-secondary">No updaters registered.</td></tr>`;
            return;
        }
        
        list.forEach(u => {
            const tr = document.createElement('tr');
            const targetLevel = roleLevels[u.role] ?? 1;
            const isEditable = targetLevel < currentUserLevel;
            
            tr.innerHTML = `
                <td>
                    <span class="text-dark fw-bold">${u.fullName}</span>
                </td>
                <td>
                    <small>${u.email}</small><br>
                    <small class="text-secondary">${u.phone}</small>
                </td>
                <td>${getRoleSelectHTML(u, isEditable)}</td>
                <td>
                    <input type="text" 
                           id="facility-${u.id}" 
                           value="${u.facility_name || ''}" 
                           class="form-control form-control-sm" 
                           placeholder="Hospital name" 
                           style="width: 180px;"
                           ${isEditable ? '' : 'disabled'}>
                </td>
                <td class="text-end">
                    <div class="d-flex gap-1 justify-content-end" style="white-space: nowrap;">
                        <button onclick="saveUserRole(${u.id}, this)" class="btn btn-sm btn-crimson rounded-circle" style="width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center;" title="Save modifications" ${isEditable ? '' : 'disabled'}>
                            <i class="bi bi-save"></i>
                        </button>
                        <button onclick="deleteUser(${u.id})" class="btn btn-sm btn-outline-danger rounded-circle" style="width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center;" title="Delete account" ${isEditable ? '' : 'disabled'}>
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </td>
            `;
            tbody.appendChild(tr);
        });
    }

    window.onRoleChange = function(selectEl, userId) {
        const facilityInput = document.getElementById(`facility-${userId}`);
        if (facilityInput) {
            if (selectEl.value === 'updater') {
                facilityInput.removeAttribute('disabled');
            } else {
                facilityInput.value = '';
                facilityInput.setAttribute('disabled', 'true');
            }
        }
    };

    window.saveUserRole = async function(userId, btn) {
        const selectEl = btn.closest('tr').querySelector('select');
        const facilityInput = document.getElementById(`facility-${userId}`);
        const alertBox = document.getElementById('updaters-alert');
        alertBox.style.display = 'none';

        const role = selectEl.value;
        const facility_name = facilityInput ? facilityInput.value : '';

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
                
                await loadUpdaters();
            }
        } catch (error) {
            alert(error.message || "Failed to update updater facility/role.");
        } finally {
            btn.removeAttribute('disabled');
        }
    };

    window.deleteUser = async function(userId) {
        if (!confirm("Are you sure you want to permanently delete this updater account? This cannot be undone.")) {
            return;
        }

        const alertBox = document.getElementById('updaters-alert');
        alertBox.style.display = 'none';

        try {
            const result = await apiFetch(`api/api.php?endpoint=users&id=${userId}`, {
                method: 'DELETE'
            });

            if (result.status === 'success') {
                alertBox.className = "alert alert-success";
                alertBox.textContent = result.message;
                alertBox.style.display = 'block';

                await loadUpdaters();
            }
        } catch (error) {
            alertBox.className = "alert alert-danger";
            alertBox.textContent = error.message || "Failed to delete updater account.";
            alertBox.style.display = 'block';
        }
    };
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
