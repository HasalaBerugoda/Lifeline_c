// JWT Authentication Helper and Session Utilities for LifeLine (Static GitHub Pages Mock)

// Base path detection helper
function getBasePath() {
    const path = window.location.pathname;
    return path.substring(0, path.lastIndexOf('/') + 1);
}

// Client-side JWT Decoder
function parseJWT(token) {
    try {
        const base64Url = token.split('.')[1];
        const base64 = base64Url.replace(/-/g, '+').replace(/_/g, '/');
        const jsonPayload = decodeURIComponent(atob(base64).split('').map(function(c) {
            return '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2);
        }).join(''));
        return JSON.parse(jsonPayload);
    } catch (e) {
        return null;
    }
}

// Check authorization on page load
function checkAuth(allowedRoles = []) {
    const token = localStorage.getItem('ll_token');
    const userJson = localStorage.getItem('ll_user');
    
    const basePath = getBasePath();

    if (!token || !userJson) {
        localStorage.removeItem('ll_token');
        localStorage.removeItem('ll_user');
        window.location.href = basePath + 'index.html';
        return;
    }

    const payload = parseJWT(token);
    if (!payload || (payload.exp && payload.exp < Date.now() / 1000)) {
        localStorage.removeItem('ll_token');
        localStorage.removeItem('ll_user');
        window.location.href = basePath + 'index.html';
        return;
    }

    const user = JSON.parse(userJson);
    if (allowedRoles.length > 0 && !allowedRoles.includes(user.role)) {
        window.location.href = basePath + 'home.html';
        return;
    }

    return { token, user };
}

// Logout session
function logout() {
    localStorage.removeItem('ll_token');
    localStorage.removeItem('ll_user');
    window.location.href = getBasePath() + 'index.html';
}

function getAuthHeader() {
    const token = localStorage.getItem('ll_token');
    if (!token) return {};
    return { 'Authorization': 'Bearer ' + token };
}

