/**
 * settings.js
 *
 * Comportement de la page de réglages globaux (Contact > Magic Wizard).
 * Sauvegarde asynchrone via l'API REST + bouton « Réinitialiser ».
 *
 * Module ES6.
 */

import { saveSettings, resetSettings } from '../api/settings-api.js';

const FORM_SELECTOR   = '[data-cf7mw-settings-form]';
const SAVE_SELECTOR   = '[data-cf7mw-save]';
const RESET_SELECTOR  = '[data-cf7mw-reset]';
const NOTICE_SELECTOR = '[data-cf7mw-notice]';

const i18n = (window.CF7MW_AdminData && window.CF7MW_AdminData.i18n) || {};

function showNotice(noticeEl, message, type = 'success') {
    if (!noticeEl) return;
    noticeEl.className = 'cf7mw-notice cf7mw-notice--' + type;
    noticeEl.textContent = message;
    noticeEl.style.display = 'block';
    clearTimeout(noticeEl._cf7mwTimer);
    noticeEl._cf7mwTimer = setTimeout(() => {
        noticeEl.style.display = 'none';
    }, 4000);
}

function serializeForm(formEl) {
    const data = {};
    new FormData(formEl).forEach((value, key) => {
        data[key] = value;
    });

    // Forcer la présence des cases à cocher non cochées (0).
    formEl.querySelectorAll('input[type="checkbox"]').forEach(cb => {
        if (!cb.checked) data[cb.name] = 0;
        else data[cb.name] = 1;
    });

    return data;
}

function init() {
    const form = document.querySelector(FORM_SELECTOR);
    if (!form) return;

    const noticeEl = form.querySelector(NOTICE_SELECTOR);

    form.querySelector(SAVE_SELECTOR)?.addEventListener('click', async (event) => {
        event.preventDefault();
        const data = serializeForm(form);
        try {
            await saveSettings(data);
            showNotice(noticeEl, i18n.saved || 'Réglages enregistrés', 'success');
        } catch (err) {
            showNotice(noticeEl, (i18n.error || 'Erreur') + ' : ' + err.message, 'error');
        }
    });

    form.querySelector(RESET_SELECTOR)?.addEventListener('click', async (event) => {
        event.preventDefault();
        if (!window.confirm('Réinitialiser tous les réglages aux valeurs par défaut ?')) return;
        try {
            await resetSettings();
            window.location.reload();
        } catch (err) {
            showNotice(noticeEl, (i18n.error || 'Erreur') + ' : ' + err.message, 'error');
        }
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
} else {
    init();
}
