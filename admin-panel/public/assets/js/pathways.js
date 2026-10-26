/* Structured source-rule editor. Values stay in labelled native form controls. */
(() => {
    'use strict';
    const root = document.querySelector('[data-pathway-editor]');
    if (!root) return;
    const limits = {subject: 50, band: 20, elective: 10};
    const containers = kind => root.querySelector(`[data-pathway-rows="${kind}"]`);
    const refresh = kind => {
        const container = containers(kind);
        if (!container) return;
        const rows = [...container.querySelectorAll('[data-pathway-row]')];
        rows.forEach((row, index) => {
            for (const node of [row, ...row.querySelectorAll('*')]) {
                for (const attr of [...node.attributes]) {
                    const next = attr.value
                        .replace(/^(score_subjects|score_bands|elective_groups)\[(?:__INDEX__|\d+)\]/, (_, prefix) => `${prefix}[${index}]`)
                        .replace(new RegExp(`(pathway-${kind}-)(?:__INDEX__|\\d+)(-)`, 'g'), (_, prefix, suffix) => `${prefix}${index}${suffix}`);
                    if (next !== attr.value) node.setAttribute(attr.name, next);
                }
            }
        });
        const add = root.querySelector(`[data-pathway-add="${kind}"]`);
        if (add) add.disabled = rows.length >= limits[kind];
    };
    root.addEventListener('click', event => {
        const add = event.target.closest('[data-pathway-add]');
        if (add && root.contains(add)) {
            const kind = add.dataset.pathwayAdd, container = containers(kind);
            if (!container || container.children.length >= limits[kind]) return;
            const template = root.querySelector(`[data-pathway-template="${kind}"]`);
            if (!template) return;
            container.append(template.content.cloneNode(true));
            refresh(kind);
            container.lastElementChild?.querySelector('select,input:not([type="hidden"])')?.focus();
        }
        const remove = event.target.closest('[data-pathway-remove]');
        if (remove && root.contains(remove)) {
            const row = remove.closest('[data-pathway-row]');
            if (!row) return;
            const kind = row.dataset.pathwayRow;
            row.remove();
            refresh(kind);
            root.querySelector(`[data-pathway-add="${kind}"]`)?.focus();
        }
    });
    Object.keys(limits).forEach(refresh);
})();
