/**
 * validation.js
 *
 * Validation HTML5 des champs CF7 d'une étape avant d'autoriser la
 * navigation vers l'étape suivante. Marque visuellement les champs invalides.
 */

const INVALID_CLASS = 'wpcf7-not-valid';

/**
 * Valide tous les champs d'un panneau d'étape.
 *
 * @param {HTMLElement} stepEl - L'élément .cf7mw-step à valider.
 * @returns {boolean} true si tous les champs sont valides.
 */
export function validateStep(stepEl) {
    if (!stepEl) return true;

    const fields = stepEl.querySelectorAll('input, select, textarea');
    let isValid = true;
    let firstInvalid = null;

    fields.forEach(field => {
        // On ignore les champs cachés ou désactivés.
        if (field.disabled || field.type === 'hidden') return;
        if (field.type === 'submit' || field.type === 'button') return;

        // checkValidity() applique les contraintes HTML5 (required, pattern, etc.)
        const fieldValid = field.checkValidity();

        if (!fieldValid) {
            isValid = false;
            field.classList.add(INVALID_CLASS);
            field.setAttribute('aria-invalid', 'true');
            showFieldError(field);
            if (!firstInvalid) {
                firstInvalid = field;
            }
        } else {
            field.classList.remove(INVALID_CLASS);
            field.removeAttribute('aria-invalid');
            hideFieldError(field);
        }
    });

    if (firstInvalid) {
        firstInvalid.focus({ preventScroll: false });
    }

    return isValid;
}

function showFieldError(field) {
    const wrap = field.closest('.wpcf7-form-control-wrap') || field.parentElement;
    if (!wrap) return;
    let errorEl = wrap.querySelector('.cf7mw-field-error');
    if (!errorEl) {
        errorEl = document.createElement('span');
        errorEl.className = 'cf7mw-field-error';
        wrap.appendChild(errorEl);
    }
    errorEl.textContent = field.validationMessage || '';
}

function hideFieldError(field) {
    const wrap = field.closest('.wpcf7-form-control-wrap') || field.parentElement;
    if (!wrap) return;
    const errorEl = wrap.querySelector('.cf7mw-field-error');
    if (errorEl) errorEl.remove();
}

/**
 * Nettoie les marqueurs d'erreur quand on revient en arrière.
 */
export function clearValidation(stepEl) {
    if (!stepEl) return;
    stepEl.querySelectorAll('.' + INVALID_CLASS).forEach(el => {
        el.classList.remove(INVALID_CLASS);
        el.removeAttribute('aria-invalid');
    });
    stepEl.querySelectorAll('.cf7mw-field-error').forEach(el => el.remove());
}
