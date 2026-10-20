/*
 * roles-permissions.js — drives the accordion permission picker on
 * roles/create + roles/edit (markup: resources/views/roles/_permissions.blade.php).
 *
 * Moved out of the partial's inline <script> (per the "no JS in Blade" convention)
 * and generalised: it self-initialises EVERY .permission-accordion on the page via
 * DOM traversal + data-* hooks, so it no longer depends on a Blade-interpolated id
 * and works for multiple pickers at once.
 *
 * Hooks (set in the Blade): [data-permission-select-all], [data-permission-selected-count],
 * [data-permission-toggle-all] (a single expand/collapse toggle, with its label swapped
 * from the data-label-expand / data-label-collapse attributes), [data-permission-group],
 * [data-permission-group-toggle], [data-permission-group-count], [data-permission-child].
 */
(function () {
    'use strict';

    function initAccordion(accordion) {
        // The toolbar (select-all + counter + expand/collapse) lives in a sibling
        // column, so scope lookups to the common .row wrapper.
        var root = accordion.closest('.row') || accordion.parentElement;
        var selectAll = root.querySelector('[data-permission-select-all]');
        var totalCounter = root.querySelector('[data-permission-selected-count]');
        var toggleAllBtn = root.querySelector('[data-permission-toggle-all]');
        var groups = accordion.querySelectorAll('[data-permission-group]');

        function syncGroup(group) {
            var parent = group.querySelector('[data-permission-group-toggle]');
            var children = group.querySelectorAll('[data-permission-child]');
            var checked = group.querySelectorAll('[data-permission-child]:checked');
            var countEl = group.querySelector('[data-permission-group-count]');

            if (parent) {
                if (checked.length === 0) {
                    parent.checked = false;
                    parent.indeterminate = false;
                } else if (checked.length === children.length) {
                    parent.checked = true;
                    parent.indeterminate = false;
                } else {
                    parent.checked = false;
                    parent.indeterminate = true;
                }
            }

            group.classList.toggle('is-active', checked.length > 0);
            if (countEl) countEl.textContent = checked.length;
        }

        function syncTotals() {
            var allChildren = accordion.querySelectorAll('[data-permission-child]');
            var checkedChildren = accordion.querySelectorAll('[data-permission-child]:checked');

            if (totalCounter) totalCounter.textContent = checkedChildren.length;
            if (!selectAll) return;

            if (checkedChildren.length === 0) {
                selectAll.checked = false;
                selectAll.indeterminate = false;
            } else if (checkedChildren.length === allChildren.length) {
                selectAll.checked = true;
                selectAll.indeterminate = false;
            } else {
                selectAll.checked = false;
                selectAll.indeterminate = true;
            }
        }

        groups.forEach(function (group) {
            var parent = group.querySelector('[data-permission-group-toggle]');
            var children = group.querySelectorAll('[data-permission-child]');

            if (parent) {
                parent.addEventListener('change', function () {
                    children.forEach(function (child) {
                        child.checked = parent.checked;
                    });
                    syncGroup(group);
                    syncTotals();
                });
            }

            children.forEach(function (child) {
                child.addEventListener('change', function () {
                    syncGroup(group);
                    syncTotals();
                });
            });

            // Make the whole header toggle the group (the header shows a pointer
            // cursor), except clicks on the select-checkbox/label or the chevron
            // button — those handle themselves.
            var header = group.querySelector('.card-header');
            var toggleBtn = group.querySelector('[data-toggle="collapse"]');
            if (header && toggleBtn) {
                header.addEventListener('click', function (e) {
                    if (e.target.closest('label') || e.target.closest('[data-toggle="collapse"]')) return;
                    toggleBtn.click();
                });
            }

            syncGroup(group);
        });

        if (selectAll) {
            selectAll.addEventListener('change', function () {
                accordion.querySelectorAll('[data-permission-child]').forEach(function (child) {
                    child.checked = selectAll.checked;
                });
                groups.forEach(syncGroup);
                syncTotals();
            });
        }

        // Expand / collapse all. The groups are independent collapsibles (no
        // data-parent), so showing/hiding them all is valid. Prefer Bootstrap's
        // collapse plugin when present; fall back to toggling classes directly.
        // Also reflects the open/closed state onto the single toggle button —
        // its aria-expanded drives the chevron rotation (CSS) and its label
        // swaps between the expand/collapse strings carried as data-* attrs.
        function setAllOpen(open) {
            var collapses = accordion.querySelectorAll('.collapse');
            var toggles = accordion.querySelectorAll('[data-toggle="collapse"]');

            if (window.jQuery && window.jQuery.fn && window.jQuery.fn.collapse) {
                window.jQuery(collapses).collapse(open ? 'show' : 'hide');
            } else {
                collapses.forEach(function (el) { el.classList.toggle('show', open); });
            }
            toggles.forEach(function (btn) {
                btn.setAttribute('aria-expanded', open ? 'true' : 'false');
                btn.classList.toggle('collapsed', !open);
            });

            if (toggleAllBtn) {
                toggleAllBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
                var label = toggleAllBtn.querySelector('[data-permission-toggle-label]');
                if (label) {
                    var next = open
                        ? toggleAllBtn.getAttribute('data-label-collapse')
                        : toggleAllBtn.getAttribute('data-label-expand');
                    if (next) label.textContent = next;
                }
            }
        }

        if (toggleAllBtn) {
            toggleAllBtn.addEventListener('click', function () {
                setAllOpen(toggleAllBtn.getAttribute('aria-expanded') !== 'true');
            });
        }

        syncTotals();
    }

    function init() {
        document.querySelectorAll('.permission-accordion').forEach(initAccordion);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
