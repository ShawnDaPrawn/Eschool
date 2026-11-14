/* Progressive layout only. Existing controllers, forms and table actions remain
   authoritative; this file never fabricates records or submits a write. */
(function () {
    "use strict";
    function label(key, fallback) {
        var value = typeof window.trans === "function" ? window.trans(key) : key;
        return value && value !== key ? value : fallback;
    }
    function enhanceTables(root) {
        root.querySelectorAll(".content-wrapper table").forEach(function (table) {
            if (table.closest(".page-table__scroll, .panel-table-scroll, .table-responsive, .fixed-table-body") ||
                (table.dataset.toggle === "table" || table.hasAttribute("data-page-table-init"))) return;
            var region = document.createElement("div");
            region.className = "panel-table-scroll";
            region.setAttribute("role", "region");
            region.tabIndex = 0;
            var caption = table.caption && table.caption.textContent.trim();
            region.setAttribute("aria-label", caption || label("page_table_region", "Table"));
            table.parentNode.insertBefore(region, table);
            region.appendChild(table);
        });
    }
    function revealCurrentSection() {
        document.querySelectorAll(".panel-section-nav, .panel-local-nav, .workspace-tabs").forEach(function (nav) {
            var current = nav.querySelector('[aria-current="page"]');
            if (!current || nav.scrollWidth <= nav.clientWidth) return;
            var viewport = nav.getBoundingClientRect();
            var item = current.getBoundingClientRect();
            var left = viewport.left + nav.clientLeft;
            var right = left + nav.clientWidth;
            var offset = item.left < left ? item.left - left : (item.right > right ? item.right - right : 0);
            if (offset) nav.scrollBy({ left: offset });
        });
    }
    function ready() {
        enhanceTables(document);
        revealCurrentSection();
        window.addEventListener("load", revealCurrentSection);
        document.addEventListener("page-table:loaded", function () { enhanceTables(document); });
    }
    if (document.readyState === "loading") document.addEventListener("DOMContentLoaded", ready);
    else ready();
})();
