/**
 * validation.js
 *
 * Validation des champs CF7 d'une étape avant d'autoriser la navigation
 * vers l'étape suivante.
 *
 * Particularité CF7 : les champs marqués obligatoires avec l'étoile
 * ([text* …], [email* …], [tel* …]…) ne reçoivent PAS l'attribut HTML5
 * `required`. CF7 ajoute uniquement `aria-required="true"` et la classe
 * `wpcf7-validates-as-required`. On doit donc détecter ces marqueurs
 * pour bloquer le passage à l'étape suivante quand un champ requis est vide.
 */

const INVALID_CLASS = 'wpcf7-not-valid';
const PROCESSED_NAMES_KEY = '__cf7mwProcessed';

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
    const processedNames = new Set();

    fields.forEach(field => {
        if (field.disabled || field.closest('.cf7mw-is-hidden')) return;
        const type = (field.type || '').toLowerCase();
        if (type === 'submit' || type === 'button' || type === 'reset' || type === 'hidden') return;

        // Pour les groupes (checkbox/radio), on ne traite chaque nom qu'une fois.
        const isGroupable = (type === 'checkbox' || type === 'radio');
        if (isGroupable && field.name) {
            if (processedNames.has(field.name)) return;
            processedNames.add(field.name);
        }

        const result = validateField(field, isGroupable);
        applyValidationState(field, result, isGroupable);

        if (!result.valid) {
            isValid = false;
            if (!firstInvalid) {
                firstInvalid = field;
            }
        }
    });

    if (firstInvalid && typeof firstInvalid.focus === 'function') {
        firstInvalid.focus({ preventScroll: false });
    }

    return isValid;
}

/**
 * Branche un listener live qui revalide automatiquement un champ dès que
 * l'utilisateur le modifie (uniquement après que le champ a déjà été marqué
 * invalide une première fois, pour ne pas être intrusif).
 */
export function attachLiveRevalidation(stepEl) {
    if (!stepEl || stepEl.dataset.cf7mwLiveBound === '1') return;
    stepEl.dataset.cf7mwLiveBound = '1';

    const handler = (event) => {
        const field = event.target;
        if (!field || !(field.matches && field.matches('input, select, textarea'))) return;
        if (!field.classList.contains(INVALID_CLASS)) return;

        const type = (field.type || '').toLowerCase();
        const isGroupable = (type === 'checkbox' || type === 'radio');
        const result = validateField(field, isGroupable);
        applyValidationState(field, result, isGroupable);
    };

    stepEl.addEventListener('input',  handler);
    stepEl.addEventListener('change', handler);
}

/**
 * Nettoie les marqueurs d'erreur quand on revient en arrière sur une étape.
 */
export function clearValidation(stepEl) {
    if (!stepEl) return;
    stepEl.querySelectorAll('.' + INVALID_CLASS).forEach(el => {
        el.classList.remove(INVALID_CLASS);
        el.removeAttribute('aria-invalid');
    });
    stepEl.querySelectorAll('.cf7mw-field-error').forEach(el => el.remove());
}

/* -----------------------------------------------------------------
 * Détection des règles CF7
 * ----------------------------------------------------------------- */

function isCF7Required(field) {
    if (field.required) return true;
    if (field.getAttribute && field.getAttribute('aria-required') === 'true') return true;
    if (field.classList && field.classList.contains('wpcf7-validates-as-required')) return true;
    if (field.classList && field.classList.contains('wpcf7-validates-as-requiredfile')) return true;
    return false;
}

function groupHasRequired(field) {
    if (!field.form || !field.name) return isCF7Required(field);
    const same = field.form.querySelectorAll(`input[name="${cssEscape(field.name)}"]`);
    for (const el of same) {
        if (isCF7Required(el)) return true;
    }
    return false;
}

function isFieldEmpty(field) {
    const type = (field.type || '').toLowerCase();
    if (type === 'checkbox' || type === 'radio') {
        if (field.form && field.name) {
            const checked = field.form.querySelectorAll(`input[name="${cssEscape(field.name)}"]:checked`);
            return checked.length === 0;
        }
        return !field.checked;
    }
    if (type === 'file') {
        return !field.files || field.files.length === 0;
    }
    return String(field.value || '').trim() === '';
}

function validateField(field, isGroupable) {
    const required = isGroupable ? groupHasRequired(field) : isCF7Required(field);
    const empty    = isFieldEmpty(field);

    if (required && empty) {
        return { valid: false, message: messageRequired() };
    }

    // Champ optionnel vide : OK
    if (empty) {
        return { valid: true };
    }

    // Champ rempli : on vérifie le format natif HTML5 (email, url, number, pattern…)
    if (typeof field.checkValidity === 'function' && !field.checkValidity()) {
        return {
            valid: false,
            message: field.validationMessage || messageInvalid(),
        };
    }

    return { valid: true };
}

/* -----------------------------------------------------------------
 * Rendu visuel des erreurs
 * ----------------------------------------------------------------- */

function applyValidationState(field, result, isGroupable) {
    if (result.valid) {
        markValid(field, isGroupable);
        return;
    }
    markInvalid(field, result.message, isGroupable);
}

function markValid(field, isGroupable) {
    const targets = isGroupable ? getGroupInputs(field) : [field];
    targets.forEach(t => {
        t.classList.remove(INVALID_CLASS);
        t.removeAttribute('aria-invalid');
    });
    hideFieldError(field);
}

function markInvalid(field, message, isGroupable) {
    const targets = isGroupable ? getGroupInputs(field) : [field];
    targets.forEach(t => {
        t.classList.add(INVALID_CLASS);
        t.setAttribute('aria-invalid', 'true');
    });
    showFieldError(field, message);
}

function getGroupInputs(field) {
    if (!field.form || !field.name) return [field];
    return Array.from(field.form.querySelectorAll(`input[name="${cssEscape(field.name)}"]`));
}

function showFieldError(field, message) {
    const wrap = field.closest('.wpcf7-form-control-wrap')
              || field.closest('.wpcf7-form-control')
              || field.parentElement;
    if (!wrap) return;
    let errorEl = wrap.querySelector('.cf7mw-field-error');
    if (!errorEl) {
        errorEl = document.createElement('span');
        errorEl.className = 'cf7mw-field-error';
        wrap.appendChild(errorEl);
    }
    errorEl.textContent = message || '';
}

function hideFieldError(field) {
    const wrap = field.closest('.wpcf7-form-control-wrap')
              || field.closest('.wpcf7-form-control')
              || field.parentElement;
    if (!wrap) return;
    const errorEl = wrap.querySelector('.cf7mw-field-error');
    if (errorEl) errorEl.remove();
}

/* -----------------------------------------------------------------
 * Messages (localisés simplement)
 * ----------------------------------------------------------------- */

function isFrench() {
    return (document.documentElement.lang || '').toLowerCase().startsWith('fr');
}

function messageRequired() {
    return isFrench() ? 'Veuillez remplir ce champ.' : 'Please fill out this field.';
}

function messageInvalid() {
    return isFrench() ? 'Format invalide.' : 'Invalid format.';
}

/* -----------------------------------------------------------------
 * Utilitaires
 * ----------------------------------------------------------------- */

function cssEscape(value) {
    if (typeof CSS !== 'undefined' && typeof CSS.escape === 'function') {
        return CSS.escape(value);
    }
    return String(value).replace(/(["\\\]])/g, '\\$1');
}
