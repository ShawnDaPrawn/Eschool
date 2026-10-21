/* ============================================================
   Panel shell: year switcher, read-only years, skip link.
   ------------------------------------------------------------
   Loaded by layouts/master.blade.php after footer_js (jQuery,
   Bootstrap, function.js). window.panelShell comes from the
   layout: { readOnly, readOnlyMessage, readOnlyAllow, setYearUrl,
   baseUrl }. The server refuses writes in a read-only year
   (EnforceReadOnlyYears); this file only makes that visible and
   stops the attempt early. Page components: page.js.
   ============================================================ */
(function ($) {
    "use strict";

    var shell = window.panelShell || {};

    function csrfToken() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute("content") : "";
    }

    /* ---- Year switcher ------------------------------------------
       Every .js-year-switcher (header and the small-screen drawer)
       stores the chosen year in the session and reloads, so the
       banner, the lists and the disabled writes all follow it. The
       header select keeps id="filter_session_year_global", which
       the list pages read for their session_year_id parameter. */
    function switchYear(id) {
        return fetch(shell.setYearUrl, {
            method: "POST",
            credentials: "same-origin",
            headers: {
                "Content-Type": "application/json",
                Accept: "application/json",
                "X-CSRF-TOKEN": csrfToken(),
            },
            body: JSON.stringify({ session_year_id: id }),
        });
    }

    window.panelSwitchYear = function (id) {
        return switchYear(id).then(function () {
            window.location.reload();
        });
    };

    $(document).on("change", ".js-year-switcher:not(#filter_session_year_global)", function () {
        window.panelSwitchYear($(this).val());
    });

    /* ---- Read-only years -------------------------------------- */
    if (!shell.readOnly) {
        return;
    }

    var allow = (shell.readOnlyAllow || []).map(function (pattern) {
        var escaped = String(pattern).replace(/[.+?^${}()|[\]\\]/g, "\\$&").replace(/\*/g, ".*");
        return new RegExp("^" + escaped + "$");
    });

    /** The request path relative to the panel's base URL, without slashes at the ends. */
    function relativePath(url) {
        var parsed;
        try {
            parsed = new URL(url, window.location.href);
        } catch (e) {
            return "";
        }
        var base = new URL(shell.baseUrl || window.location.origin, window.location.href).pathname.replace(/\/+$/, "");
        var path = parsed.pathname;
        if (base && path.indexOf(base) === 0) {
            path = path.slice(base.length);
        }
        return path.replace(/^\/+|\/+$/g, "");
    }

    function isAllowed(url) {
        var path = relativePath(url);
        return allow.some(function (re) {
            return re.test(path);
        });
    }

    function refuse() {
        if (typeof showErrorToast === "function") {
            showErrorToast(shell.readOnlyMessage);
        }
    }

    // Writes made with jQuery (most panel forms and table actions).
    $.ajaxPrefilter(function (options, original, jqXHR) {
        var method = String(options.type || options.method || "GET").toUpperCase();
        if (method === "GET" || method === "HEAD" || method === "OPTIONS" || isAllowed(options.url)) {
            return;
        }
        jqXHR.abort();
        refuse();
    });

    var WRITE_CONTROLS = [
        "[data-write]",
        ".delete-form",
        ".deletedata",
        ".deletepermanentdata",
        ".delete-semester",
        ".delete-question-form",
        ".delete-clear-btn",
        ".restore-data",
        ".trash-data",
    ].join(",");

    function isWriteForm(form) {
        if (!form || form.hasAttribute("data-year-safe")) {
            return false;
        }
        var method = (form.getAttribute("method") || "GET").toUpperCase();
        return method !== "GET" && !isAllowed(form.getAttribute("action") || window.location.href);
    }

    function disable(el) {
        if (el.hasAttribute("data-read-only-disabled")) {
            return;
        }
        el.setAttribute("data-read-only-disabled", "");
        el.setAttribute("aria-disabled", "true");
        el.setAttribute("title", shell.readOnlyMessage);
        if ("disabled" in el) {
            el.disabled = true;
        }
        el.classList.add("is-read-only-disabled");
    }

    function disableWrites(root) {
        root.querySelectorAll(WRITE_CONTROLS).forEach(disable);
        root.querySelectorAll("form").forEach(function (form) {
            if (isWriteForm(form)) {
                form.querySelectorAll('button[type="submit"], button:not([type]), input[type="submit"]').forEach(disable);
            }
        });
    }

    // Stop clicks on write controls (including ones a table adds later) and form submits.
    document.addEventListener(
        "click",
        function (event) {
            var control = event.target.closest(WRITE_CONTROLS + ",[data-read-only-disabled]");
            if (control) {
                event.preventDefault();
                event.stopImmediatePropagation();
                refuse();
            }
        },
        true
    );
    document.addEventListener(
        "submit",
        function (event) {
            if (isWriteForm(event.target)) {
                event.preventDefault();
                event.stopImmediatePropagation();
                refuse();
            }
        },
        true
    );

    $(function () {
        disableWrites(document);
        // Tables and modals render rows and buttons after the page loads.
        new MutationObserver(function (mutations) {
            mutations.forEach(function (mutation) {
                mutation.addedNodes.forEach(function (node) {
                    if (node.nodeType === 1) {
                        disableWrites(node.parentNode || node);
                    }
                });
            });
        }).observe(document.body, { childList: true, subtree: true });
    });
})(jQuery);