// ----------------------------------------------------
// Mock Data Engine (LocalStorage Database)
// ----------------------------------------------------
function initMockDB(force = false) {
    if (!force && localStorage.getItem('ll_mock_db')) {
        return JSON.parse(localStorage.getItem('ll_mock_db'));
    }

    const db = {
        users: [
            { id: 1, donor_number: 'LL-0001', fullName: 'System Administrator', email: 'admin@lifeline.com', phone: '+94771234567', province: 'Western', district: 'Colombo', town: 'Colombo 3', bloodType: 'O+', role: 'admin', facility_name: null, created_at: new Date().toISOString() },
            { id: 2, donor_number: 'LL-0002', fullName: 'Central Hospital Updater', email: 'updater@lifeline.com', phone: '+94777654321', province: 'Central', district: 'Kandy', town: 'Peradeniya', bloodType: 'A+', role: 'updater', facility_name: 'Central General Hospital', created_at: new Date().toISOString() },
            { id: 3, donor_number: 'LL-0003', fullName: 'John Doe', email: 'donor@lifeline.com', phone: '+94711112222', province: 'Western', district: 'Colombo', town: 'Nugegoda', bloodType: 'O+', role: 'donor', facility_name: null, created_at: new Date().toISOString() },
            { id: 4, donor_number: 'LL-0004', fullName: 'Jane Smith', email: 'jane@lifeline.com', phone: '+94722223333', province: 'Southern', district: 'Galle', town: 'Unawatuna', bloodType: 'B-', role: 'donor', facility_name: null, created_at: new Date().toISOString() },
            { id: 5, donor_number: 'LL-0005', fullName: 'Revoked Donor', email: 'revoked@lifeline.com', phone: '+94755554444', province: 'Northern', district: 'Jaffna', town: 'Jaffna', bloodType: 'AB+', role: 'revoked', facility_name: null, created_at: new Date().toISOString() },
            { id: 6, donor_number: 'LL-0006', fullName: 'Saman Perera', email: 'saman@lifeline.com', phone: '+94779998888', province: 'Western', district: 'Gampaha', town: 'Negombo', bloodType: 'A-', role: 'donor', facility_name: null, created_at: new Date().toISOString() }
        ],
        blood_inventory: {
            oPos: 142, aPos: 110, bPos: 78, abPos: 24,
            oNeg: 15, aNeg: 8, bNeg: 5, abNeg: 2,
            platelets: 18
        },
        donations: [
            { id: 1, user_id: 3, blood_type: 'O+', volume_ml: 450, location: 'Colombo General Hospital', hemoglobin: 13.5, blood_pressure: '120/80', weight: 68.5, donation_date: '2026-03-24' },
            { id: 2, user_id: 3, blood_type: 'O+', volume_ml: 350, location: 'National Blood Center Colombo', hemoglobin: 14.0, blood_pressure: '118/75', weight: 69.0, donation_date: '2026-05-15' },
            { id: 3, user_id: 4, blood_type: 'B-', volume_ml: 350, location: 'Galle Karapitiya Hospital', hemoglobin: 12.8, blood_pressure: '115/80', weight: 58.0, donation_date: '2026-04-10' }
        ],
        camps: [
            { id: 1, name: 'National Hero Blood Drive', date: new Date(Date.now() + 5*24*60*60*1000).toISOString().substring(0, 10), time: '09:00:00', location: 'National Blood Center, Colombo 05', organizer: 'Ministry of Health', description: 'A grand blood donation camp organized to support the national pediatric cancer ward requirements. All blood groups needed.', created_by: 1, created_at: new Date().toISOString() },
            { id: 2, name: 'Kandy Youth Donation Campaign', date: new Date(Date.now() + 12*24*60*60*1000).toISOString().substring(0, 10), time: '08:30:00', location: 'Kandy City Centre, Kandy', organizer: 'Rotaract Club of Kandy', description: 'Annual youth blood donation campaign to support Central Province blood reserves.', created_by: 1, created_at: new Date().toISOString() },
            { id: 3, name: 'Galle Beach Blood Drive', date: new Date(Date.now() - 10*24*60*60*1000).toISOString().substring(0, 10), time: '10:00:00', location: 'Karapitiya Medical College Grounds, Galle', organizer: 'Galle Lions Club', description: 'Community blood donation campaign to support post-monsoon medical emergencies.', created_by: 1, created_at: new Date().toISOString() }
        ],
        camp_registrations: [
            { id: 1, camp_id: 1, user_id: 3, registered_at: new Date().toISOString(), attended: false },
            { id: 2, camp_id: 2, user_id: 4, registered_at: new Date().toISOString(), attended: true }
        ],
        contact_messages: [
            { id: 1, name: 'Amara Fernando', email: 'amara@example.com', subject: 'Camp Participation Inquiry', message: 'Hello, I would like to know if there are any specific guidelines for first-time donors at the Kandy Youth Drive.', status: 'Unread', created_at: new Date().toISOString() },
            { id: 2, name: 'Ruwan Kumara', email: 'ruwan@example.com', subject: 'Organizing a blood camp', message: 'We want to organize a donation campaign at our university. How can we register as an organizing entity?', status: 'Read', created_at: new Date().toISOString() }
        ],
        urgent_requests: [
            { id: 1, blood_type: 'AB-', hospital_name: 'Badulla General Hospital', status_level: 'Critical Level', created_at: new Date().toISOString() },
            { id: 2, blood_type: 'AB+', hospital_name: 'Balangoda Base', status_level: 'Critical Level', created_at: new Date().toISOString() },
            { id: 3, blood_type: 'B+', hospital_name: 'Kandy National Hospital', status_level: 'Critical Level', created_at: new Date().toISOString() }
        ],
        email_log: [],
        audit_log: [
            { id: 1, user_id: 1, action: 'System Setup', details: 'Database created, tables initialized, seeded users, CSV file generated, and dummy data inserted.', timestamp: new Date().toISOString() }
        ]
    };

    localStorage.setItem('ll_mock_db', JSON.stringify(db));
    return db;
}

// Initialise DB
initMockDB();

function saveMockDB(db) {
    localStorage.setItem('ll_mock_db', JSON.stringify(db));
}

function mockGenerateToken(user) {
    const payload = {
        id: user.id,
        email: user.email,
        role: user.role,
        exp: Math.floor(Date.now() / 1000) + 24 * 60 * 60
    };
    return "header." + btoa(JSON.stringify(payload)) + ".signature";
}

