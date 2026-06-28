<?php
$pageTitle = "Super Admin Panel";
$activePage = "superadmin";
require_once __DIR__ . '/includes/header.php';
?>

<script>
// Restricted exclusively to superadmin
const auth = checkAuth(['superadmin']);
</script>

<div class="hero-header">
    <h1 class="hero-title">Super Admin Panel</h1>
    <p class="hero-subtitle">Manage system administrators, update master credentials, or review detailed system audit trails.</p>
</div>

<div class="container overlap-container">
    <div class="row g-4">
        <!-- Left Column: Sidebar Menu -->
        <div class="col-lg-3 col-md-4">
            <?php require_once __DIR__ . '/includes/sidebar.php'; ?>
        </div>

        <!-- Right Column: Main Content -->
        <div class="col-lg-9 col-md-8">
            <div class="premium-card shadow">
                <!-- Navigation Tabs -->
                <ul class="nav nav-tabs custom-tabs mb-4" id="superAdminTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="admins-tab" data-bs-toggle="tab" data-bs-target="#admins-pane" type="button" role="tab" aria-controls="admins-pane" aria-selected="true">
                            <i class="bi bi-shield-lock-fill me-1"></i> Administrators Registry
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="audit-tab" data-bs-toggle="tab" data-bs-target="#audit-pane" type="button" role="tab" aria-controls="audit-pane" aria-selected="false">
                            <i class="bi bi-list-columns-reverse me-1"></i> System Audit Trail
                        </button>
                    </li>
                </ul>

                <div class="tab-content" id="superAdminTabContent">
                    <!-- Administrators Pane -->
                    <div class="tab-pane fade show active" id="admins-pane" role="tabpanel" aria-labelledby="admins-tab" tabindex="0">
                        <div id="admins-alert" class="alert alert-success" style="display: none;"></div>
                        
                        <div class="table-responsive">
                            <table class="table table-striped align-middle">
                                <thead>
                                    <tr>
                                        <th>Admin Name</th>
                                        <th>Contact Email / Phone</th>
                                        <th>Role Authority</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="admins-table-body">
                                    <tr>
                                        <td colspan="4" class="text-center py-4 text-secondary">
                                            <div class="spinner-border spinner-border-sm text-danger me-1"></div> Loading administrators...
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Audit Trail Pane -->
                    <div class="tab-pane fade" id="audit-pane" role="tabpanel" aria-labelledby="audit-tab" tabindex="0">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="font-heading mb-0 text-dark">Recent Operations Logs</h5>
                            <button onclick="loadAuditLogs()" class="btn btn-sm btn-outline-secondary px-3 py-1 rounded-pill" style="font-size: 11px;">
                                <i class="bi bi-arrow-clockwise"></i> Refresh Trail
                            </button>
                        </div>
                        <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                            <table class="table table-striped align-middle table-sm">
                                <thead>
                                    <tr>
                                        <th style="width: 180px;">Timestamp</th>
                                        <th>Operator</th>
                                        <th>Action</th>
                                        <th>Details</th>
                                    </tr>
                                </thead>
                                <tbody id="audit-table-body">
                                    <tr>
                                        <td colspan="4" class="text-center py-4 text-secondary">
                                            <div class="spinner-border spinner-border-sm text-danger me-1"></div> Loading logs...
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
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

    // Load initial data
    await loadAdmins();
    await loadAuditLogs();

    async function loadAdmins() {
        try {
            const result = await apiFetch('api/api.php?endpoint=users');
            if (result.status === 'success') {
                const users = result.data;
                const admins = users.filter(u => u.role === 'admin' || u.role === 'superadmin');
                renderAdminsTable(admins);
            }
        } catch (e) {
            console.error('Failed to load admins', e);
            const tbody = document.getElementById('admins-table-body');
            if (tbody) {
                tbody.innerHTML = `<tr><td colspan="4" class="text-center text-danger py-4">Failed to load administrator records.</td></tr>`;
            }
        }
    }

    window.loadAuditLogs = async function() {
        const tbody = document.getElementById('audit-table-body');
        if (tbody) {
            tbody.innerHTML = `<tr><td colspan="4" class="text-center py-4 text-secondary"><div class="spinner-border spinner-border-sm text-danger me-1"></div> Loading logs...</td></tr>`;
        }
        try {
            const result = await apiFetch('api/api.php?endpoint=audit_logs');
            if (result.status === 'success') {
                renderAuditLogsTable(result.data);
            }
        } catch (e) {
            console.error('Failed to load audit logs', e);
            if (tbody) {
                tbody.innerHTML = `<tr><td colspan="4" class="text-center text-danger py-4">Failed to load system operations log.</td></tr>`;
            }
        }
    };

    function toInt(val) {
        return parseInt(val, 10) || 0;
    }

    // Helper to generate role select options dynamically based on hierarchy
    function getRoleSelectHTML(user, isEditable) {
        if (!isEditable) {
            const roleName = user.role === 'superadmin' ? 'Super Admin' : 'Admin';
            return `<span class="badge bg-secondary px-3 py-2 text-uppercase" style="font-size: 11px;">${roleName}</span>`;
        }
        
        const allRoles = [
            { value: 'donor', label: 'Donor', level: 1 },
            { value: 'revoked', label: 'Revoked', level: 1 },
            { value: 'updater', label: 'Updater', level: 2 },
            { value: 'admin', label: 'Admin', level: 3 },
            { value: 'superadmin', label: 'Super Admin', level: 4 }
        ];

        let html = `<select class="form-select form-select-sm" style="width: 130px;">`;
        allRoles.forEach(r => {
            if (r.level < currentUserLevel || r.value === user.role) {
                const selected = user.role === r.value ? 'selected' : '';
                html += `<option value="${r.value}" ${selected}>${r.label}</option>`;
            }
        });
        html += `</select>`;
        return html;
    }

    function renderAdminsTable(list) {
        const tbody = document.getElementById('admins-table-body');
        if (!tbody) return;
        tbody.innerHTML = '';
        
        if (list.length === 0) {
            tbody.innerHTML = `<tr><td colspan="4" class="text-center py-4 text-secondary">No admins registered.</td></tr>`;
            return;
        }
        
        list.forEach(u => {
            const tr = document.createElement('tr');
            const targetLevel = roleLevels[u.role] ?? 1;
            const isSelf = toInt(u.id) === toInt(adminUser.id);
            // Super admin can edit other admins, but cannot edit themselves to prevent lock-out.
            const isEditable = targetLevel <= currentUserLevel && !isSelf;
            
            tr.innerHTML = `
                <td>
                    <span class="text-dark fw-bold">${u.fullName}</span>
                    ${isSelf ? '<span class="badge bg-secondary ms-1">You</span>' : ''}
                </td>
                <td>
                    <small>${u.email}</small><br>
                    <small class="text-secondary">${u.phone}</small>
                </td>
                <td>${getRoleSelectHTML(u, isEditable)}</td>
                <td class="text-end">
                    <div class="d-flex gap-1 justify-content-end" style="white-space: nowrap;">
                        <button onclick="saveAdminRole(${u.id}, this)" class="btn btn-sm btn-crimson rounded-circle" style="width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center;" title="Save modifications" ${isEditable ? '' : 'disabled'}>
                            <i class="bi bi-save"></i>
                        </button>
                        <button onclick="deleteAdmin(${u.id})" class="btn btn-sm btn-outline-danger rounded-circle" style="width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center;" title="Delete account" ${isEditable ? '' : 'disabled'}>
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </td>
            `;
            tbody.appendChild(tr);
        });
    }

    function renderAuditLogsTable(logs) {
        const tbody = document.getElementById('audit-table-body');
        if (!tbody) return;
        tbody.innerHTML = '';

        if (logs.length === 0) {
            tbody.innerHTML = `<tr><td colspan="4" class="text-center py-4 text-secondary">Operations logs empty.</td></tr>`;
            return;
        }

        logs.forEach(log => {
            const tr = document.createElement('tr');
            const logTime = new Date(log.timestamp).toLocaleString();
            
            tr.innerHTML = `
                <td><small class="text-secondary" style="font-size: 11px;">${logTime}</small></td>
                <td><strong style="font-size: 12px;">${log.user_name || 'System / Guest'}</strong></td>
                <td><span class="badge bg-dark border border-secondary" style="font-size: 10px;">${log.action}</span></td>
                <td><p class="mb-0 text-secondary" style="font-size: 11px; max-width: 400px; white-space: normal; line-height: 1.3;">${log.details}</p></td>
            `;
            tbody.appendChild(tr);
        });
    }

    window.saveAdminRole = async function(userId, btn) {
        const selectEl = btn.closest('tr').querySelector('select');
        const alertBox = document.getElementById('admins-alert');
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
                
                await loadAdmins();
            }
        } catch (error) {
            alert(error.message || "Failed to update administrator role.");
        } finally {
            btn.removeAttribute('disabled');
        }
    };

    window.deleteAdmin = async function(userId) {
        if (!confirm("Are you sure you want to permanently delete this administrator account? This will delete all their audit log ties and registrations. This cannot be undone.")) {
            return;
        }

        const alertBox = document.getElementById('admins-alert');
        alertBox.style.display = 'none';

        try {
            const result = await apiFetch(`api/api.php?endpoint=users&id=${userId}`, {
                method: 'DELETE'
            });

            if (result.status === 'success') {
                alertBox.className = "alert alert-success";
                alertBox.textContent = result.message;
                alertBox.style.display = 'block';

                await loadAdmins();
            }
        } catch (error) {
            alertBox.className = "alert alert-danger";
            alertBox.textContent = error.message || "Failed to delete admin.";
            alertBox.style.display = 'block';
        }
    };
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
