/**
 * wizard.js
 *
 * Point d'entrée frontend du plugin CF7 Magic Wizard (module ES6).
 * Initialise un wizard sur chaque .cf7mw-wrapper trouvé dans la page :
 *  - lit la configuration JSON inline
 *  - découpe le formulaire en étapes
 *  - injecte la navigation
 *  - synchronise indicateur + barre de progression
 *  - gère le retour à l'étape 1 après envoi avec succès
 */

import { splitFormIntoSteps } from './splitter.js';
import { injectNavigation, showStep, scrollToWrapperTop } from './navigation.js';
import { validateStep, clearValidation } from './validation.js';
import { updateIndicator, attachIndicatorClicks } from './indicator.js';
import { updateProgress } from './progress.js';

class CF7MagicWizard {
    constructor(wrapperEl) {
        this.wrapper      = wrapperEl;
        this.config       = this.readConfig();
        this.currentIndex = 0;

        this.stepsContainer = wrapperEl.querySelector('[data-cf7mw-steps]');
        this.indicator      = wrapperEl.querySelector('[data-cf7mw-indicator]');
        this.progress       = wrapperEl.querySelector('[data-cf7mw-progress]');
        this.formEl         = wrapperEl.querySelector('form.wpcf7-form');

        this.steps = splitFormIntoSteps(this.stepsContainer, this.config);
        if (this.steps.length < 2) {
            // Aucun découpage : on désactive le wizard et on laisse CF7 fonctionner normalement.
            return;
        }

        injectNavigation(this.steps, this.config, {
            onNext: (i) => this.goToNext(i),
            onPrev: (i) => this.goToPrev(i),
        });

        attachIndicatorClicks(this.indicator, (i) => this.goTo(i));
        this.bindCF7Events();

        this.render();
    }

    readConfig() {
        const node = this.wrapper.querySelector('script.cf7mw-config');
        if (!node) return { steps: [] };
        try {
            return JSON.parse(node.textContent || '{}');
        } catch (e) {
            console.warn('[CF7 Magic Wizard] Configuration invalide', e);
            return { steps: [] };
        }
    }

    bindCF7Events() {
        if (!this.formEl) return;

        // Empêcher la soumission accidentelle (touche Entrée) tant qu'on n'est pas à la dernière étape.
        this.formEl.addEventListener('keydown', (event) => {
            if (event.key !== 'Enter') return;
            const target = event.target;
            if (target && target.tagName === 'TEXTAREA') return;
            if (this.currentIndex < this.steps.length - 1) {
                event.preventDefault();
                this.goToNext(this.currentIndex);
            }
        });

        // Quand CF7 retourne une erreur de validation serveur sur soumission,
        // on revient automatiquement à la première étape contenant un champ invalide.
        this.formEl.addEventListener('wpcf7invalid', () => {
            this.jumpToFirstInvalidStep();
        });

        // En cas de succès, on remet le wizard à l'étape 1.
        this.formEl.addEventListener('wpcf7mailsent', () => {
            // Marquer la barre de progression à 100% un court instant.
            updateProgress(this.progress, this.steps.length - 1, this.steps.length, { submitted: true });
            setTimeout(() => this.goTo(0), 600);
        });
    }

    goToNext(fromIndex) {
        const stepEl = this.steps[fromIndex];

        if (this.config.enableValidation && !validateStep(stepEl)) {
            return;
        }
        const target = Math.min(fromIndex + 1, this.steps.length - 1);
        this.goTo(target);
    }

    goToPrev(fromIndex) {
        clearValidation(this.steps[fromIndex]);
        const target = Math.max(0, fromIndex - 1);
        this.goTo(target);
    }

    goTo(targetIndex) {
        if (targetIndex === this.currentIndex) return;
        if (targetIndex < 0 || targetIndex >= this.steps.length) return;

        this.currentIndex = targetIndex;
        this.render();

        if (this.config.scrollToTopOnStep) {
            scrollToWrapperTop(this.wrapper);
        }
    }

    render() {
        showStep(this.steps, this.currentIndex);
        updateIndicator(this.indicator, this.currentIndex, this.steps.length);
        updateProgress(this.progress, this.currentIndex, this.steps.length);
    }

    jumpToFirstInvalidStep() {
        for (let i = 0; i < this.steps.length; i++) {
            const invalid = this.steps[i].querySelector('.wpcf7-not-valid');
            if (invalid) {
                this.goTo(i);
                return;
            }
        }
    }
}

function init() {
    const wrappers = document.querySelectorAll('.cf7mw-wrapper');
    wrappers.forEach(wrapper => {
        if (wrapper.dataset.cf7mwInitialized === '1') return;
        wrapper.dataset.cf7mwInitialized = '1';
        new CF7MagicWizard(wrapper);
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
} else {
    init();
}