function getMockCurrentUser() {
    const token = localStorage.getItem('ll_token');
    if (!token) return null;
    const parts = token.split('.');
    if (parts.length !== 3) return null;
    try {
        return JSON.parse(atob(parts[1]));
    } catch(e) {
        return null;
    }
}

// ----------------------------------------------------
// Mock Fetch Router
// ----------------------------------------------------
async function apiFetch(endpoint, options = {}) {
    // Artificial delay to simulate real latency
    await new Promise(r => setTimeout(r, 150));

    const db = initMockDB();
    const currentUser = getMockCurrentUser();
    
    // Parse URL and params
    const cleanEndpoint = endpoint.startsWith('/') ? endpoint.substring(1) : endpoint;
    const urlObj = new URL(cleanEndpoint, 'http://mock.api/');
    const path = urlObj.pathname;
    const searchParams = urlObj.searchParams;
    
    const method = options.method ? options.method.toUpperCase() : 'GET';
    const body = options.body ? JSON.parse(options.body) : {};

    // 1. Auth Endpoint
    if (path === 'api/auth.php') {
        const action = searchParams.get('action');
        if (action === 'login') {
            const { email, password } = body;
            const user = db.users.find(u => u.email === email);
            if (!user) {
                return { status: 'error', message: 'Invalid email or password.' };
            }
            // Mock authentication: Simple match for testing
            const expectedPasswordName = user.role.charAt(0).toUpperCase() + user.role.slice(1) + "Password123";
            // Check Saman, John, Jane
            const isMatch = (user.role === 'donor' && password === 'DonorPassword123') ||
                            (user.role === 'updater' && password === 'UpdaterPassword123') ||
                            (user.role === 'admin' && password === 'AdminPassword123') ||
                            (user.role === 'revoked' && password === 'RevokedPassword123');

            if (!isMatch) {
                return { status: 'error', message: 'Invalid email or password.' };
            }
            if (user.role === 'revoked') {
                return { status: 'error', message: 'Your account has been suspended. Please contact administration.' };
            }

            const token = mockGenerateToken(user);
            db.audit_log.unshift({ id: db.audit_log.length + 1, user_id: user.id, action: 'Login', details: 'User logged in.', timestamp: new Date().toISOString() });
            saveMockDB(db);

            return { status: 'success', message: 'Login successful.', token, user };
        } 
        
        if (action === 'register') {
            const { fullName, email, phone, province, district, town, bloodType, password } = body;
            if (db.users.find(u => u.email === email)) {
                return { status: 'error', message: 'An account with this email already exists.' };
            }
            const nextId = db.users.length + 1;
            const donor_number = 'LL-' + String(nextId).padStart(4, '0');
            const newUser = {
                id: nextId,
                donor_number,
                fullName,
                email,
                phone,
                province,
                district,
                town,
                bloodType,
                role: 'donor',
                facility_name: null,
                created_at: new Date().toISOString()
            };
            db.users.push(newUser);
            const token = mockGenerateToken(newUser);
            db.audit_log.unshift({ id: db.audit_log.length + 1, user_id: nextId, action: 'Register', details: `User registered as a donor. Assigned Donor Number: ${donor_number}`, timestamp: new Date().toISOString() });
            saveMockDB(db);

            return { status: 'success', message: 'Registration successful.', token, user: newUser };
        }
    }

    // 2. Main API Router
    if (path === 'api/api.php') {
        const endpointParam = searchParams.get('endpoint');
        
        // A. Profile
        if (endpointParam === 'profile') {
            const id = parseInt(searchParams.get('id'));
            if (method === 'GET') {
                const user = db.users.find(u => u.id === id);
                if (!user) return { status: 'error', message: 'Profile not found.' };
                return { status: 'success', data: user };
            }
            if (method === 'PUT') {
                const userIdx = db.users.findIndex(u => u.id === id);
                if (userIdx === -1) return { status: 'error', message: 'Profile not found.' };
                db.users[userIdx] = { ...db.users[userIdx], ...body };
                db.audit_log.unshift({ id: db.audit_log.length + 1, user_id: id, action: 'Update Profile', details: 'User updated profile details.', timestamp: new Date().toISOString() });
                saveMockDB(db);
                return { status: 'success', message: 'Profile updated successfully.' };
            }
        }

        // B. Urgent Requests
        if (endpointParam === 'urgent_requests') {
            if (method === 'GET') {
                return { status: 'success', data: db.urgent_requests };
            }
            // Require updater/admin for mutations
            if (!currentUser || !['admin', 'updater'].includes(currentUser.role)) {
                return { status: 'error', message: 'Access forbidden: Insufficient privileges.' };
            }
            if (method === 'POST') {
                const newId = db.urgent_requests.length > 0 ? Math.max(...db.urgent_requests.map(u => u.id)) + 1 : 1;
                const newReq = {
                    id: newId,
                    blood_type: body.blood_type,
                    hospital_name: body.hospital_name,
                    status_level: body.status_level,
                    created_at: new Date().toISOString()
                };
                db.urgent_requests.unshift(newReq);
                db.audit_log.unshift({ id: db.audit_log.length + 1, user_id: currentUser.id, action: 'Create Urgent Request', details: `Added urgent request ID ${newId} for ${body.blood_type} at ${body.hospital_name} with status '${body.status_level}'.`, timestamp: new Date().toISOString() });
                saveMockDB(db);
                return { status: 'success', message: 'Urgent request added successfully.', data: { id: newId } };
            }
            if (method === 'PUT') {
                const idx = db.urgent_requests.findIndex(u => u.id === body.id);
                if (idx === -1) return { status: 'error', message: 'Urgent request not found.' };
                const old = db.urgent_requests[idx];
                db.urgent_requests[idx] = { ...old, ...body };
                db.audit_log.unshift({ id: db.audit_log.length + 1, user_id: currentUser.id, action: 'Update Urgent Request', details: `Updated urgent request ID ${body.id}: changed to ${body.blood_type} at ${body.hospital_name} (${body.status_level}) (was: ${old.blood_type} at ${old.hospital_name}).`, timestamp: new Date().toISOString() });
                saveMockDB(db);
                return { status: 'success', message: 'Urgent request updated successfully.' };
            }
            if (method === 'DELETE') {
                const id = parseInt(searchParams.get('id'));
                const idx = db.urgent_requests.findIndex(u => u.id === id);
                if (idx === -1) return { status: 'error', message: 'Urgent request not found.' };
                const old = db.urgent_requests[idx];
                db.urgent_requests.splice(idx, 1);
                db.audit_log.unshift({ id: db.audit_log.length + 1, user_id: currentUser.id, action: 'Delete Urgent Request', details: `Deleted urgent request for ${old.blood_type} at ${old.hospital_name} (ID: ${id}).`, timestamp: new Date().toISOString() });
                saveMockDB(db);
                return { status: 'success', message: 'Urgent request deleted successfully.' };
            }
        }

        // C. Contact messages
        if (endpointParam === 'contact') {
            if (method === 'POST') {
                if (body.action === 'reply') {
                    if (!currentUser || currentUser.role !== 'admin') return { status: 'error', message: 'Admin role required.' };
                    const msgIdx = db.contact_messages.findIndex(m => m.id === body.id);
                    if (msgIdx !== -1) {
                        db.contact_messages[msgIdx].status = 'Replied';
                    }
                    db.email_log.unshift({
                        id: db.email_log.length + 1,
                        recipient: body.to,
                        subject: body.subject,
                        status: 'sent',
                        error_msg: null,
                        sent_at: new Date().toISOString()
                    });
                    saveMockDB(db);
                    return { status: 'success', message: 'Reply sent successfully. Email dispatched.' };
                } else {
                    const nextId = db.contact_messages.length + 1;
                    db.contact_messages.unshift({
                        id: nextId,
                        name: body.name,
                        email: body.email,
                        subject: body.subject,
                        message: body.message,
                        status: 'Unread',
                        created_at: new Date().toISOString()
                    });
                    saveMockDB(db);
                    return { status: 'success', message: 'Message submitted successfully. We will get back to you soon.' };
                }
            }
            if (method === 'GET') {
                if (!currentUser || currentUser.role !== 'admin') return { status: 'error', message: 'Admin role required.' };
                return { status: 'success', data: db.contact_messages };
            }
            if (method === 'PATCH') {
                if (!currentUser || currentUser.role !== 'admin') return { status: 'error', message: 'Admin role required.' };
                const msgIdx = db.contact_messages.findIndex(m => m.id === body.id);
                if (msgIdx !== -1) {
                    db.contact_messages[msgIdx].status = body.status;
                    saveMockDB(db);
                    return { status: 'success', message: 'Message status updated.' };
                }
                return { status: 'error', message: 'Message not found.' };
            }
        }

        // D. Admin Dashboard
        if (endpointParam === 'admin_dashboard') {
            if (!currentUser || currentUser.role !== 'admin') return { status: 'error', message: 'Admin role required.' };
            const totalUsers = db.users.length;
            const totalDonors = db.users.filter(u => u.role === 'donor').length;
            const totalUpdaters = db.users.filter(u => u.role === 'updater').length;
            const totalCamps = db.camps.length;
            const totalMessages = db.contact_messages.length;
            const totalDonations = db.donations.length;
            const totalVolume = db.donations.reduce((sum, d) => sum + d.volume_ml, 0);

            const recentUsers = [...db.users].sort((a,b) => b.id - a.id).slice(0, 5);
            const recentMessages = [...db.contact_messages].sort((a,b) => b.id - a.id).slice(0, 5);
            
            // Map logs with user names
            const recentAudits = db.audit_log.slice(0, 10).map(log => {
                const u = db.users.find(x => x.id === log.user_id);
                return {
                    ...log,
                    user_name: u ? u.fullName : 'System'
                };
            });

            return {
                status: 'success',
                data: {
                    stats: {
                        total_users: totalUsers,
                        total_donors: totalDonors,
                        total_updaters: totalUpdaters,
                        total_camps: totalCamps,
                        total_messages: totalMessages,
                        total_donations: totalDonations,
                        total_volume_ml: totalVolume
                    },
                    recent_users: recentUsers,
                    recent_messages: recentMessages,
                    recent_audits: recentAudits
                }
            };
        }

        // E. Users Management
        if (endpointParam === 'users') {
            if (!currentUser || currentUser.role !== 'admin') return { status: 'error', message: 'Admin role required.' };
            if (method === 'GET') {
                return { status: 'success', data: db.users };
            }
            if (method === 'PUT') {
                const idx = db.users.findIndex(u => u.id === body.id);
                if (idx === -1) return { status: 'error', message: 'User not found.' };
                const old = db.users[idx];
                db.users[idx].role = body.role;
                db.users[idx].facility_name = body.role === 'updater' ? body.facility_name : null;
                db.audit_log.unshift({ id: db.audit_log.length + 1, user_id: currentUser.id, action: 'Modify User', details: `Changed user ${old.fullName} (ID: ${body.id}) role from ${old.role} to ${body.role}.`, timestamp: new Date().toISOString() });
                saveMockDB(db);
                return { status: 'success', message: 'User role updated successfully.' };
            }
            if (method === 'DELETE') {
                const id = parseInt(searchParams.get('id'));
                if (id === currentUser.id) return { status: 'error', message: 'Action blocked: You cannot delete your own admin account.' };
                const idx = db.users.findIndex(u => u.id === id);
                if (idx === -1) return { status: 'error', message: 'User not found.' };
                const old = db.users[idx];
                db.users.splice(idx, 1);
                db.audit_log.unshift({ id: db.audit_log.length + 1, user_id: currentUser.id, action: 'Delete User', details: `Deleted user account: ${old.fullName} (ID: ${id})`, timestamp: new Date().toISOString() });
                saveMockDB(db);
                return { status: 'success', message: 'User account deleted successfully.' };
            }
        }
    }

    // 3. Camps Endpoint
    if (path === 'api/camps.php') {
        const action = searchParams.get('action');
        if (method === 'GET') {
            if (action === 'all') {
                if (!currentUser || !['admin', 'updater'].includes(currentUser.role)) return { status: 'error', message: 'Unauthorized.' };
                const data = db.camps.map(c => {
                    const creator = db.users.find(u => u.id === c.created_by);
                    const regCount = db.camp_registrations.filter(r => r.camp_id === c.id).length;
                    return {
                        ...c,
                        creator_name: creator ? creator.fullName : 'Admin',
                        registered_count: regCount
                    };
                });
                return { status: 'success', data };
            }
            if (action === 'my_registrations') {
                if (!currentUser) return { status: 'error', message: 'Login required.' };
                const regs = db.camp_registrations.filter(r => r.user_id === currentUser.id);
                const data = regs.map(r => {
                    const c = db.camps.find(x => x.id === r.camp_id);
                    return {
                        ...c,
                        registered_at: r.registered_at,
                        attended: r.attended
                    };
                });
                return { status: 'success', data };
            }
            if (action === 'participants') {
                if (!currentUser || !['admin', 'updater'].includes(currentUser.role)) return { status: 'error', message: 'Unauthorized.' };
                const campId = parseInt(searchParams.get('id'));
                const regs = db.camp_registrations.filter(r => r.camp_id === campId);
                const data = regs.map(r => {
                    const u = db.users.find(x => x.id === r.user_id);
                    return {
                        registration_id: r.id,
                        registered_at: r.registered_at,
                        attended: r.attended,
                        user_id: u.id,
                        donor_number: u.donor_number,
                        fullName: u.fullName,
                        email: u.email,
                        phone: u.phone,
                        bloodType: u.bloodType
                    };
                });
                return { status: 'success', data };
            }
            // Public upcoming list
            const upcoming = db.camps.filter(c => new Date(c.date) >= new Date().setHours(0,0,0,0)).map(c => {
                const regCount = db.camp_registrations.filter(r => r.camp_id === c.id).length;
                return {
                    ...c,
                    registered_count: regCount
                };
            });
            return { status: 'success', data: upcoming };
        }
        if (method === 'POST') {
            if (action === 'register') {
                if (!currentUser) return { status: 'error', message: 'Login required.' };
                const campId = parseInt(searchParams.get('id'));
                // Check if already registered
                const exists = db.camp_registrations.find(r => r.camp_id === campId && r.user_id === currentUser.id);
                if (exists) return { status: 'error', message: 'You are already registered for this camp.' };
                db.camp_registrations.push({
                    id: db.camp_registrations.length + 1,
                    camp_id: campId,
                    user_id: currentUser.id,
                    registered_at: new Date().toISOString(),
                    attended: false
                });
                saveMockDB(db);
                return { status: 'success', message: 'Successfully registered for campaign.' };
            }
            if (action === 'toggle_attendance') {
                if (!currentUser || !['admin', 'updater'].includes(currentUser.role)) return { status: 'error', message: 'Unauthorized.' };
                const idx = db.camp_registrations.findIndex(r => r.id === body.registration_id);
                if (idx !== -1) {
                    db.camp_registrations[idx].attended = body.attended;
                    saveMockDB(db);
                    return { status: 'success' };
                }
                return { status: 'error', message: 'Registration record not found.' };
            }
            // Create Camp
            if (!currentUser || !['admin', 'updater'].includes(currentUser.role)) return { status: 'error', message: 'Unauthorized.' };
            const nextCampId = db.camps.length > 0 ? Math.max(...db.camps.map(c => c.id)) + 1 : 1;
            const newCamp = {
                id: nextCampId,
                name: body.name,
                date: body.date,
                time: body.time,
                location: body.location,
                organizer: body.organizer,
                description: body.description,
                created_by: currentUser.id,
                created_at: new Date().toISOString()
            };
            db.camps.push(newCamp);
            saveMockDB(db);
            return { status: 'success', message: 'Campaign camp published successfully.' };
        }
        if (method === 'DELETE') {
            if (!currentUser || !['admin', 'updater'].includes(currentUser.role)) return { status: 'error', message: 'Unauthorized.' };
            const campId = parseInt(searchParams.get('id'));
            const idx = db.camps.findIndex(c => c.id === campId);
            if (idx === -1) return { status: 'error', message: 'Camp not found.' };
            db.camps.splice(idx, 1);
            // Delete associated registrations
            db.camp_registrations = db.camp_registrations.filter(r => r.camp_id !== campId);
            saveMockDB(db);
            return { status: 'success', message: 'Camp deleted.' };
        }
    }

    // 4. Donations Endpoint
    if (path === 'api/donations.php') {
        if (method === 'GET') {
            if (!currentUser) return { status: 'error', message: 'Login required.' };
            const list = db.donations.filter(d => d.user_id === currentUser.id);
            return { status: 'success', data: list };
        }
        if (method === 'POST') {
            if (!currentUser || !['admin', 'updater'].includes(currentUser.role)) return { status: 'error', message: 'Unauthorized.' };
            // Find user by donor_number or ID
            let donor = db.users.find(u => u.donor_number === body.donor_id || String(u.id) === String(body.donor_id));
            if (!donor) return { status: 'error', message: 'Donor not found. Please verify the ID/Code.' };

            const bag_id = 'BAG-' + new Date(body.donation_date).toISOString().slice(0, 7).replace('-', '') + '-' + String(db.donations.length + 1).padStart(4, '0');
            
            db.donations.unshift({
                id: db.donations.length + 1,
                user_id: donor.id,
                blood_type: body.blood_type,
                volume_ml: parseInt(body.volume_ml),
                location: body.location,
                hemoglobin: body.hemoglobin,
                blood_pressure: body.blood_pressure,
                weight: body.weight,
                donation_date: body.donation_date
            });

            // Update physical inventory counts
            const prop = body.blood_type.replace('+', 'Pos').replace('-', 'Neg');
            if (db.blood_inventory[prop] !== undefined) {
                db.blood_inventory[prop]++;
            }

            db.audit_log.unshift({ id: db.audit_log.length + 1, user_id: currentUser.id, action: 'Log Donation', details: `Recorded blood pack ${bag_id} (${body.blood_type}) from donor ${donor.fullName} (ID: ${donor.id}).`, timestamp: new Date().toISOString() });
            saveMockDB(db);

            return { status: 'success', message: 'Donation logged successfully.', data: { bag_id } };
        }
    }

    // 5. Inventory Endpoint
    if (path === 'api/inventory.php') {
        if (method === 'GET') {
            return { status: 'success', data: db.blood_inventory };
        }
        if (method === 'POST') {
            if (!currentUser || !['admin', 'updater'].includes(currentUser.role)) return { status: 'error', message: 'Unauthorized.' };
            db.blood_inventory = {
                oPos: parseInt(body.oPos),
                aPos: parseInt(body.aPos),
                bPos: parseInt(body.bPos),
                abPos: parseInt(body.abPos),
                oNeg: parseInt(body.oNeg),
                aNeg: parseInt(body.aNeg),
                bNeg: parseInt(body.bNeg),
                abNeg: parseInt(body.abNeg),
                platelets: parseInt(body.platelets)
            };
            db.audit_log.unshift({ id: db.audit_log.length + 1, user_id: currentUser.id, action: 'Update Inventory', details: 'Physical count adjusted manually.', timestamp: new Date().toISOString() });
            saveMockDB(db);
            return { status: 'success', message: 'Physical reserves adjusted.' };
        }
    }

    // 6. Analytics Endpoint
    if (path === 'api/analytics.php') {
        const totalBags = db.donations.length + 1350; // Add baseline mock number
        const forecast = {
            'O+': { month1: 150 }, 'A+': { month1: 120 }, 'B+': { month1: 90 }, 'AB+': { month1: 30 },
            'O-': { month1: 20 }, 'A-': { month1: 10 }, 'B-': { month1: 8 }, 'AB-': { month1: 4 }
        };
        const alerts = [];
        const types = ['O+', 'A+', 'B+', 'AB+', 'O-', 'A-', 'B-', 'AB-'];
        
        types.forEach(t => {
            const prop = t.replace('+', 'Pos').replace('-', 'Neg');
            const available = db.blood_inventory[prop];
            const predicted_demand = forecast[t].month1;
            if (available < predicted_demand) {
                alerts.push({
                    blood_type: t,
                    available,
                    predicted_demand,
                    deficit: predicted_demand - available,
                    severity: (predicted_demand - available) / predicted_demand
                });
            }
        });

        return {
            status: 'success',
            data: {
                total_bags: totalBags,
                forecast,
                alerts
            }
        };
    }

    return { status: 'error', message: 'Endpoint not found.' };
}
