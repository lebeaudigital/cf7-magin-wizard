/**
 * navigation.js
 *
 * Construit la barre de navigation (Précédent / Suivant) injectée au pied
 * de chaque étape (sauf la dernière qui conserve le bouton submit CF7).
 *
 * Expose :
 *   - injectNavigation(stepEls, config, callbacks)
 *   - showStep(stepEls, targetIndex)
 */

const NEXT_CLASS    = 'cf7mw-btn cf7mw-btn--next';
const PREV_CLASS    = 'cf7mw-btn cf7mw-btn--prev';
const NAV_CLASS     = 'cf7mw-step__nav';
const SPACER_CLASS  = 'cf7mw-step__nav-spacer';

/**
 * Ajoute la barre de navigation à chaque étape.
 *
 * @param {HTMLElement[]} stepEls
 * @param {object} config
 * @param {{ onNext: (i:number)=>void, onPrev: (i:number)=>void }} callbacks
 */
export function injectNavigation(stepEls, config, callbacks) {
    const total = stepEls.length;
    const steps = Array.isArray(config.steps) ? config.steps : [];
    const labelPrev = config.labelPrevious || 'Précédent';

    stepEls.forEach((stepEl, index) => {
        const isLast = (index === total - 1);

        // S'il existe déjà des boutons CF7 dans cette étape mais pas la dernière,
        // on les déplace vers la dernière étape (le submit doit rester à la fin).
        if (!isLast) {
            const submits = stepEl.querySelectorAll('.wpcf7-submit, input[type="submit"]');
            submits.forEach(s => {
                // On masque le submit s'il est dans une étape intermédiaire.
                s.style.display = 'none';
                s.setAttribute('data-cf7mw-hidden-submit', '1');
            });
        }

        const nav = document.createElement('div');
        nav.className = NAV_CLASS;

        // Bouton Précédent (sauf à la première étape)
        if (index > 0) {
            const prevBtn = document.createElement('button');
            prevBtn.type = 'button';
            prevBtn.className = PREV_CLASS;
            prevBtn.textContent = labelPrev;
            prevBtn.addEventListener('click', () => callbacks.onPrev(index));
            nav.appendChild(prevBtn);
        }

        // Spacer pour pousser le bouton Suivant à droite
        const spacer = document.createElement('span');
        spacer.className = SPACER_CLASS;
        nav.appendChild(spacer);

        if (!isLast) {
            // Bouton Suivant
            const nextBtn = document.createElement('button');
            nextBtn.type = 'button';
            nextBtn.className = NEXT_CLASS;
            const stepCfg = steps[index] || {};
            nextBtn.textContent = stepCfg.button || 'Suivant';
            nextBtn.addEventListener('click', () => callbacks.onNext(index));
            nav.appendChild(nextBtn);
        }
        // Sinon : la dernière étape conserve son [submit] CF7 natif

        stepEl.appendChild(nav);
    });
}

/**
 * Affiche l'étape demandée et masque les autres.
 *
 * @param {HTMLElement[]} stepEls
 * @param {number} targetIndex
 */
export function showStep(stepEls, targetIndex) {
    stepEls.forEach((stepEl, index) => {
        const isActive = (index === targetIndex);
        stepEl.classList.toggle('is-active', isActive);
        stepEl.setAttribute('aria-hidden', isActive ? 'false' : 'true');
    });
}

/**
 * Fait défiler la page vers le haut du wrapper.
 */
export function scrollToWrapperTop(wrapperEl) {
    if (!wrapperEl) return;
    const rect = wrapperEl.getBoundingClientRect();
    const top  = window.scrollY + rect.top - 24;
    window.scrollTo({ top: Math.max(0, top), behavior: 'smooth' });
}
