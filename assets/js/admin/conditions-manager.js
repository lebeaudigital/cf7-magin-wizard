/**
 * conditions-manager.js
 *
 * Constructeur de conditions dans l'éditeur Contact Form 7.
 * Chaque règle est une phrase : si [champ] [opérateur] [valeur] alors [action] [champ].
 * Les listes de champs suivent le contenu de l'onglet Formulaire.
 */

const ROOT = '[data-cf7mw-conditions-root]';
const FORM_FIELD = '#wpcf7-form';
const FIELD_TYPES = ['text', 'email', 'tel', 'url', 'number', 'date', 'textarea', 'select', 'checkbox', 'radio', 'acceptance', 'quiz', 'range', 'hidden'];

const OPERATORS = [
    ['equals', 'est'],
    ['not_equals', "n'est pas"],
    ['contains', 'contient'],
    ['not_contains', 'ne contient pas'],
    ['empty', 'est vide'],
    ['not_empty', "n'est pas vide"],
    ['gt', 'est supérieur à'],
    ['lt', 'est inférieur à'],
];

const ACTIONS = [
    ['show', 'afficher'],
    ['hide', 'masquer'],
];

const VALUELESS = new Set(['empty', 'not_empty']);

function parseFields(content) {
    const pattern = new RegExp('\\[(' + FIELD_TYPES.join('|') + ')\\*?([^\\]]*)\\]', 'gi');
    const fields = [];
    const seen = new Set();
    let match = pattern.exec(content);
    while (match) {
        const parsed = parseTagBody(match[2] || '');
        if (parsed.name && !seen.has(parsed.name)) {
            seen.add(parsed.name);
            fields.push({
                name: parsed.name,
                type: match[1].toLowerCase(),
                label: parsed.label || parsed.name,
                options: parsed.options,
            });
        }
        match = pattern.exec(content);
    }
    return fields;
}

