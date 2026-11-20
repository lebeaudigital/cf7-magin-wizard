/**
 * conditions.js
 *
 * Applique les règles d'affichage sur le formulaire public.
 * Un champ « afficher » reste masqué tant qu'aucune de ses conditions n'est vraie.
 * Un champ « masquer » disparaît dès qu'une de ses conditions est vraie.
 * Les champs masqués sont désactivés : ils ne partent pas avec l'envoi
 * et ne bloquent pas le passage à l'étape suivante.
 */

function readRules(form) {
    const node = form.querySelector('script.cf7mw-conditions');
    if (!node) return [];
    try {
        const rules = JSON.parse(node.textContent || '[]');
        return Array.isArray(rules) ? rules : [];
    } catch (error) {
        return [];
    }
}

function fieldValue(form, name) {
    const named = form.querySelectorAll(`[name="${cssEscape(name)}"], [name="${cssEscape(name)}[]"]`);
    if (!named.length) return '';
    const first = named[0];
    const type = (first.type || '').toLowerCase();
    if (type === 'checkbox' || type === 'radio') {
        return [...named].filter(field => field.checked && !field.disabled).map(field => field.value.trim());
    }
    if (first.disabled) return '';
    return String(first.value || '').trim();
}

function matches(rule, form) {
    const current = fieldValue(form, rule.source);
    const values = Array.isArray(current) ? current : [current];
    const value = Array.isArray(current) ? current.join(' ') : current;
    const expected = String(rule.value || '').trim();
    const equals = () => (expected === '' ? value === '' : values.includes(expected));

    switch (rule.operator) {
        case 'equals':
            return equals();
        case 'not_equals':
            return !equals();
        case 'contains':
            return expected !== '' && value.toLowerCase().includes(expected.toLowerCase());
        case 'not_contains':
            return expected === '' || !value.toLowerCase().includes(expected.toLowerCase());
        case 'empty':
            return value === '';
        case 'not_empty':
            return value !== '';
        case 'gt':
            return value !== '' && expected !== '' && Number(value) > Number(expected);
        case 'lt':
            return value !== '' && expected !== '' && Number(value) < Number(expected);
        default:
            return false;
    }
}

function ruleTargets(rule) {
    const names = Array.isArray(rule.targets) ? rule.targets.slice() : [];
    if (rule.target) names.push(rule.target);
    return [...new Set(names.filter(Boolean))];
}

function hiddenTargets(rules, form) {
    const grouped = new Map();
    rules.forEach(rule => {
        ruleTargets(rule).forEach(target => {
            if (!grouped.has(target)) grouped.set(target, []);
            grouped.get(target).push(rule);
        });
    });

    const hidden = new Set();
    grouped.forEach((group, target) => {
        const showRules = group.filter(rule => rule.action === 'show');
        const hideHit = group.some(rule => rule.action === 'hide' && matches(rule, form));
        if (hideHit) {
            hidden.add(target);
            return;
        }
        if (showRules.length && !showRules.some(rule => matches(rule, form))) {
            hidden.add(target);
        }
    });
    return hidden;
}

function fieldBlock(form, name) {
    const wrap = form.querySelector(`.wpcf7-form-control-wrap[data-name="${cssEscape(name)}"]`);
    if (!wrap) {
        const field = form.querySelector(`[name="${cssEscape(name)}"], [name="${cssEscape(name)}[]"]`);
        return field ? (field.closest('p, label, li, div') || field) : null;
    }
    const parent = wrap.parentElement;
    if (!parent || parent.matches('form')) return wrap;
    const wraps = parent.querySelectorAll(':scope > .wpcf7-form-control-wrap, :scope > span > .wpcf7-form-control-wrap, .wpcf7-form-control-wrap');
    if (wraps.length === 1) return parent;
    return wrap;
}

function setHidden(block, hidden) {
    block.classList.toggle('cf7mw-is-hidden', hidden);
    block.querySelectorAll('input, select, textarea').forEach(field => {
        if (field.dataset.cf7mwWasDisabled === undefined) {
            field.dataset.cf7mwWasDisabled = field.disabled ? '1' : '0';
        }
        if (hidden) {
            field.disabled = true;
            return;
        }
        if (field.dataset.cf7mwWasDisabled === '0') {
            field.disabled = false;
        }
    });
}

function apply(form, rules) {
    const targets = new Set(rules.flatMap(ruleTargets));
    const hidden = hiddenTargets(rules, form);
    targets.forEach(name => {
        const block = fieldBlock(form, name);
        if (!block) return;
        setHidden(block, hidden.has(name));
    });
}

function cssEscape(value) {
    if (window.CSS && typeof window.CSS.escape === 'function') {
        return window.CSS.escape(value);
    }
    return String(value).replace(/["\\\]\s]/g, '\\$&');
}

function initForm(form) {
    if (form.dataset.cf7mwConditions === '1') return;
    const rules = readRules(form);
    if (!rules.length) return;
    form.dataset.cf7mwConditions = '1';
    const run = () => apply(form, rules);
    form.addEventListener('input', run);
    form.addEventListener('change', run);
    form.addEventListener('wpcf7reset', run);
    form.addEventListener('wpcf7mailsent', () => window.setTimeout(run, 30));
    run();
}

function init() {
    document.querySelectorAll('form.wpcf7-form').forEach(initForm);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
} else {
    init();
}
