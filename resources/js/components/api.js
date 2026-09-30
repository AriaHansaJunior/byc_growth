/**
 * API Helper for CSRF protected AJAX requests
 */

export function getCsrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') : '';
}

export async function postJson(url, data) {
    const response = await fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': getCsrfToken(),
            'Accept': 'application/json',
        },
        body: JSON.stringify(data),
    });

    const json = await response.json().catch(() => ({}));
    if (!response.ok) {
        if (response.status === 401) {
            throw new Error('You must login as an admin account to input score.');
        }
        if (response.status === 403) {
            throw new Error('Access denied: You must login as an admin account.');
        }
        if (response.status === 419) {
            throw new Error('Security session expired. Please refresh the page.');
        }
        throw new Error(json.error || json.message || 'An error occurred while processing the request.');
    }
    return json;
}

export async function postFormData(url, formData) {
    const response = await fetch(url, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': getCsrfToken(),
            'Accept': 'application/json',
        },
        body: formData,
    });

    const json = await response.json().catch(() => ({}));
    if (!response.ok) {
        if (response.status === 401) {
            throw new Error('You must login as an admin account to input score.');
        }
        if (response.status === 403) {
            throw new Error('Access denied: You must login as an admin account.');
        }
        if (response.status === 419) {
            throw new Error('Security session expired. Please refresh the page.');
        }
        throw new Error(json.error || json.message || 'An error occurred while processing the request.');
    }
    return json;
}

export async function deleteJson(url) {
    const response = await fetch(url, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': getCsrfToken(),
            'Accept': 'application/json',
        },
    });

    const json = await response.json().catch(() => ({}));
    if (!response.ok) {
        if (response.status === 401) {
            throw new Error('You must login as an admin account to input score.');
        }
        if (response.status === 403) {
            throw new Error('Access denied: You must login as an admin account.');
        }
        if (response.status === 419) {
            throw new Error('Security session expired. Please refresh the page.');
        }
        throw new Error(json.error || json.message || 'An error occurred while deleting data.');
    }
    return json;
}