function parseTagBody(body) {
    const nameOk = /^[A-Za-z][-A-Za-z0-9_:.]*$/;
    let name = '';
    let label = '';
    const options = [];
    let expectPlaceholder = false;

    tokenize(body).forEach(token => {
        if (expectPlaceholder) {
            label = token.replace(/^["']|["']$/g, '');
            expectPlaceholder = false;
            return;
        }
        if (token === 'placeholder') {
            expectPlaceholder = true;
            return;
        }
        if (token.startsWith('placeholder:')) {
            label = token.slice('placeholder:'.length);
            return;
        }
        if (!name && !token.includes(':')) {
            const candidate = token.replace(/^["']|["']$/g, '');
            if (nameOk.test(candidate)) {
                name = candidate;
            }
            return;
        }
        if (token.startsWith('"') || token.startsWith("'")) {
            const raw = token.replace(/^["']|["']$/g, '');
            const pipe = raw.indexOf('|');
            const optionLabel = pipe === -1 ? raw : raw.slice(0, pipe).trim();
            const optionValue = pipe === -1 ? raw : raw.slice(pipe + 1).trim();
            if (optionValue) {
                options.push({ value: optionValue, label: optionLabel || optionValue });
            }
        }
    });

    return { name, label, options };
}

function tokenize(body) {
    const tokens = [];
    let buffer = '';
    let quote = '';
    for (const char of body) {
        if (quote) {
            buffer += char;
            if (char === quote) {
                tokens.push(buffer);
                buffer = '';
                quote = '';
            }
            continue;
        }
        if (char === '"' || char === "'") {
            if (buffer) {
                tokens.push(buffer);
                buffer = '';
            }
            quote = char;
            buffer = char;
            continue;
        }
        if (/\s/.test(char)) {
            if (buffer) {
                tokens.push(buffer);
                buffer = '';
            }
            continue;
        }
        buffer += char;
    }
    if (buffer) tokens.push(buffer);
    return tokens;
}

function escapeHtml(str) {
    return String(str)
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;');
}

function fieldOptions(fields, selected, placeholder) {
    const known = new Set(fields.map(field => field.name));
    let html = `<option value="">${escapeHtml(placeholder)}</option>`;
    fields.forEach(field => {
        html += `<option value="${escapeHtml(field.name)}"${field.name === selected ? ' selected' : ''}>${escapeHtml(field.label)}</option>`;
    });
    if (selected && !known.has(selected)) {
        html += `<option value="${escapeHtml(selected)}" selected>${escapeHtml(selected)}</option>`;
    }
    return html;
}

function operatorOptions(selected) {
    return OPERATORS.map(([value, label]) => (
        `<option value="${value}"${value === selected ? ' selected' : ''}>${escapeHtml(label)}</option>`
    )).join('');
}

function actionOptions(selected) {
    return ACTIONS.map(([value, label]) => (
        `<option value="${value}"${value === selected ? ' selected' : ''}>${escapeHtml(label)}</option>`
    )).join('');
}

function valueControl(index, value, field) {
    if (field && field.options.length) {
        const known = new Set(field.options.map(option => option.value));
        let html = `<select name="cf7mw[rules][${index}][value]" data-part="value">`;
        html += `<option value="">${escapeHtml('Choisir une valeur')}</option>`;
        field.options.forEach(option => {
            html += `<option value="${escapeHtml(option.value)}"${option.value === value ? ' selected' : ''}>${escapeHtml(option.label)}</option>`;
        });
        if (value && !known.has(value)) {
            html += `<option value="${escapeHtml(value)}" selected>${escapeHtml(value)}</option>`;
        }
        html += '</select>';
        return html;
    }
    return `<input type="text" name="cf7mw[rules][${index}][value]" data-part="value" value="${escapeHtml(value)}" placeholder="${escapeHtml('Valeur')}">`;
}

function targetItem(index, selected, fields, removable) {
    return `<span class="cf7mw-rule__target">
        <select name="cf7mw[rules][${index}][targets][]" data-part="target">${fieldOptions(fields, selected, 'Choisir un champ')}</select>
        <button type="button" class="cf7mw-rule__target-remove" data-cf7mw-remove-target aria-label="Retirer ce champ"${removable ? '' : ' hidden'}>×</button>
    </span>`;
}

function targetsMarkup(index, targets, fields) {
    const list = targets.length ? targets : [''];
    const removable = list.length > 1;
    return `<span class="cf7mw-rule__targets" data-part="targets">${list.map(name => targetItem(index, name, fields, removable)).join('')}
        <button type="button" class="cf7mw-rule__add-target" data-cf7mw-add-target aria-label="Ajouter un champ">+</button>
    </span>`;
}

function syncTargetRemoves(row) {
    const items = row.querySelectorAll('.cf7mw-rule__target');
    items.forEach(item => {
        const button = item.querySelector('[data-cf7mw-remove-target]');
        if (button) button.hidden = items.length < 2;
    });
}

function addTargetSelect(row) {
    const group = row.querySelector('[data-part="targets"]');
    const first = group?.querySelector('[data-part="target"]');
    const add = group?.querySelector('[data-cf7mw-add-target]');
    if (!group || !add) return;
    const item = document.createElement('span');
    item.className = 'cf7mw-rule__target';
    const select = document.createElement('select');
    select.dataset.part = 'target';
    select.name = first?.name || 'cf7mw[rules][0][targets][]';
    if (first) {
        [...first.options].forEach(option => {
            const copy = option.cloneNode(true);
            copy.selected = false;
            select.appendChild(copy);
        });
    } else {
        select.innerHTML = '<option value="">Choisir un champ</option>';
    }
    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'cf7mw-rule__target-remove';
    button.dataset.cf7mwRemoveTarget = '';
    button.setAttribute('aria-label', 'Retirer ce champ');
    button.textContent = '×';
    item.append(select, button);
    group.insertBefore(item, add);
    syncTargetRemoves(row);
    select.focus();
}

function buildRule(index, rule, fields) {
    const row = document.createElement('div');
    row.className = 'cf7mw-rule';
    row.dataset.cf7mwRule = '';
    const operator = rule.operator || 'equals';
    row.innerHTML = `
        <span class="cf7mw-rule__word">Si</span>
        <select name="cf7mw[rules][${index}][source]" data-part="source">${fieldOptions(fields, rule.source || '', 'Choisir un champ')}</select>
        <select name="cf7mw[rules][${index}][operator]" data-part="operator">${operatorOptions(operator)}</select>
        <span class="cf7mw-rule__value" data-part="value-wrap"></span>
        <span class="cf7mw-rule__word">alors</span>
        <select name="cf7mw[rules][${index}][action]" data-part="action">${actionOptions(rule.action || 'show')}</select>
        ${targetsMarkup(index, rule.targets || (rule.target ? [rule.target] : ['']), fields)}
        <button type="button" class="cf7mw-rule__remove" data-cf7mw-remove-rule aria-label="Supprimer cette condition">×</button>
        <p class="cf7mw-rule__summary" data-part="summary"></p>
    `;
    const source = fields.find(field => field.name === (rule.source || ''));
    row.querySelector('[data-part="value-wrap"]').innerHTML = valueControl(index, rule.value || '', source);
    if (VALUELESS.has(operator)) {
        row.classList.add('is-valueless');
    }
    return row;
}

function readRule(row) {
    const value = (part) => row.querySelector(`[data-part="${part}"]`)?.value || '';
    return {
        source: value('source'),
        operator: value('operator') || 'equals',
        value: value('value'),
        action: value('action') || 'show',
        targets: [...row.querySelectorAll('[data-part="target"]')].map(select => select.value).filter(Boolean),
    };
}

function fieldLabel(fields, name) {
    return fields.find(field => field.name === name)?.label || name || '…';
}

function updateSummary(row, fields) {
    const rule = readRule(row);
    const summary = row.querySelector('[data-part="summary"]');
    if (!summary) return;
    if (!rule.source || rule.targets.length === 0) {
        summary.textContent = 'Choisissez le champ qui déclenche la condition, puis les champs à afficher ou masquer.';
        return;
    }
    const source = fieldLabel(fields, rule.source);
    const labels = rule.targets.map(name => fieldLabel(fields, name));
    const listed = labels.length === 1
        ? `« ${labels[0]} »`
        : `${labels.slice(0, -1).map(label => `« ${label} »`).join(', ')} et « ${labels.at(-1)} »`;
    const plural = labels.length > 1;
    const action = rule.action === 'hide'
        ? (plural ? 'se masquent' : 'se masque')
        : (plural ? 's’affichent' : 's’affiche');
    const operator = OPERATORS.find(([value]) => value === rule.operator)?.[1] || 'est';
    if (VALUELESS.has(rule.operator)) {
        summary.textContent = `${listed} ${action} quand « ${source} » ${operator}.`;
        return;
    }
    const shown = rule.value || '…';
    summary.textContent = `${listed} ${action} quand « ${source} » ${operator} « ${shown} ».`;
}

function refreshValueControl(row, fields) {
    const rule = readRule(row);
    const wrap = row.querySelector('[data-part="value-wrap"]');
    const index = row.dataset.index || '0';
    const source = fields.find(field => field.name === rule.source);
    wrap.innerHTML = valueControl(index, rule.value, source);
    row.classList.toggle('is-valueless', VALUELESS.has(rule.operator));
}

function reindex(list) {
    [...list.querySelectorAll('[data-cf7mw-rule]')].forEach((row, index) => {
        row.dataset.index = String(index);
        row.querySelectorAll('[name^="cf7mw[rules]"]').forEach(input => {
            input.name = input.name.replace(/cf7mw\[rules]\[\d+]/, `cf7mw[rules][${index}]`);
        });
    });
}

function bindRule(row, list, fields) {
    const redraw = () => updateSummary(row, fields);
    row.querySelector('[data-part="source"]')?.addEventListener('change', () => {
        refreshValueControl(row, fields);
        redraw();
    });
    row.querySelector('[data-part="operator"]')?.addEventListener('change', () => {
        row.classList.toggle('is-valueless', VALUELESS.has(readRule(row).operator));
        redraw();
    });
    row.addEventListener('input', redraw);
    row.addEventListener('change', redraw);
    row.addEventListener('click', (event) => {
        if (event.target.closest('[data-cf7mw-add-target]')) {
            addTargetSelect(row);
            reindex(list);
            redraw();
        }
        const removeTarget = event.target.closest('[data-cf7mw-remove-target]');
        if (removeTarget && row.querySelectorAll('.cf7mw-rule__target').length > 1) {
            removeTarget.closest('.cf7mw-rule__target')?.remove();
            syncTargetRemoves(row);
            reindex(list);
            redraw();
        }
    });
    row.querySelector('[data-cf7mw-remove-rule]')?.addEventListener('click', () => {
        row.remove();
        reindex(list);
    });
    redraw();
    syncTargetRemoves(row);
}

function refreshFieldLists(root, fields) {
    root.querySelectorAll('[data-part="source"], [data-part="target"]').forEach(select => {
        const selected = select.value;
        const placeholder = select.dataset.part === 'source' ? 'Choisir un champ' : 'Choisir un champ';
        select.innerHTML = fieldOptions(fields, selected, placeholder);
    });
    root.querySelectorAll('[data-cf7mw-rule]').forEach(row => {
        refreshValueControl(row, fields);
        updateSummary(row, fields);
    });
}

function initRoot(root) {
    const list = root.querySelector('[data-cf7mw-rules]');
    const addButton = root.querySelector('[data-cf7mw-add-rule]');
    if (!list || !addButton) return;

    const fieldsOf = () => parseFields(document.querySelector(FORM_FIELD)?.value || '');

    const bindAll = () => {
        const fields = fieldsOf();
        reindex(list);
        list.querySelectorAll('[data-cf7mw-rule]').forEach(row => bindRule(row, list, fields));
        refreshFieldLists(root, fields);
    };

    bindAll();

    addButton.addEventListener('click', () => {
        const fields = fieldsOf();
        const index = list.querySelectorAll('[data-cf7mw-rule]').length;
        const row = buildRule(index, { operator: 'equals', action: 'show' }, fields);
        list.appendChild(row);
        bindRule(row, list, fields);
        reindex(list);
        row.querySelector('select')?.focus();
    });

    const field = document.querySelector(FORM_FIELD);
    if (field && field.dataset.cf7mwConditionsBound !== '1') {
        field.dataset.cf7mwConditionsBound = '1';
        field.addEventListener('input', () => {
            document.querySelectorAll(ROOT).forEach(node => refreshFieldLists(node, parseFields(field.value)));
        });
    }
}

function init() {
    document.querySelectorAll(ROOT).forEach(initRoot);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
} else {
    init();
}
