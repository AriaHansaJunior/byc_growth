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

    const json = await response.json();
    if (!response.ok) {
        throw new Error(json.error || 'Terjadi kesalahan saat memproses data.');
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

    const json = await response.json();
    if (!response.ok) {
        throw new Error(json.error || 'Terjadi kesalahan saat memproses data.');
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

    const json = await response.json();
    if (!response.ok) {
        throw new Error(json.error || 'Terjadi kesalahan saat menghapus data.');
    }
    return json;
}
