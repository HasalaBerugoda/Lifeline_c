<?php
$pageTitle = "Admin Dashboard";
$activePage = "dashboard";
require_once __DIR__ . '/includes/header.php';
?>

<script>
// Restrict to admins and updaters
const auth = checkAuth(['admin', 'updater', 'superadmin']);
</script>

<div class="hero-header">
    <h1 class="hero-title">Admin Dashboard</h1>
    <p class="hero-subtitle">Real-time statistics, contact messages, and system audit logs.</p>
</div>

<div class="container overlap-container">
    <div class="row g-4">
        <!-- Left Column: Sidebar Menu -->
        <div class="col-lg-3 col-md-4">
            <?php require_once __DIR__ . '/includes/sidebar.php'; ?>
        </div>

        <!-- Right Column: Main Dashboard Content -->
        <div class="col-lg-9 col-md-8">
            <!-- Admin Aggregated Stats -->
            <div class="row g-3 mb-4" id="admin-stats-row">
                <div class="col-sm-3 col-6">
                    <div class="premium-card text-center p-3">
                        <div class="stat-number fs-4 text-dark" id="stat-total-users">0</div>
                        <div class="stat-label" style="font-size:10px;">Total Accounts</div>
                    </div>
                </div>
                <div class="col-sm-3 col-6">
                    <div class="premium-card text-center p-3">
                        <div class="stat-number fs-4 text-danger" id="stat-total-donors">0</div>
                        <div class="stat-label" style="font-size:10px;">Donors</div>
                    </div>
                </div>
                <div class="col-sm-3 col-6">
                    <div class="premium-card text-center p-3">
                        <div class="stat-number fs-4 text-info" id="stat-total-updaters">0</div>
                        <div class="stat-label" style="font-size:10px;">Staff Updaters</div>
                    </div>
                </div>
                <div class="col-sm-3 col-6">
                    <div class="premium-card text-center p-3">
                        <div class="stat-number fs-4 text-warning" id="stat-total-messages">0</div>
                        <div class="stat-label" style="font-size:10px;">Messages</div>
                    </div>
                </div>
            </div>

            <!-- Tab Panels Card -->
            <div class="premium-card">
                <ul class="nav nav-tabs custom-tabs mb-4" id="adminTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="inbox-tab" data-bs-toggle="tab" data-bs-target="#inbox-panel" type="button" role="tab" aria-controls="inbox-panel" aria-selected="true">
                            <i class="bi bi-envelope-paper-fill me-1"></i> Inquiries Inbox
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="audit-tab" data-bs-toggle="tab" data-bs-target="#audit-panel" type="button" role="tab" aria-controls="audit-panel" aria-selected="false">
                            <i class="bi bi-shield-lock-fill me-1"></i> System Audit Logs
                        </button>
                    </li>
                </ul>

                <div class="tab-content" id="adminTabContent">
                    <!-- Inquiries Inbox -->
                    <div class="tab-pane fade show active" id="inbox-panel" role="tabpanel" aria-labelledby="inbox-tab">
                        <div id="inbox-alert" class="alert alert-success" style="display: none;"></div>
                        <div class="table-responsive">
                            <table class="table table-striped align-middle">
                                <thead>
                                    <tr>
                                        <th>From</th>
                                        <th>Subject</th>
                                        <th>Message</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                        <th class="text-end">Action Toggles</th>
                                    </tr>
                                </thead>
                                <tbody id="inbox-table-body">
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-secondary">
                                            <div class="spinner-border spinner-border-sm text-danger me-1"></div> Loading contact logs...
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Audit Logs -->
                    <div class="tab-pane fade" id="audit-panel" role="tabpanel" aria-labelledby="audit-tab">
                        <div class="table-responsive">
                            <table class="table table-striped align-middle">
                                <thead>
                                    <tr>
                                        <th>Timestamp</th>
                                        <th>User Account</th>
                                        <th>Action Taken</th>
                                        <th>Description Details</th>
                                    </tr>
                                </thead>
                                <tbody id="audit-table-body">
                                    <tr>
                                        <td colspan="4" class="text-center py-4 text-secondary">
                                            <div class="spinner-border spinner-border-sm text-danger me-1"></div> Loading system audit trail...
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

