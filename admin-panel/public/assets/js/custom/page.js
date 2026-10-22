/* ============================================================
   Page pattern behaviour for resources/views/components/page/:
   tables with bulk actions and designed empty states, filters,
   form cards that save without a reload, and the confirm dialog.
   Loaded by layouts/master.blade.php after panel.js. Plain jQuery
   and fetch, no build step. See wiki/UI-Standards.md,
   "Admin panel page pattern".
   ============================================================ */
(function ($) {
    "use strict";

    function csrfToken() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute("content") : "";
    }

    function label(key, fallback) {
        return typeof trans === "function" && window.languageLabels && window.languageLabels[key] ? trans(key) : fallback;
    }

    /* ---- A list opened with ?search= starts filtered ------------
       Global search links to list pages with the name it found; any
       bootstrap-table with a search box picks it up. This runs before
       bootstrap-table's own start-up (on DOM ready). */
    (function prefillSearch() {
        var search = new URLSearchParams(window.location.search).get("search");
        if (!search) {
            return;
        }
        document.querySelectorAll('table[data-toggle="table"][data-search="true"], table[data-page-table-init][data-search="true"]').forEach(function (table) {
            if (!table.hasAttribute("data-search-text")) {
                table.setAttribute("data-search-text", search);
            }
        });
    })();

    /* ---- Names for controls the plugins draw ----------------------
       bootstrap-table's row checkboxes and Select2's search fields
       come without an accessible name; give them one on every page,
       old or new, as they appear. */
    function labelText(select) {
        if (!select) {
            return "";
        }
        var label = select.id ? document.querySelector('label[for="' + select.id + '"]') : null;
        return (label && label.textContent.replace(/\*/g, "").trim()) || select.getAttribute("aria-label") || "";
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

    $(function () {
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

    /* ---- Confirm dialog ---------------------------------------- */
    var confirmDialog = null;

    window.pageConfirm = function (options) {
        options = options || {};
        var $dialog = $("#page-confirm");
        if (!$dialog.length) {
            return Promise.resolve(window.confirm(options.message || ""));
        }
        if (confirmDialog) {
            confirmDialog.resolve(false);
        }
        var opener = document.activeElement;
        $dialog.find("#page-confirm-title").text(options.title || label("page_confirm_title", "Are you sure?"));
        $dialog.find("#page-confirm-message").text(options.message || "");
        var $ok = $dialog.find("[data-confirm-ok]");
        $ok.text(options.confirmLabel || label("page_confirm_ok", "Confirm"));
        $ok.toggleClass("btn-danger", !!options.danger).toggleClass("btn-theme", !options.danger);

        return new Promise(function (resolve) {
            var answered = false;
            confirmDialog = {
                resolve: function (value) {
                    if (!answered) {
                        answered = true;
                        resolve(value);
                    }
                },
            };
            $ok.off("click.pageConfirm").on("click.pageConfirm", function () {
                confirmDialog.resolve(true);
                $dialog.modal("hide");
            });
            $dialog.off("hidden.bs.modal.pageConfirm").on("hidden.bs.modal.pageConfirm", function () {
                confirmDialog.resolve(false);
                confirmDialog = null;
                if (opener && typeof opener.focus === "function") {
                    opener.focus();
                }
            });
            $dialog.off("shown.bs.modal.pageConfirm").on("shown.bs.modal.pageConfirm", function () {
                $dialog.find("[data-confirm-cancel]").trigger("focus");
            });
            $dialog.modal("show");
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
                .then(function (ok) {
                    if (!ok) {
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

    /* ---- Tables ------------------------------------------------- */
    function templateHtml(selector) {
        var template = document.querySelector(selector);
        return template ? template.innerHTML : "";
    }

    function updateBulkBar($table) {
        var id = $table.attr("id");
        var $bar = $('[data-bulk-bar="' + id + '"]');
        if (!$bar.length) {
            return;
        }
        var count = $table.bootstrapTable("getSelections").length;
        $bar.prop("hidden", count === 0);
        $bar.find("[data-bulk-count]").text(
            label("page_selected_count", ":count selected").replace(":count", String(count))
        );
    }

    function initTable(table) {
        var $table = $(table);
        var id = $table.attr("id");
        $table.bootstrapTable({
            formatNoMatches: function () {
                // Nothing found for a search: say so. Nothing at all: say what to do first.
                var searching = (this && this.searchText) || $(this && this.toolbar ? this.toolbar : []).find("[data-filter]").filter(function () {
                    return $(this).val();
                }).length;
                return templateHtml(
                    searching ? '[data-no-matches-template="' + id + '"]' : '[data-empty-template="' + id + '"]'
                );
            },
        });
        $table.on("check.bs.table uncheck.bs.table check-all.bs.table uncheck-all.bs.table load-success.bs.table", function () {
            updateBulkBar($table);
        });
    }

    $(function () {
        document.querySelectorAll("table[data-page-table-init]").forEach(initTable);
    });

    // Filters: a change reloads the table they belong to.
    $(document).on("change", "[data-filter]", function () {
        var target = $(this).attr("data-filter") || "table_list";
        $("#" + target).bootstrapTable("refresh", { pageNumber: 1 });
    });

    // Bulk actions: POST the ticked rows' ids, then refresh.
    $(document).on("click", "[data-bulk-action]", function () {
        var $button = $(this);
        var $table = $("#" + $button.closest("[data-bulk-bar]").attr("data-bulk-bar"));
        var ids = $table.bootstrapTable("getSelections").map(function (row) {
            return row.id;
        });
        if (!ids.length) {
            return;
        }
        var send = function () {
            var body = new FormData();
            ids.forEach(function (id) {
                body.append("ids[]", id);
            });
            $.ajax({
                type: "POST",
                url: $button.attr("data-bulk-action"),
                data: body,
                processData: false,
                contentType: false,
                dataType: "json",
                headers: { "X-CSRF-TOKEN": csrfToken() },
            })
                .done(function (response) {
                    if (response && response.error === false) {
                        showSuccessToast(response.message);
                        $table.bootstrapTable("refresh");
                    } else {
                        showErrorToast((response && response.message) || label("error_occurred", "Something went wrong."));
                    }
                })
                .fail(function (xhr) {
                    showErrorToast((xhr.responseJSON && xhr.responseJSON.message) || label("error_occurred", "Something went wrong."));
                });
        };
        var question = $button.attr("data-bulk-confirm");
        if (question) {
            window.pageConfirm({ message: question, danger: $button.hasClass("btn-danger") }).then(function (ok) {
                if (ok) {
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

    function errorBox(form, field) {
        var id = field.getAttribute("aria-describedby");
        var box = null;
        if (id) {
            id.split(/\s+/).some(function (part) {
                var el = document.getElementById(part);
                if (el && el.classList.contains("invalid-feedback")) {
                    box = el;
                    return true;
                }
                return false;
            });
        }
        if (!box) {
            box = document.createElement("div");
            box.className = "invalid-feedback";
            box.id = (field.id || "field-" + Math.random().toString(36).slice(2)) + "-error";
            field.insertAdjacentElement("afterend", box);
            field.setAttribute("aria-describedby", ((field.getAttribute("aria-describedby") || "") + " " + box.id).trim());
        }
        return box;
    }

    function showFieldError(form, field, message) {
        field.classList.add("is-invalid");
        field.setAttribute("aria-invalid", "true");
        var box = errorBox(form, field);
        box.textContent = message;
        box.style.display = "block";
    }

    function clearFieldError(form, field) {
        field.classList.remove("is-invalid");
        field.removeAttribute("aria-invalid");
        var box = errorBox(form, field);
        box.textContent = "";
        box.style.display = "";
    }

    function clearErrors(form) {
        form.querySelectorAll("[aria-invalid]").forEach(function (field) {
            clearFieldError(form, field);
        });
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
    $(document).on("blur change", "[data-page-form] .form-control", function () {
        var form = this.form;
        if (!form || !this.willValidate) {
            return;
        }
        if (this.checkValidity()) {
            clearFieldError(form, this);
        } else if (this.value !== "" || this.hasAttribute("aria-invalid")) {
            showFieldError(form, this, this.validationMessage);
        }
    });

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
        var values = JSON.parse(raw);
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

    $(function () {
        document.querySelectorAll("form[data-page-form][data-draft]").forEach(restoreDraft);
    });
    $(document).on("input change", "form[data-page-form][data-draft]", function () {
        saveDraft(this);
    });

    function afterSave(form, response) {
        clearDraft(form);
        if (response && response.message) {
            showSuccessToast(response.message);
        }
        var event = new CustomEvent("page-form:saved", { bubbles: true, detail: response || {} });
        form.dispatchEvent(event);
        var table = form.getAttribute("data-refresh-table");
        if (table) {
            $("#" + table).bootstrapTable("refresh");
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
                    showFieldError(form, field, text);
                    first = first || field;
                }
            });
        }
        showSummary(form, message);
        if (first) {
            first.focus();
        } else if (typeof showErrorToast === "function") {
            showErrorToast(message);
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
                showFieldError(form, field, field.validationMessage);
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
})(jQuery);
