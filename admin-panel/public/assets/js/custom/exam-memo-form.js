/* Pack the existing memo controls before PHP parses the request. No network or persistence here. */
(() => {
    'use strict';
    const form = document.querySelector('[data-exam-memo-form]');
    if (!form) return;
    form.addEventListener('formdata', event => {
        const questions = [];
        const names = [];
        for (const [name, value] of event.formData.entries()) {
            const number = /^questions\[(\d+)\]\[number\]$/.exec(name);
            const point = /^questions\[(\d+)\]\[points\]\[(\d+)\]\[(earns|type|marks|rule_kind|rule_value|rule_tolerance|rule_unit)\]$/.exec(name);
            if (!number && !point) continue;
            const index = Number((number || point)[1]);
            questions[index] ??= {number: '', points: []};
            if (number) questions[index].number = value;
            else {
                const row = Number(point[2]);
                questions[index].points[row] ??= {};
                questions[index].points[row][point[3]] = value;
            }
            names.push(name);
        }
        // Mutate the submitted FormData, keeping all visible controls editable
        // for a retry. CSRF and the expected revision remain ordinary fields.
        names.forEach(name => event.formData.delete(name));
        event.formData.set('memo_payload', JSON.stringify(questions));
    });
})();
