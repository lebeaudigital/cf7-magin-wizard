/**
 * steps-manager.js
 *
 * Gère la liste d'étapes dans le panneau « Magic Wizard » de l'éditeur CF7.
 * Permet d'ajouter, supprimer et réordonner des étapes (drag & drop simple),
 * et de renommer dynamiquement leur numéro.
 *
 * Module ES6 (chargé via <script type="module">).
 */

const ROOT_SELECTOR    = '[data-cf7mw-steps-root]';
const LIST_SELECTOR    = '[data-cf7mw-steps-list]';
const ADD_BTN_SELECTOR = '[data-cf7mw-add-step]';
const ROW_SELECTOR     = '.cf7mw-step-row';

const i18n = (window.CF7MW_AdminData && window.CF7MW_AdminData.i18n) || {};

function buildStepRow(index, data = {}) {
    const row = document.createElement('li');
    row.className = 'cf7mw-step-row';
    row.draggable = true;

    const defaultName   = (i18n.defaultStep || 'Étape') + ' ' + (index + 1);
    const defaultButton = i18n.defaultNext || 'Suivant';

    const phName     = i18n.stepName     || "Nom de l'étape";
    const phSubtitle = i18n.stepSubtitle || 'Sous-titre (facultatif)';
    const phButton   = i18n.buttonLabel  || 'Libellé du bouton';
    const phRemove   = i18n.removeStep   || 'Supprimer';

    row.innerHTML = `
        <span class="cf7mw-step-row__handle" aria-hidden="true">≡</span>
        <input type="text" name="cf7mw[steps][${index}][name]"
               value="${escapeHtml(data.name ?? defaultName)}"
               placeholder="${escapeHtml(phName)}">
        <input type="text" name="cf7mw[steps][${index}][subtitle]"
               value="${escapeHtml(data.subtitle ?? '')}"
               placeholder="${escapeHtml(phSubtitle)}">
        <input type="text" name="cf7mw[steps][${index}][button]"
               value="${escapeHtml(data.button ?? defaultButton)}"
               placeholder="${escapeHtml(phButton)}">
        <button type="button" class="cf7mw-step-row__remove">${escapeHtml(phRemove)}</button>
    `;
    return row;
}

function escapeHtml(str) {
    return String(str)
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

function reindexRows(listEl) {
    const rows = listEl.querySelectorAll(ROW_SELECTOR);
    rows.forEach((row, index) => {
        row.querySelectorAll('input[name^="cf7mw[steps]"]').forEach(input => {
            input.name = input.name.replace(/cf7mw\[steps]\[\d+]/, `cf7mw[steps][${index}]`);
        });
    });
}

function attachRemove(listEl, row) {
    const btn = row.querySelector('.cf7mw-step-row__remove');
    btn?.addEventListener('click', () => {
        row.remove();
        reindexRows(listEl);
    });
}

/**
 * Drag & drop natif HTML5 (sans dépendance).
 */
function attachDragAndDrop(listEl) {
    let draggedRow = null;

    listEl.addEventListener('dragstart', (event) => {
        const row = event.target.closest(ROW_SELECTOR);
        if (!row) return;
        draggedRow = row;
        row.style.opacity = '0.4';
        event.dataTransfer.effectAllowed = 'move';
    });

    listEl.addEventListener('dragend', () => {
        if (draggedRow) draggedRow.style.opacity = '';
        draggedRow = null;
        reindexRows(listEl);
    });

    listEl.addEventListener('dragover', (event) => {
        event.preventDefault();
        const target = event.target.closest(ROW_SELECTOR);
        if (!target || target === draggedRow) return;
        const rect = target.getBoundingClientRect();
        const after = (event.clientY - rect.top) > rect.height / 2;
        if (after) {
            target.parentNode.insertBefore(draggedRow, target.nextSibling);
        } else {
            target.parentNode.insertBefore(draggedRow, target);
        }
    });
}

function initRoot(root) {
    const listEl  = root.querySelector(LIST_SELECTOR);
    const addBtn  = root.querySelector(ADD_BTN_SELECTOR);
    if (!listEl || !addBtn) return;

    listEl.querySelectorAll(ROW_SELECTOR).forEach(row => attachRemove(listEl, row));
    attachDragAndDrop(listEl);

    addBtn.addEventListener('click', () => {
        const nextIndex = listEl.querySelectorAll(ROW_SELECTOR).length;
        const row       = buildStepRow(nextIndex);
        listEl.appendChild(row);
        attachRemove(listEl, row);
        row.querySelector('input[type="text"]')?.focus();
    });
}

function init() {
    document.querySelectorAll(ROOT_SELECTOR).forEach(initRoot);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
} else {
    init();
}