<!-- Reply Inquiry Modal -->
<div class="modal fade" id="replyModal" tabindex="-1" aria-labelledby="replyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="border-radius: 24px; overflow: hidden; border: none; box-shadow: 0 25px 60px rgba(17, 24, 39, 0.2);">
            <div class="modal-header bg-dark text-white p-4" style="background: linear-gradient(135deg, #111827 0%, #1f2937 100%) !important; border-bottom: 1px solid rgba(255, 255, 255, 0.05);">
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-danger text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px; background-color: var(--color-crimson) !important;">
                        <i class="bi bi-envelope-fill fs-5"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold mb-0" id="replyModalLabel" style="font-family: var(--font-heading);">Compose Email Response</h5>
                        <small class="text-secondary" style="font-size: 12px; color: rgba(255, 255, 255, 0.6) !important;">Replying directly via LifeLine mail dispatch</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <div class="modal-body p-4 bg-light-subtle">
                <form id="reply-form">
                    <input type="hidden" id="reply-message-id">
                    
                    <div class="row g-3">
                        <!-- Recipient Field -->
                        <div class="col-md-6">
                            <label for="reply-to" class="form-label fw-bold small text-secondary tracking-wider uppercase mb-1">To (Recipient)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-person-fill"></i></span>
                                <input type="email" class="form-control border-start-0 ps-0" id="reply-to" readonly style="background-color: #ffffff; font-weight: 500;">
                            </div>
                        </div>
                        
                        <!-- Subject Field -->
                        <div class="col-md-6">
                            <label for="reply-subject" class="form-label fw-bold small text-secondary tracking-wider uppercase mb-1">Subject Line</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-chat-left-text-fill"></i></span>
                                <input type="text" class="form-control border-start-0 ps-0" id="reply-subject" required style="font-weight: 500;">
                            </div>
                        </div>
                        
                        <!-- Original Message Preview -->
                        <div class="col-12">
                            <label class="form-label fw-bold small text-secondary tracking-wider uppercase mb-1">Original Inquiry Reference</label>
                            <div class="p-3 bg-white rounded shadow-sm" style="border: 1px solid var(--color-light-gray); border-left: 4px solid var(--color-crimson) !important;">
                                <div id="original-message-text" class="text-muted small" style="max-height: 100px; overflow-y: auto; font-style: italic; white-space: pre-wrap; line-height: 1.5;">
                                    <!-- Filled dynamically -->
                                </div>
                            </div>
                        </div>
                        
                        <!-- Reply Content -->
                        <div class="col-12">
                            <label for="reply-body" class="form-label fw-bold small text-secondary tracking-wider uppercase mb-1">Your Response Message</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white align-items-start pt-2 border-end-0 text-muted"><i class="bi bi-pencil-fill"></i></span>
                                <textarea class="form-control border-start-0 ps-0" id="reply-body" rows="8" placeholder="Write your professional response message here..." required style="resize: none;"></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top" style="border-top-color: var(--color-light-gray) !important;">
                        <button type="button" class="btn btn-pill btn-outline-secondary px-4" data-bs-dismiss="modal">Discard</button>
                        <button type="submit" class="btn btn-pill btn-crimson px-4" id="btn-send-reply">
                            <span class="spinner-border spinner-border-sm me-1" id="reply-spinner" style="display: none;" role="status" aria-hidden="true"></span>
                            <i class="bi bi-send-fill me-1" id="reply-send-icon"></i> Send Email
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', async function() {
    const adminUser = auth.user;

    // Load initial dashboard metrics and tables
    await loadAdminDashboard();

    // ----------------------------------------------------
    // Load Admin Dashboard (Stats + Audit + Messages)
    // ----------------------------------------------------
    async function loadAdminDashboard() {
        try {
            const result = await apiFetch('api/api.php?endpoint=admin_dashboard');
            if (result.status === 'success') {
                const data = result.data;
                
                // Set stats counts
                document.getElementById('stat-total-users').textContent = data.stats.total_users;
                document.getElementById('stat-total-donors').textContent = data.stats.total_donors;
                document.getElementById('stat-total-updaters').textContent = data.stats.total_updaters;
                document.getElementById('stat-total-messages').textContent = data.stats.total_messages;

                // Load messages
                renderMessages(data.recent_messages);

                // Load audit logs
                renderAuditLogs(data.recent_audits);
            }
        } catch (e) {
            console.error('Failed to load admin dashboard aggregations', e);
        }
    }

    // ----------------------------------------------------
    // Messages Rendering
    // ----------------------------------------------------
    function renderMessages(messages) {
        const tbody = document.getElementById('inbox-table-body');
        tbody.innerHTML = '';

        if (messages && messages.length > 0) {
            messages.forEach(m => {
                const tr = document.createElement('tr');
                
                let badgeClass = 'bg-secondary';
                if (m.status === 'Unread') badgeClass = 'bg-danger';
                if (m.status === 'Replied') badgeClass = 'bg-success';
                
                const dateStr = new Date(m.created_at).toLocaleDateString('en-US', {
                    month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit'
                });

                tr.innerHTML = `
                    <td>
                        <span class="fw-bold">${m.name}</span><br>
                        <small class="text-secondary">${m.email}</small>
                    </td>
                    <td><strong>${m.subject}</strong></td>
                    <td><p class="mb-0 text-truncate" style="max-width: 250px;" title="${m.message}">${m.message}</p></td>
                    <td><small>${dateStr}</small></td>
                    <td><span class="badge ${badgeClass}">${m.status}</span></td>
                    <td class="text-end">
                        <div class="btn-group" role="group">
                            <button onclick="updateMessageStatus(${m.id}, 'Read')" class="btn btn-sm btn-outline-secondary rounded-circle" style="width: 28px; height: 28px; padding: 0; display: inline-flex; align-items: center; justify-content: center;" title="Mark Read"><i class="bi bi-eye"></i></button>
                            <button 
                                class="btn btn-sm btn-outline-success rounded-circle reply-btn" 
                                style="width: 28px; height: 28px; padding: 0; display: inline-flex; align-items: center; justify-content: center;"
                                data-id="${m.id}" 
                                data-email="${m.email}" 
                                data-subject="${escapeHtml(m.subject)}" 
                                data-message="${escapeHtml(m.message)}"
                                title="Reply Email">
                                <i class="bi bi-reply-fill"></i>
                            </button>
                        </div>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        } else {
            tbody.innerHTML = `<tr><td colspan="6" class="text-center py-4 text-secondary">Inbox empty. No client inquiries.</td></tr>`;
        }
    }

    // Toggle contact message status
    window.updateMessageStatus = async function(id, status) {
        const alertBox = document.getElementById('inbox-alert');
        alertBox.style.display = 'none';

        try {
            const result = await apiFetch('api/api.php?endpoint=contact', {
                method: 'PATCH',
                body: JSON.stringify({ id, status })
            });

            if (result.status === 'success') {
                alertBox.textContent = result.message;
                alertBox.style.display = 'block';
                await loadAdminDashboard();
            }
        } catch (error) {
            alert(error.message || "Failed to toggle status.");
        }
    };

    // ----------------------------------------------------
    // Audit Logs Rendering
    // ----------------------------------------------------
    function renderAuditLogs(logs) {
        const tbody = document.getElementById('audit-table-body');
        tbody.innerHTML = '';

        if (logs && logs.length > 0) {
            logs.forEach(log => {
                const tr = document.createElement('tr');
                const logTime = new Date(log.timestamp).toLocaleString();
                
                tr.innerHTML = `
                    <td><small class="text-secondary">${logTime}</small></td>
                    <td><strong>${log.user_name || 'System / Guest'}</strong></td>
                    <td><span class="badge bg-secondary">${log.action}</span></td>
                    <td><p class="mb-0 text-secondary" style="font-size:12px;">${log.details}</p></td>
                `;
                tbody.appendChild(tr);
            });
        } else {
            tbody.innerHTML = `<tr><td colspan="4" class="text-center py-4 text-secondary">Audit logs empty.</td></tr>`;
        }
    }

    // HTML Escaper Helper
    function escapeHtml(str) {
        if (!str) return '';
        return str.replace(/&/g, '&amp;')
                  .replace(/</g, '&lt;')
                  .replace(/>/g, '&gt;')
                  .replace(/"/g, '&quot;')
                  .replace(/'/g, '&#039;');
    }

    // Bootstrap Modal Reference
    let replyModalInstance = null;

    window.openReplyModal = function(id, email, subject, message) {
        document.getElementById('reply-message-id').value = id;
        document.getElementById('reply-to').value = email;
        document.getElementById('reply-subject').value = subject.startsWith('Re:') ? subject : `Re: ${subject}`;
        document.getElementById('original-message-text').textContent = message;
        document.getElementById('reply-body').value = '';
        
        if (!replyModalInstance) {
            replyModalInstance = new bootstrap.Modal(document.getElementById('replyModal'));
        }
        replyModalInstance.show();
    };

    // Event delegation for reply buttons in inbox table
    const inboxTableBody = document.getElementById('inbox-table-body');
    if (inboxTableBody) {
        inboxTableBody.addEventListener('click', function(e) {
            const btn = e.target.closest('.reply-btn');
            if (btn) {
                const id = btn.getAttribute('data-id');
                const email = btn.getAttribute('data-email');
                const subject = btn.getAttribute('data-subject');
                const message = btn.getAttribute('data-message');
                openReplyModal(id, email, subject, message);
            }
        });
    }

    // Reply Form Submission
    const replyForm = document.getElementById('reply-form');
    if (replyForm) {
        replyForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const id = document.getElementById('reply-message-id').value;
            const to = document.getElementById('reply-to').value;
            const subject = document.getElementById('reply-subject').value;
            const message = document.getElementById('reply-body').value;
            
            const btn = document.getElementById('btn-send-reply');
            const spinner = document.getElementById('reply-spinner');
            const icon = document.getElementById('reply-send-icon');
            const alertBox = document.getElementById('inbox-alert');
            
            btn.setAttribute('disabled', 'true');
            if (spinner) spinner.style.display = 'inline-block';
            if (icon) icon.style.display = 'none';
            alertBox.style.display = 'none';
            
            try {
                const result = await apiFetch('api/api.php?endpoint=contact', {
                    method: 'POST',
                    body: JSON.stringify({
                        action: 'reply',
                        id: id,
                        to: to,
                        subject: subject,
                        message: message
                    })
                });
                
                if (result.status === 'success') {
                    alertBox.className = "alert alert-success";
                    alertBox.textContent = result.message;
                    alertBox.style.display = 'block';
                    
                    if (replyModalInstance) {
                        replyModalInstance.hide();
                    }
                    
                    await loadAdminDashboard();
                }
            } catch (error) {
                alert(error.message || "Failed to send email reply.");
            } finally {
                btn.removeAttribute('disabled');
                if (spinner) spinner.style.display = 'none';
                if (icon) icon.style.display = 'inline-block';
            }
        });
    }
});
</script>

<style>
.btn-xs {
    padding: 2px 6px;
    font-size: 11px;
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
