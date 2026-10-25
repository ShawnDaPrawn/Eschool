'use strict';
for (const form of document.querySelectorAll('[data-mastery-graph]')) {
    const edges = form.querySelector('[data-mastery-edges]');
    const template = form.querySelector('[data-mastery-edge-template]');
    let sequence = Math.max(-1, ...Array.from(edges.querySelectorAll('[data-mastery-edge]'), edge => Number(edge.dataset.masteryEdgeIndex)).filter(Number.isFinite)) + 1;
    form.querySelector('[data-mastery-add]').addEventListener('click', () => {
        if (edges.querySelectorAll('[data-mastery-edge]').length >= 10000) return;
        const fragment = template.content.cloneNode(true);
        const index = sequence++;
        for (const element of fragment.querySelectorAll('[name], [id], [for]')) {
            for (const attribute of ['name', 'id', 'for']) {
                if (element.hasAttribute(attribute)) element.setAttribute(attribute, element.getAttribute(attribute).replaceAll('__INDEX__', String(index)));
            }
        }
        edges.append(fragment);
        edges.lastElementChild.querySelector('select').focus();
    });
    edges.addEventListener('click', event => {
        const button = event.target.closest('[data-mastery-remove]');
        if (button) button.closest('[data-mastery-edge]').remove();
    });
}
