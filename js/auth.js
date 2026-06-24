// JWT Authentication Helper and Session Utilities for LifeLine

// Base path detection helper
function getBasePath() {
    const path = window.location.pathname;
    // Returns directory path (ends with '/')
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
        // Clear anything corrupt
        localStorage.removeItem('ll_token');
        localStorage.removeItem('ll_user');
        window.location.href = basePath + 'index.php';
        return;
    }

    const payload = parseJWT(token);
    if (!payload || (payload.exp && payload.exp < Date.now() / 1000)) {
        // Expired or invalid token
        localStorage.removeItem('ll_token');
        localStorage.removeItem('ll_user');
        window.location.href = basePath + 'index.php';
        return;
    }

    const user = JSON.parse(userJson);
    if (allowedRoles.length > 0) {
        const roleLevels = {
            'superadmin': 4,
            'admin': 3,
            'updater': 2,
            'donor': 1,
            'revoked': 0
        };
        const userLevel = roleLevels[user.role] ?? 0;
        let isAllowed = false;
        for (const allowedRole of allowedRoles) {
            const allowedLevel = roleLevels[allowedRole] ?? 999;
            if (userLevel >= allowedLevel) {
                isAllowed = true;
                break;
            }
        }
        if (!isAllowed) {
            window.location.href = basePath + 'home.php';
            return;
        }
    }

    return { token, user };
}

// Logout session
function logout() {
    localStorage.removeItem('ll_token');
    localStorage.removeItem('ll_user');
    window.location.href = getBasePath() + 'index.php';
}

// Utility to verify headers are attached
function getAuthHeader() {
    const token = localStorage.getItem('ll_token');
    if (!token) return {};
    return { 'Authorization': 'Bearer ' + token };
}

// Custom Fetch Wrapper supporting auto-token insertion & relative base routing
async function apiFetch(endpoint, options = {}) {
    const headers = getAuthHeader();
    
    options.headers = {
        ...headers,
        'Content-Type': 'application/json',
        ...(options.headers || {})
    };

    // Ensure relative paths from the current base folder are used
    const basePath = getBasePath();
    
    // Strips leading slash if present to maintain relative paths
    const cleanEndpoint = endpoint.startsWith('/') ? endpoint.substring(1) : endpoint;
    const url = basePath + cleanEndpoint;

    try {
        const response = await fetch(url, options);
        
        // Handle token expiration / 401 Unauthorized globally
        if (response.status === 401) {
            localStorage.removeItem('ll_token');
            localStorage.removeItem('ll_user');
            window.location.href = basePath + 'index.php';
            throw new Error('Unauthorized session. Please log in again.');
        }

        const result = await response.json();
        
        if (!response.ok) {
            throw new Error(result.message || 'API request failed.');
        }
        
        return result;
    } catch (error) {
        console.error('Fetch Error:', error);
        throw error;
    }
}
