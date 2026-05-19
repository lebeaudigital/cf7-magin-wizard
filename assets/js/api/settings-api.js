/**
 * settings-api.js
 *
 * Appels REST pour la configuration globale du plugin (couleurs, libellés, options).
 *
 *   GET    cf7mw/v1/settings
 *   PUT    cf7mw/v1/settings
 *   POST   cf7mw/v1/settings/reset
 */

import { http } from './http.js';

export function getSettings() {
    return http.get('settings');
}

export function saveSettings(payload) {
    return http.put('settings', payload);
}

export function resetSettings() {
    return http.post('settings/reset');
}
