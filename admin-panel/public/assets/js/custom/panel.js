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

    /* ---- Global search box --------------------------------------
       A combobox over GET /search (JSON). Results are rendered with
       textContent and safeUrl(), never as HTML. "/" (outside a field)
       and Ctrl/Cmd+K focus it; arrows move, Enter opens, Escape
       closes. Enter with nothing highlighted opens the results page. */
    (function globalSearch() {
        var form = document.querySelector("[data-panel-search]");
        if (!form) {
            return;
        }
        var input = form.querySelector("input[name=q]");
        var list = form.querySelector('[role="listbox"]');
        var status = form.querySelector("[data-search-status]");
        var timer = null;
        var request = 0;
        var active = -1;

        function options() {
            return Array.prototype.slice.call(list.querySelectorAll('[role="option"]'));
        }

        function open(show) {
            list.hidden = !show;
            input.setAttribute("aria-expanded", show ? "true" : "false");
            if (!show) {
                setActive(-1);
            }
        }

        function setActive(index) {
            var all = options();
            all.forEach(function (option, i) {
                var on = i === index;
                option.classList.toggle("is-active", on);
                option.setAttribute("aria-selected", on ? "true" : "false");
            });
            active = index;
            if (index >= 0 && all[index]) {
                input.setAttribute("aria-activedescendant", all[index].id);
                all[index].scrollIntoView({ block: "nearest" });
            } else {
                input.removeAttribute("aria-activedescendant");
            }
        }

        function option(id, href, title, subtitle) {
            var a = document.createElement("a");
            a.id = id;
            a.className = "panel-search-option";
            a.setAttribute("role", "option");
            a.setAttribute("aria-selected", "false");
            a.tabIndex = -1;
            a.href = typeof safeUrl === "function" ? safeUrl(href) : href;
            var t = document.createElement("span");
            t.className = "panel-search-option-title";
            t.textContent = title;
            a.appendChild(t);
            if (subtitle) {
                var s = document.createElement("span");
                s.className = "panel-search-option-subtitle";
                s.textContent = subtitle;
                a.appendChild(s);
            }
            return a;
        }

        function render(data) {
            list.textContent = "";
            var count = 0;
            (data.groups || []).forEach(function (group, g) {
                var wrap = document.createElement("div");
                wrap.setAttribute("role", "group");
                wrap.setAttribute("aria-labelledby", "panel-search-group-" + g);
                var heading = document.createElement("div");
                heading.className = "panel-search-group-label";
                heading.id = "panel-search-group-" + g;
                heading.textContent = group.label;
                wrap.appendChild(heading);
                group.items.forEach(function (item) {
                    wrap.appendChild(option("panel-search-option-" + count++, item.url, item.title, item.subtitle));
                });
                list.appendChild(wrap);
            });
            if (!count) {
                var none = document.createElement("div");
                none.className = "panel-search-empty";
                none.textContent = trans("search_box_nothing");
                list.appendChild(none);
            }
            list.appendChild(option("panel-search-option-all", data.search_url, trans("search_box_see_all")));
            status.textContent = count ? trans("search_box_found").replace(":count", String(count)) : trans("search_box_nothing");
            open(true);
            setActive(-1);
        }

        function run() {
            var q = input.value.trim();
            if (q.length < 2) {
                open(false);
                return;
            }
            var mine = ++request;
            fetch(form.action + "?q=" + encodeURIComponent(q), {
                credentials: "same-origin",
                headers: { Accept: "application/json", "X-Requested-With": "XMLHttpRequest" },
            })
                .then(function (response) {
                    return response.ok ? response.json() : null;
                })
                .then(function (data) {
                    if (data && mine === request && document.activeElement === input) {
                        render(data);
                    }
                })
                .catch(function () {
                    /* The results page still works with Enter. */
                });
        }

        input.addEventListener("input", function () {
            clearTimeout(timer);
            timer = setTimeout(run, 200);
        });

        input.addEventListener("keydown", function (event) {
            var all = options();
            if (event.key === "ArrowDown" && all.length) {
                event.preventDefault();
                if (list.hidden) {
                    open(true);
                }
                setActive(active + 1 >= all.length ? 0 : active + 1);
            } else if (event.key === "ArrowUp" && all.length) {
                event.preventDefault();
                setActive(active - 1 < 0 ? all.length - 1 : active - 1);
            } else if (event.key === "Enter" && !list.hidden && active >= 0 && all[active]) {
                event.preventDefault();
                window.location.assign(all[active].href);
            } else if (event.key === "Escape") {
                if (!list.hidden) {
                    event.preventDefault();
                    open(false);
                } else {
                    input.value = "";
                }
            }
        });

        input.addEventListener("focus", function () {
            if (list.childNodes.length && input.value.trim().length >= 2) {
                open(true);
            }
        });

        document.addEventListener("click", function (event) {
            if (!form.contains(event.target)) {
                open(false);
            }
        });

        // Shortcuts: "/" outside a field, Ctrl+K or Cmd+K anywhere.
        document.addEventListener("keydown", function (event) {
            var target = event.target;
            var typing = target.isContentEditable || /^(INPUT|TEXTAREA|SELECT)$/.test(target.tagName);
            var slash = event.key === "/" && !typing && !event.ctrlKey && !event.metaKey && !event.altKey;
            var commandK = (event.key === "k" || event.key === "K") && (event.ctrlKey || event.metaKey);
            if (slash || commandK) {
                event.preventDefault();
                input.focus();
                input.select();
            }
        });
    })();

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
