/* Scoped timetable helpers. Domain validation and clash checks stay on the server. */
(() => {
  const root = document.querySelector('[data-timetable-workbench]');
  if (!root) return;
  const bells = root.querySelector('[data-bell-form]');
  if (bells) {
    const list = bells.querySelector('[data-period-list]');
    const template = bells.querySelector('[data-period-template]');
    const add = bells.querySelector('[data-add-period]');
    add.addEventListener('click', () => {
      const index = list.querySelectorAll('[data-period-row]').length;
      if (index >= 30) return;
      const row = template.content.cloneNode(true);
      row.querySelectorAll('*').forEach(element => {
        for (const attribute of [...element.attributes]) {
          if (attribute.value.includes('__INDEX__')) element.setAttribute(attribute.name, attribute.value.replaceAll('__INDEX__', String(index)));
        }
      });
      list.append(row);
      if (index + 1 >= 30) add.disabled = true;
    });
  }
  const importer = root.querySelector('[data-import-value-form]');
  if (importer) importer.addEventListener('submit', () => {
    const values = Object.create(null);
    importer.querySelectorAll('[data-map-field]').forEach(select => {
      const field = select.dataset.mapField;
      values[field] ??= Object.create(null);
      values[field][select.dataset.mapSource] = Number(select.value);
    });
    importer.querySelector('[data-import-values]').value = JSON.stringify(values);
  });
})();
