/**
 * forms-api.js
 *
 * Appels REST pour la configuration multi-étapes d'un formulaire CF7 donné.
 *
 *   GET   cf7mw/v1/forms/{id}/config
 *   PUT   cf7mw/v1/forms/{id}/config
 */

import { http } from './http.js';

export function getFormConfig(formId) {
    return http.get(`forms/${encodeURIComponent(formId)}/config`);
}

export function saveFormConfig(formId, payload) {
    return http.put(`forms/${encodeURIComponent(formId)}/config`, payload);
}
