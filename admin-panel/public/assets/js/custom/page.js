/* ============================================================
   Page pattern behaviour for resources/views/components/page/:
   server-side tables (PageTable) with bulk actions and designed
   empty states, filters, form cards that save without a reload,
   and the confirm dialog. Built for the Material 3 library
   (public/assets/m3, m3.js loads first); no Bootstrap and no
   bootstrap-table. Loaded by layouts/master.blade.php after
   panel.js. See wiki/UI-Standards.md, "Admin panel page pattern".
   ============================================================ */
(function () {
    "use strict";

    function csrfToken() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute("content") : "";
    }

    function label(key, fallback) {
        return typeof trans === "function" && window.languageLabels && window.languageLabels[key] ? trans(key) : fallback;
    }

    function notify(type, message) {
        if (!message) {
            return;
        }
        if (window.M3 && typeof window.M3.snackbar === "function") {
            window.M3.snackbar({ message: message, type: type });
        } else if (type === "success" && typeof showSuccessToast === "function") {
            showSuccessToast(message);
        } else if (typeof showErrorToast === "function") {
            showErrorToast(message);
        }
    }

    function ready(fn) {
        if (document.readyState === "loading") {
            document.addEventListener("DOMContentLoaded", fn);
        } else {
            fn();
        }
    }

    function escapeText(value) {
        if (typeof escapeHtml === "function") {
            return escapeHtml(value);
        }
        return String(value)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    var searchParam = new URLSearchParams(window.location.search).get("search");

    /* ---- A list opened with ?search= starts filtered ------------
       Global search links to list pages with the name it found. The
       older list pages (bootstrap-table, which reads data-search-text
       when it starts on DOM ready) pick it up here; PageTable reads it
       itself. */
    if (searchParam) {
        document.querySelectorAll('table[data-search="true"]:not([data-page-table-init])').forEach(function (table) {
            if (!table.hasAttribute("data-search-text")) {
                table.setAttribute("data-search-text", searchParam);
            }
        });
    }

    /* ---- Names for controls the older plugins draw --------------
       bootstrap-table's row checkboxes and Select2's search fields on
       the older pages come without an accessible name; give them one,
       as they appear. */
    function labelText(select) {
        if (!select) {
            return "";
        }
        var tag = select.id ? document.querySelector('label[for="' + select.id + '"]') : null;
        return (tag && tag.textContent.replace(/\*/g, "").trim()) || select.getAttribute("aria-label") || "";
    }

    function nameControls(root) {
        root.querySelectorAll('input[name="btSelectAll"]:not([aria-label])').forEach(function (box) {
            box.setAttribute("aria-label", label("page_select_all_rows", "Select all rows"));
        });
        root.querySelectorAll('input[name="btSelectItem"]:not([aria-label])').forEach(function (box) {
            var index = Number(box.getAttribute("data-index") || 0) + 1;
            box.setAttribute("aria-label", label("page_select_row", "Select row :number").replace(":number", String(index)));
        });
        // A table wider than its card scrolls inside it; let the keyboard reach that scroll.
        root.querySelectorAll(".fixed-table-body:not([tabindex])").forEach(function (body) {
            body.setAttribute("tabindex", "0");
            body.setAttribute("role", "region");
            body.setAttribute("aria-label", label("page_table_region", "Table"));
        });
        root.querySelectorAll(".select2-search__field:not([aria-label])").forEach(function (field) {
            var container = field.closest(".select2-container");
            var select = container && container.previousElementSibling && container.previousElementSibling.tagName === "SELECT" ? container.previousElementSibling : null;
            field.setAttribute("aria-label", labelText(select) || label("search_label", "Search"));
        });
    }

    ready(function () {
        nameControls(document);
        new MutationObserver(function (mutations) {
            mutations.forEach(function (mutation) {
                mutation.addedNodes.forEach(function (node) {
                    if (node.nodeType === 1) {
                        nameControls(node.parentNode || node);
                    }
                });
            });
        }).observe(document.body, { childList: true, subtree: true });
    });

    /* ---- Confirm dialog (components/page/confirm-dialog) -------- */
    var pendingConfirm = null;

    window.pageConfirm = function (options) {
        options = options || {};
        var dialog = document.getElementById("page-confirm");
        if (!dialog || typeof dialog.showModal !== "function") {
            if (window.M3 && typeof window.M3.confirm === "function") {
                return window.M3.confirm({ title: options.title, message: options.message, confirmText: options.confirmLabel, destructive: !!options.danger });
            }
            return Promise.resolve(window.confirm(options.message || ""));
        }
        if (pendingConfirm) {
            pendingConfirm(false);
        }
        var opener = document.activeElement;
        dialog.querySelector("#page-confirm-title").textContent = options.title || label("page_confirm_title", "Are you sure?");
        dialog.querySelector("#page-confirm-message").textContent = options.message || "";
        var ok = dialog.querySelector("[data-confirm-ok]");
        ok.textContent = options.confirmLabel || label("page_confirm_ok", "Yes, go ahead");
        ok.classList.toggle("m3-button--error", !!options.danger);

        return new Promise(function (resolve) {
            var answered = false;
            var answer = function (value) {
                if (!answered) {
                    answered = true;
                    if (pendingConfirm === answer) {
                        pendingConfirm = null;
                    }
                    resolve(value);
                }
            };
            pendingConfirm = answer;
            dialog.addEventListener(
                "close",
                function () {
                    answer(dialog.returnValue === "confirm");
                    if (opener && typeof opener.focus === "function") {
                        opener.focus();
                    }
                },
                { once: true }
            );
            dialog.returnValue = "";
            dialog.showModal();
            dialog.querySelector("[data-confirm-cancel]").focus();
        });
    };

    // Buttons and links that ask first: data-confirm="message".
    document.addEventListener(
        "click",
        function (event) {
            var trigger = event.target.closest("[data-confirm]");
            if (!trigger || trigger.hasAttribute("data-confirmed") || trigger.hasAttribute("data-read-only-disabled")) {
                return;
            }
            event.preventDefault();
            event.stopImmediatePropagation();
            window
                .pageConfirm({
                    title: trigger.getAttribute("data-confirm-title"),
                    message: trigger.getAttribute("data-confirm"),
                    confirmLabel: trigger.getAttribute("data-confirm-label"),
                    danger: trigger.hasAttribute("data-confirm-danger"),
                })
                .then(function (answer) {
                    if (!answer) {
                        return;
                    }
                    trigger.setAttribute("data-confirmed", "");
                    if (trigger.form && (trigger.type === "submit" || trigger.tagName === "BUTTON")) {
                        if (typeof trigger.form.requestSubmit === "function") {
                            trigger.form.requestSubmit(trigger);
                        } else {
                            trigger.form.submit();
                        }
                    } else {
                        trigger.click();
                    }
                    trigger.removeAttribute("data-confirmed");
                });
        },
        true
    );

    /* ---- Tables (components/page/table) --------------------------
       PageTable draws a server-side list: it asks the list endpoint
       for one page ({offset, limit, sort, order, search}, through the
       page's queryParams function when it has one, plus the named
       fields of the toolbar) and renders {total, rows}. Cells are text
       unless the column says data-escape="false"; formatters get the
       escaped value and return HTML, as the panel's formatters do. */
    var tables = {};

    function PageTable(table) {
        this.table = table;
        this.id = table.id;
        this.root = table.closest("[data-page-table]") || table.parentNode;
        this.body = table.tBodies[0] || table.appendChild(document.createElement("tbody"));
        this.url = table.getAttribute("data-url");
        this.escapeAll = table.getAttribute("data-escape") !== "false";
        this.offset = 0;
        this.limit = Number(table.getAttribute("data-page-size")) || 10;
        this.sort = table.getAttribute("data-sort-name") || "id";
        this.order = table.getAttribute("data-sort-order") || "desc";
        this.searchBox = this.root.querySelector("[data-table-search]");
        this.search = (this.searchBox && searchParam) || "";
        if (this.searchBox && this.search) {
            this.searchBox.value = this.search;
        }
        this.toolbar = table.getAttribute("data-toolbar") ? document.querySelector(table.getAttribute("data-toolbar")) : null;
        this.rows = [];
        this.selected = {};
        this.total = 0;
        this.request = 0;
        this.columns = Array.prototype.map.call(table.tHead.rows[0].cells, function (th) {
            return {
                th: th,
                field: th.getAttribute("data-field"),
                checkbox: th.getAttribute("data-checkbox") === "true",
                sortable: th.getAttribute("data-sortable") === "true",
                formatter: th.getAttribute("data-formatter"),
                events: th.getAttribute("data-events"),
                escape: th.getAttribute("data-escape") !== "false",
                visible: th.getAttribute("data-visible") !== "false",
                title: th.textContent.trim(),
            };
        });
        this.setUpHeader();
        this.bind();
    }

    PageTable.prototype.setUpHeader = function () {
        var self = this;
        this.columns.forEach(function (column) {
            var th = column.th;
            th.setAttribute("scope", "col");
            if (!column.visible) {
                th.hidden = true;
            }
            if (column.checkbox) {
                th.classList.add("page-table__check");
                th.textContent = "";
                th.appendChild(self.checkbox(label("page_select_all_rows", "Select all rows"), "all"));
            } else if (column.sortable && column.field) {
                var button = document.createElement("button");
                button.type = "button";
                button.className = "page-table__sort";
                button.setAttribute("data-sort", column.field);
                while (th.firstChild) {
                    button.appendChild(th.firstChild);
                }
                var arrow = document.createElement("span");
                arrow.className = "page-table__sort-icon";
                arrow.setAttribute("aria-hidden", "true");
                button.appendChild(arrow);
                th.appendChild(button);
            }
        });
        this.markSort();
    };

    PageTable.prototype.checkbox = function (name, value) {
        var wrap = document.createElement("label");
        wrap.className = "m3-checkbox page-table__checkbox";
        var input = document.createElement("input");
        input.type = "checkbox";
        input.className = "m3-checkbox__input";
        input.setAttribute("aria-label", name);
        input.setAttribute("data-row-check", value);
        var control = document.createElement("span");
        control.className = "m3-checkbox__control";
        wrap.appendChild(input);
        wrap.appendChild(control);
        return wrap;
    };

    PageTable.prototype.markSort = function () {
        var self = this;
        this.columns.forEach(function (column) {
            if (!column.sortable) {
                return;
            }
            var on = column.field === self.sort;
            column.th.setAttribute("aria-sort", on ? (self.order === "asc" ? "ascending" : "descending") : "none");
        });
    };

    PageTable.prototype.bind = function () {
        var self = this;
        var timer = null;
        this.table.tHead.addEventListener("click", function (event) {
            var button = event.target.closest("[data-sort]");
            if (!button) {
                return;
            }
            var field = button.getAttribute("data-sort");
            self.order = self.sort === field && self.order === "asc" ? "desc" : "asc";
            self.sort = field;
            self.offset = 0;
            self.markSort();
            self.load();
        });
        this.table.addEventListener("change", function (event) {
            var box = event.target.closest("[data-row-check]");
            if (!box) {
                return;
            }
            var which = box.getAttribute("data-row-check");
            if (which === "all") {
                self.rows.forEach(function (row, index) {
                    self.select(index, box.checked);
                });
                self.body.querySelectorAll("[data-row-check]").forEach(function (rowBox) {
                    rowBox.checked = box.checked;
                });
            } else {
                self.select(Number(which), box.checked);
            }
            self.updateBulkBar();
        });
        if (this.searchBox) {
            this.searchBox.addEventListener("input", function () {
                clearTimeout(timer);
                timer = setTimeout(function () {
                    self.search = self.searchBox.value.trim();
                    self.offset = 0;
                    self.load();
                }, 300);
            });
        }
        this.root.addEventListener("click", function (event) {
            if (event.target.closest('[data-table-refresh="' + self.id + '"]')) {
                self.load();
                return;
            }
            var page = event.target.closest("[data-table-page]");
            if (page && !page.disabled) {
                self.offset = (Number(page.getAttribute("data-table-page")) - 1) * self.limit;
                self.load();
            }
        });
        var size = this.root.querySelector("[data-table-page-size]");
        if (size) {
            size.addEventListener("change", function () {
                self.limit = Number(size.value) || 10;
                self.offset = 0;
                self.load();
            });
        }
    };

    PageTable.prototype.select = function (index, on) {
        var row = this.rows[index];
        if (!row) {
            return;
        }
        var key = row.id !== undefined ? String(row.id) : "row-" + (this.offset + index);
        if (on) {
            this.selected[key] = row;
        } else {
            delete this.selected[key];
        }
    };

    PageTable.prototype.selections = function () {
        var self = this;
        return Object.keys(this.selected).map(function (key) {
            return self.selected[key];
        });
    };

    PageTable.prototype.updateBulkBar = function () {
        var bar = this.root.querySelector('[data-bulk-bar="' + this.id + '"]');
        if (!bar) {
            return;
        }
        var count = this.selections().length;
        bar.hidden = count === 0;
        bar.querySelector("[data-bulk-count]").textContent = label("page_selected_count", ":count selected").replace(":count", String(count));
    };

    PageTable.prototype.params = function () {
        var params = { offset: this.offset, limit: this.limit, sort: this.sort, order: this.order, search: this.search };
        var fn = window[this.table.getAttribute("data-query-params")];
        if (typeof fn === "function") {
            var custom = fn(Object.assign({}, params));
            if (custom && typeof custom === "object") {
                params = Object.assign({}, params, custom);
            }
        }
        if (this.toolbar) {
            this.toolbar.querySelectorAll("[name]").forEach(function (field) {
                if (field.type === "checkbox" || field.type === "radio") {
                    if (field.checked) {
                        params[field.name] = field.value;
                    }
                } else if (!(field.name in params) || field.value !== "") {
                    params[field.name] = field.value;
                }
            });
        }
        return params;
    };

    PageTable.prototype.filtered = function () {
        if (this.search) {
            return true;
        }
        return !!(this.toolbar && Array.prototype.some.call(this.toolbar.querySelectorAll("[data-filter]"), function (field) {
            return field.value !== "";
        }));
    };

    PageTable.prototype.busy = function (on) {
        this.table.setAttribute("aria-busy", on ? "true" : "false");
        var progress = this.root.querySelector("[data-table-progress]");
        if (progress) {
            progress.hidden = !on;
        }
    };

    PageTable.prototype.load = function () {
        var self = this;
        var mine = ++this.request;
        var query = new URLSearchParams();
        var params = this.params();
        Object.keys(params).forEach(function (key) {
            if (params[key] !== undefined && params[key] !== null) {
                query.append(key, params[key]);
            }
        });
        this.busy(true);
        return fetch(this.url + (this.url.indexOf("?") === -1 ? "?" : "&") + query.toString(), {
            credentials: "same-origin",
            headers: { Accept: "application/json", "X-Requested-With": "XMLHttpRequest" },
        })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error("HTTP " + response.status);
                }
                return response.json();
            })
            .then(function (data) {
                if (mine !== self.request) {
                    return;
                }
                self.rows = (data && data.rows) || [];
                self.total = Number((data && data.total) || 0);
                self.selected = {};
                self.render();
                self.root.dispatchEvent(new CustomEvent("page-table:loaded", { bubbles: true, detail: { id: self.id, data: data } }));
            })
            .catch(function () {
                if (mine === self.request) {
                    self.showState("error");
                }
            })
            .finally(function () {
                if (mine === self.request) {
                    self.busy(false);
                }
            });
    };

    PageTable.prototype.cell = function (column, row, index) {
        var td = document.createElement("td");
        if (!column.visible) {
            td.hidden = true;
        }
        if (column.checkbox) {
            td.className = "page-table__check";
            td.appendChild(this.checkbox(label("page_select_row", "Select row :number").replace(":number", String(this.offset + index + 1)), String(index)));
            return td;
        }
        var value = column.field ? row[column.field] : undefined;
        var formatter = column.formatter ? window[column.formatter] : null;
        if (typeof formatter === "function") {
            var input = value;
            if (this.escapeAll && column.escape && typeof value === "string") {
                input = escapeText(value);
            }
            var html = formatter(input, row, index, column.field);
            td.innerHTML = html === undefined || html === null ? "" : String(html);
        } else if (this.escapeAll && column.escape) {
            td.textContent = value === undefined || value === null ? "" : String(value);
        } else {
            td.innerHTML = value === undefined || value === null ? "" : String(value);
        }
        var events = column.events ? window[column.events] : null;
        if (events && typeof events === "object") {
            Object.keys(events).forEach(function (key) {
                var split = key.indexOf(" ");
                var type = split === -1 ? key : key.slice(0, split);
                var selector = split === -1 ? null : key.slice(split + 1);
                td.addEventListener(type, function (event) {
                    var target = selector ? event.target.closest(selector) : td;
                    if (target && td.contains(target)) {
                        events[key].call(target, event, value, row, index);
                    }
                });
            });
        }
        return td;
    };

    PageTable.prototype.render = function () {
        var self = this;
        this.body.textContent = "";
        var all = this.table.tHead.querySelector('[data-row-check="all"]');
        if (all) {
            all.checked = false;
        }
        this.updateBulkBar();
        if (!this.rows.length) {
            this.showState(this.filtered() ? "no-matches" : "empty");
            return;
        }
        this.showState(null);
        var fragment = document.createDocumentFragment();
        this.rows.forEach(function (row, index) {
            var tr = document.createElement("tr");
            self.columns.forEach(function (column) {
                tr.appendChild(self.cell(column, row, index));
            });
            fragment.appendChild(tr);
        });
        this.body.appendChild(fragment);
        this.renderFooter();
    };

    PageTable.prototype.showState = function (state) {
        var box = this.root.querySelector("[data-table-state]");
        var footer = this.root.querySelector("[data-table-footer]");
        var scroll = this.table.closest(".page-table__scroll");
        if (box) {
            box.textContent = "";
        }
        if (!state) {
            if (scroll) {
                scroll.hidden = false;
            }
            return;
        }
        if (scroll) {
            scroll.hidden = true;
        }
        if (footer) {
            footer.hidden = true;
        }
        var template = this.root.querySelector("[data-" + state + '-template="' + this.id + '"]');
        if (box && template) {
            box.appendChild(template.content.cloneNode(true));
        }
    };

    PageTable.prototype.pageButton = function (content, page, options) {
        options = options || {};
        var li = document.createElement("li");
        var button = document.createElement("button");
        button.type = "button";
        button.className = options.icon ? "m3-icon-button" : "m3-pagination__item";
        button.setAttribute("data-table-page", String(page));
        button.setAttribute("aria-controls", this.id);
        if (options.label) {
            button.setAttribute("aria-label", options.label);
        }
        if (options.current) {
            button.setAttribute("aria-current", "page");
        }
        if (options.disabled) {
            button.disabled = true;
        }
        if (options.icon) {
            button.innerHTML = options.icon;
        } else {
            button.textContent = content;
        }
        li.appendChild(button);
        return li;
    };

    PageTable.prototype.renderFooter = function () {
        var footer = this.root.querySelector("[data-table-footer]");
        if (!footer) {
            return;
        }
        footer.hidden = false;
        var pages = Math.max(1, Math.ceil(this.total / this.limit));
        var page = Math.floor(this.offset / this.limit) + 1;
        var from = this.total ? this.offset + 1 : 0;
        var to = Math.min(this.offset + this.rows.length, this.total);
        footer.querySelector("[data-table-range]").textContent = label("page_table_range", ":from–:to of :total")
            .replace(":from", String(from))
            .replace(":to", String(to))
            .replace(":total", String(this.total));
        var list = footer.querySelector("[data-table-pages]");
        list.textContent = "";
        var chevron = function (d) {
            return '<svg class="lc-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="' + d + '"/></svg>';
        };
        var rtl = document.documentElement.dir === "rtl";
        list.appendChild(this.pageButton("", page - 1, { icon: chevron(rtl ? "m9 18 6-6-6-6" : "m15 18-6-6 6-6"), label: label("page_table_previous", "Previous page"), disabled: page <= 1 }));
        var start = Math.max(1, Math.min(page - 2, pages - 4));
        var end = Math.min(pages, start + 4);
        for (var n = start; n <= end; n++) {
            list.appendChild(this.pageButton(String(n), n, { current: n === page, label: label("page_table_page", "Page :number").replace(":number", String(n)) }));
        }
        list.appendChild(this.pageButton("", page + 1, { icon: chevron(rtl ? "m15 18-6-6 6-6" : "m9 18 6-6-6-6"), label: label("page_table_next", "Next page"), disabled: page >= pages }));
    };

    window.PageTable = {
        get: function (id) {
            return tables[id] || null;
        },
        refresh: function (id, options) {
            var table = tables[id];
            if (!table) {
                return null;
            }
            if (options && options.pageNumber) {
                table.offset = (options.pageNumber - 1) * table.limit;
            }
            return table.load();
        },
        selections: function (id) {
            return tables[id] ? tables[id].selections() : [];
        },
    };

    ready(function () {
        document.querySelectorAll("table[data-page-table-init]").forEach(function (element) {
            if (element.id && !tables[element.id]) {
                tables[element.id] = new PageTable(element);
                tables[element.id].load();
            }
        });
    });

    // Filters: a change reloads the table they belong to, from its first page.
    document.addEventListener("change", function (event) {
        var field = event.target.closest("[data-filter]");
        if (field) {
            window.PageTable.refresh(field.getAttribute("data-filter") || "table_list", { pageNumber: 1 });
        }
    });

    // Bulk actions: POST the ticked rows' ids, then refresh.
    document.addEventListener("click", function (event) {
        var button = event.target.closest("[data-bulk-action]");
        if (!button || button.hasAttribute("data-read-only-disabled")) {
            return;
        }
        var bar = button.closest("[data-bulk-bar]");
        var id = bar ? bar.getAttribute("data-bulk-bar") : null;
        var ids = window.PageTable.selections(id).map(function (row) {
            return row.id;
        });
        if (!ids.length) {
            return;
        }
        var send = function () {
            var body = new FormData();
            ids.forEach(function (rowId) {
                body.append("ids[]", rowId);
            });
            fetch(button.getAttribute("data-bulk-action"), {
                method: "POST",
                credentials: "same-origin",
                headers: { Accept: "application/json", "X-CSRF-TOKEN": csrfToken(), "X-Requested-With": "XMLHttpRequest" },
                body: body,
            })
                .then(function (response) {
                    return response.json().catch(function () {
                        return null;
                    });
                })
                .then(function (response) {
                    if (response && response.error === false) {
                        notify("success", response.message);
                        window.PageTable.refresh(id);
                    } else {
                        notify("error", (response && response.message) || label("error_occurred", "Something went wrong."));
                    }
                })
                .catch(function () {
                    notify("error", label("page_form_offline", "The panel could not be reached."));
                });
        };
        var question = button.getAttribute("data-bulk-confirm");
        if (question) {
            window.pageConfirm({ message: question, danger: button.classList.contains("m3-button--error") }).then(function (answer) {
                if (answer) {
                    send();
                }
            });
        } else {
            send();
        }
    });

    /* ---- Form cards --------------------------------------------- */

    /** "terms.0.name" (Laravel's error key) to "terms[0][name]" (the field's name). */
    function fieldName(key) {
        var parts = String(key).split(".");
        return parts.shift() + parts.map(function (part) {
            return "[" + part + "]";
        }).join("");
    }

    function errorBox(field) {
        var ids = (field.getAttribute("aria-describedby") || "").split(/\s+/);
        for (var i = 0; i < ids.length; i++) {
            var el = ids[i] ? document.getElementById(ids[i]) : null;
            if (el && el.classList.contains("page-field-error")) {
                return el;
            }
        }
        var box = document.createElement("p");
        box.className = "m3-text-field__supporting m3-text-field__supporting--error page-field-error";
        box.id = (field.id || "field-" + Math.random().toString(36).slice(2)) + "-error";
        box.hidden = true;
        var wrapper = field.closest(".m3-text-field, .m3-checkbox, .m3-radio, .m3-switch") || field;
        wrapper.insertAdjacentElement("afterend", box);
        field.setAttribute("aria-describedby", ((field.getAttribute("aria-describedby") || "") + " " + box.id).trim());
        return box;
    }

    function showFieldError(field, message) {
        field.setAttribute("aria-invalid", "true");
        var wrapper = field.closest(".m3-text-field");
        if (wrapper) {
            wrapper.classList.add("m3-text-field--error");
        }
        var box = errorBox(field);
        box.textContent = message;
        box.hidden = false;
    }

    function clearFieldError(field) {
        field.removeAttribute("aria-invalid");
        var wrapper = field.closest(".m3-text-field");
        if (wrapper) {
            wrapper.classList.remove("m3-text-field--error");
        }
        var box = errorBox(field);
        box.textContent = "";
        box.hidden = true;
    }

    function clearErrors(form) {
        form.querySelectorAll("[aria-invalid]").forEach(clearFieldError);
        var summary = form.querySelector("[data-form-summary]");
        if (summary) {
            summary.hidden = true;
            summary.textContent = "";
        }
    }

    function showSummary(form, message) {
        var summary = form.querySelector("[data-form-summary]");
        if (summary) {
            summary.textContent = message;
            summary.hidden = false;
        }
    }

    // Inline validation as people go: the browser's own message for required,
    // email, number ranges and patterns, shown under the field.
    function validateField(event) {
        var field = event.target;
        if (!field.form || !field.form.matches("form[data-page-form]") || !field.willValidate || !field.closest("[data-page-field]")) {
            return;
        }
        if (field.checkValidity()) {
            clearFieldError(field);
        } else if (field.value !== "" || field.hasAttribute("aria-invalid")) {
            showFieldError(field, field.validationMessage);
        }
    }
    document.addEventListener("focusout", validateField);
    document.addEventListener("change", validateField);

    /* Drafts: what was typed survives a failed save, an expired session or a reload. */
    function draftKey(form) {
        return "page-form-draft:" + window.location.pathname + ":" + form.getAttribute("data-draft");
    }

    function saveDraft(form) {
        if (!form.hasAttribute("data-draft")) {
            return;
        }
        var values = {};
        Array.prototype.forEach.call(form.elements, function (field) {
            // Never keep passwords, files or the hidden token fields.
            if (!field.name || field.disabled || /^(password|file|hidden|submit|button)$/.test(field.type)) {
                return;
            }
            if ((field.type === "checkbox" || field.type === "radio") && !field.checked) {
                return;
            }
            (values[field.name] = values[field.name] || []).push(field.value);
        });
        try {
            sessionStorage.setItem(draftKey(form), JSON.stringify(values));
        } catch (e) {
            /* Storage full or off: the form still works. */
        }
    }

    function restoreDraft(form) {
        var raw = null;
        try {
            raw = sessionStorage.getItem(draftKey(form));
        } catch (e) {
            return;
        }
        if (!raw) {
            return;
        }
        var values;
        try {
            values = JSON.parse(raw);
        } catch (e) {
            return;
        }
        Object.keys(values).forEach(function (name) {
            var fields = form.querySelectorAll('[name="' + name.replace(/"/g, '\\"') + '"]');
            fields.forEach(function (field, index) {
                if (field.type === "checkbox" || field.type === "radio") {
                    field.checked = values[name].indexOf(field.value) !== -1;
                } else if (field.type !== "file" && values[name][index] !== undefined) {
                    field.value = values[name][index];
                }
            });
        });
    }

    function clearDraft(form) {
        try {
            sessionStorage.removeItem(draftKey(form));
        } catch (e) {
            /* ignore */
        }
    }

    ready(function () {
        document.querySelectorAll("form[data-page-form][data-draft]").forEach(restoreDraft);
    });
    ["input", "change"].forEach(function (type) {
        document.addEventListener(type, function (event) {
            var form = event.target.form;
            if (form && form.matches("form[data-page-form][data-draft]")) {
                saveDraft(form);
            }
        });
    });

    function afterSave(form, response) {
        clearDraft(form);
        if (response && response.message) {
            notify("success", response.message);
        }
        var event = new CustomEvent("page-form:saved", { bubbles: true, cancelable: true, detail: response || {} });
        form.dispatchEvent(event);
        var table = form.getAttribute("data-refresh-table");
        if (table) {
            window.PageTable.refresh(table);
        }
        if (form.hasAttribute("data-reset-on-success")) {
            form.reset();
        }
        var redirect = (response && response.redirect) || form.getAttribute("data-redirect");
        if (redirect && !event.defaultPrevented) {
            window.location.assign(redirect);
        }
    }

    function handleFailure(form, status, body) {
        var message = (body && body.message) || label("error_occurred", "Something went wrong. Nothing was lost; try again.");
        if (status === 419) {
            message = label("page_form_session_expired", "Your session has ended. Sign in again in another tab, then save; what you typed is kept.");
        }
        var errors = body && body.errors;
        var first = null;
        if (errors && typeof errors === "object") {
            Object.keys(errors).forEach(function (key) {
                var name = fieldName(key);
                var field = form.querySelector('[name="' + name + '"]') || form.querySelector('[name="' + name + '[]"]');
                var text = [].concat(errors[key])[0];
                if (field) {
                    showFieldError(field, text);
                    first = first || field;
                }
            });
        }
        showSummary(form, message);
        if (first) {
            first.focus();
        } else {
            notify("error", message);
        }
    }

    document.addEventListener("submit", function (event) {
        var form = event.target;
        if (!form.matches || !form.matches("form[data-page-form]") || event.defaultPrevented) {
            return;
        }
        event.preventDefault();
        clearErrors(form);

        // The browser's checks first, shown inline rather than as bubbles.
        var invalid = Array.prototype.filter.call(form.elements, function (field) {
            return field.willValidate && !field.checkValidity();
        });
        if (invalid.length) {
            invalid.forEach(function (field) {
                showFieldError(field, field.validationMessage);
            });
            showSummary(form, label("page_form_fix_errors", "Some fields need attention."));
            invalid[0].focus();
            return;
        }

        var submit = form.querySelector("[data-submit]");
        if (submit) {
            submit.disabled = true;
            submit.setAttribute("aria-busy", "true");
        }
        form.setAttribute("aria-busy", "true");
        saveDraft(form);

        fetch(form.action, {
            method: "POST",
            credentials: "same-origin",
            headers: { Accept: "application/json", "X-CSRF-TOKEN": csrfToken(), "X-Requested-With": "XMLHttpRequest" },
            body: new FormData(form),
        })
            .then(function (response) {
                return response
                    .json()
                    .catch(function () {
                        return null;
                    })
                    .then(function (body) {
                        if (response.ok && body && body.error === false) {
                            afterSave(form, body);
                        } else {
                            handleFailure(form, response.status, body);
                        }
                    });
            })
            .catch(function () {
                handleFailure(form, 0, { message: label("page_form_offline", "The panel could not be reached. Nothing was lost; check the connection and save again.") });
            })
            .finally(function () {
                form.removeAttribute("aria-busy");
                if (submit) {
                    submit.disabled = false;
                    submit.removeAttribute("aria-busy");
                }
            });
    });
})();
