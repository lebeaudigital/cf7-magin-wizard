/**
 * indicator.js
 *
 * Met à jour visuellement l'indicateur d'étapes (cercles + noms + lignes)
 * en fonction de l'étape courante : ajoute les classes is-active / is-done
 * sur les <li> de la liste.
 */

export function updateIndicator(indicatorEl, currentIndex, totalSteps) {
    if (!indicatorEl) return;

    const items = indicatorEl.querySelectorAll('.cf7mw-indicator__item');
    items.forEach((item, index) => {
        item.classList.remove('is-active', 'is-done');
        if (index < currentIndex) {
            item.classList.add('is-done');
        } else if (index === currentIndex) {
            item.classList.add('is-active');
        }
    });
}

/**
 * Ajoute la possibilité de cliquer sur un cercle déjà complété pour y revenir.
 *
 * @param {HTMLElement} indicatorEl
 * @param {(index:number) => void} onStepClick
 */
export function attachIndicatorClicks(indicatorEl, onStepClick) {
    if (!indicatorEl) return;
    const items = indicatorEl.querySelectorAll('.cf7mw-indicator__item');
    items.forEach((item, index) => {
        item.style.cursor = 'pointer';
        item.addEventListener('click', () => {
            // Autoriser uniquement le retour en arrière (et pas le saut en avant).
            if (item.classList.contains('is-done')) {
                onStepClick(index);
            }
        });
        item.addEventListener('keydown', (event) => {
            if ((event.key === 'Enter' || event.key === ' ') && item.classList.contains('is-done')) {
                event.preventDefault();
                onStepClick(index);
            }
        });
        item.setAttribute('tabindex', '0');
        item.setAttribute('role', 'button');
    });
}
