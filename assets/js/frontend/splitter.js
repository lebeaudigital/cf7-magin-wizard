/**
 * splitter.js
 *
 * Découpe le contenu du formulaire CF7 en plusieurs panneaux d'étapes
 * en se basant sur les marqueurs HTML <!--cf7mw-step-break--> injectés
 * par le tag PHP [wizard-break].
 *
 * Exporte la fonction `splitFormIntoSteps(stepsContainer, config)`.
 */

export function splitFormIntoSteps(stepsContainer, config) {
    if (!stepsContainer) return [];

    const breakMarker = config.breakMarker || '<!--cf7mw-step-break-->';
    const html = stepsContainer.innerHTML;

    // Si le marqueur HTML est absent, on tente une détection alternative
    // basée sur la présence de noeuds commentaires.
    let parts = [];
    if (html.includes(breakMarker)) {
        parts = html.split(breakMarker);
    } else {
        parts = splitByCommentNodes(stepsContainer);
    }

    if (parts.length <= 1) {
        return [];
    }

    // On vide le container puis on reconstruit en wrappant chaque morceau.
    stepsContainer.innerHTML = '';

    const stepElements = [];

    parts.forEach((partHtml, index) => {
        const stepEl = document.createElement('div');
        stepEl.className = 'cf7mw-step' + (index === 0 ? ' is-active' : '');
        stepEl.dataset.cf7mwStep = String(index);
        stepEl.setAttribute('aria-hidden', index === 0 ? 'false' : 'true');
        stepEl.innerHTML = partHtml;
        stepsContainer.appendChild(stepEl);
        stepElements.push(stepEl);
    });

    return stepElements;
}

/**
 * Fallback : si le marqueur n'est pas trouvé textuellement (parfois retiré
 * par des filtres tiers), on cherche les noeuds commentaire.
 */
function splitByCommentNodes(container) {
    const walker = document.createTreeWalker(container, NodeFilter.SHOW_COMMENT);
    const breaks = [];
    let node = walker.nextNode();
    while (node) {
        if (node.nodeValue && node.nodeValue.includes('cf7mw-step-break')) {
            breaks.push(node);
        }
        node = walker.nextNode();
    }

    if (breaks.length === 0) return [container.innerHTML];

    // Pas trivial de re-splitter par DOM ; on convertit en HTML avec un
    // marqueur unique puis on split.
    const sentinel = '<!--cf7mw-step-break-->';
    breaks.forEach(b => {
        b.nodeValue = 'cf7mw-step-break';
    });
    return container.innerHTML.split(sentinel);
}
