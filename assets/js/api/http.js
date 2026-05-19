/**
 * http.js
 *
 * Petit wrapper autour de fetch() qui ajoute automatiquement les en-têtes
 * d'authentification REST WordPress (X-WP-Nonce).
 *
 * Toutes les requêtes vers l'API REST du plugin passent par ce module.
 */

const adminData = window.CF7MW_AdminData || {};

function buildUrl(path) {
    const base = adminData.restUrl || '/wp-json/cf7mw/v1/';
    if (path.startsWith('http')) return path;
    return base.replace(/\/$/, '/') + path.replace(/^\//, '');
}

async function request(path, options = {}) {
    const url = buildUrl(path);
    const headers = Object.assign(
        {
            'Content-Type': 'application/json',
            'X-WP-Nonce': adminData.nonce || '',
        },
        options.headers || {}
    );

    const response = await fetch(url, {
        ...options,
        headers,
        credentials: 'same-origin',
    });

    if (!response.ok) {
        let message = response.statusText || 'HTTP ' + response.status;
        try {
            const data = await response.json();
            if (data?.message) message = data.message;
        } catch (e) { /* noop */ }
        throw new Error(message);
    }

    if (response.status === 204) return null;
    return response.json();
}

export const http = {
    get:    (path)        => request(path, { method: 'GET' }),
    post:   (path, body)  => request(path, { method: 'POST',   body: JSON.stringify(body || {}) }),
    put:    (path, body)  => request(path, { method: 'PUT',    body: JSON.stringify(body || {}) }),
    del:    (path)        => request(path, { method: 'DELETE' }),
};
