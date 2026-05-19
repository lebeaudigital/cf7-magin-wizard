/**
 * progress.js
 *
 * Met à jour la barre de progression placée sous le formulaire :
 * largeur de la barre + texte du pourcentage + attributs ARIA.
 *
 * Le pourcentage représente la progression vers la fin du formulaire
 * (étape 1/3 = 33%, étape 2/3 = 66%, étape 3/3 = 100% si soumise).
 */

export function updateProgress(progressEl, currentIndex, totalSteps, { submitted = false } = {}) {
    if (!progressEl) return;

    const bar   = progressEl.querySelector('[data-cf7mw-progress-bar]');
    const label = progressEl.querySelector('[data-cf7mw-progress-label]');

    if (totalSteps <= 1) {
        if (bar)   bar.style.width = '100%';
        if (label) label.textContent = '100%';
        progressEl.setAttribute('aria-valuenow', '100');
        return;
    }

    // Progression : la dernière étape vaut (n-1)/n tant qu'elle n'est pas soumise.
    let percent;
    if (submitted) {
        percent = 100;
    } else {
        percent = Math.round((currentIndex / (totalSteps - 1)) * 100);
        // On garantit une visibilité de la première étape (>= 5%).
        if (currentIndex === 0) {
            percent = Math.max(percent, Math.round(100 / totalSteps));
        }
    }
    percent = Math.max(0, Math.min(100, percent));

    if (bar)   bar.style.width = percent + '%';
    if (label) label.textContent = percent + '%';
    progressEl.setAttribute('aria-valuenow', String(percent));
}
