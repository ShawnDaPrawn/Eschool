/*
 * NavigateEd Material 3 for the web: behaviour for m3.css.
 *
 * No dependencies. Wires itself up from data attributes through delegated
 * listeners, so markup added later (AJAX, templates) works too:
 *
 *   data-m3-dialog-open="id"   opens <dialog id> as a modal
 *   data-m3-dialog-close       closes the dialog it sits in
 *   data-m3-menu="id"          toggles the popover menu with that id, anchored here
 *   role="tablist" .m3-tabs    tabs switch the panels named by aria-controls
 *   data-m3-scroll on a top app bar: tinted once the page scrolls under it
 *
 * And exposes window.M3: dialog.open/close, menu.open/close,
 * snackbar(message | options), confirm(options) → Promise<boolean>.
 */
(function () {
    "use strict";

    var isRtl = function () {
        return document.documentElement.dir === "rtl" || document.body.dir === "rtl";
    };

    // ── Dialogs ──────────────────────────────────────────────────────────

    function openDialog(dialog) {
        if (!dialog || dialog.open) return;
        dialog.showModal();
        dialog.dispatchEvent(new CustomEvent("m3:open", { bubbles: true }));
    }

    function closeDialog(dialog, returnValue) {
        if (!dialog || !dialog.open) return;
        dialog.close(returnValue);
    }

    // Clicking the scrim (the dialog element itself, outside its content box)
    // closes it, unless the dialog has data-m3-persistent.
    function onDialogClick(event) {
        var dialog = event.target;
        if (!(dialog instanceof HTMLDialogElement) || dialog.hasAttribute("data-m3-persistent")) return;
        var rect = dialog.getBoundingClientRect();
        var inside = event.clientX >= rect.left && event.clientX <= rect.right &&
            event.clientY >= rect.top && event.clientY <= rect.bottom;
        if (!inside) closeDialog(dialog, "dismiss");
    }

    // ── Menus ────────────────────────────────────────────────────────────

    var supportsPopover = typeof HTMLElement !== "undefined" && HTMLElement.prototype.hasOwnProperty("popover");
    var openFallbackMenu = null;

    function menuItems(menu) {
        return Array.prototype.slice.call(
            menu.querySelectorAll(".m3-menu__item:not([disabled]):not([aria-disabled='true'])")
        );
    }

    // Place the menu under its trigger, aligned to the trigger's start edge,
    // flipping above or inward when it would leave the viewport.
    function positionMenu(menu, trigger) {
        var gap = 4;
        var margin = 8;
        var t = trigger.getBoundingClientRect();
        var m = menu.getBoundingClientRect();
        var top = t.bottom + gap;
        if (top + m.height > window.innerHeight - margin && t.top - gap - m.height > margin) {
            top = t.top - gap - m.height;
        }
        var left = isRtl() ? t.right - m.width : t.left;
        left = Math.max(margin, Math.min(left, window.innerWidth - m.width - margin));
        menu.style.top = Math.max(margin, top) + "px";
        menu.style.left = left + "px";
    }

    function openMenu(menu, trigger, focusFirst) {
        if (!menu) return;
        menu._m3Trigger = trigger;
        if (supportsPopover && menu.hasAttribute("popover")) {
            if (!menu.matches(":popover-open")) menu.showPopover();
        } else {
            if (openFallbackMenu && openFallbackMenu !== menu) closeMenu(openFallbackMenu);
            menu.classList.add("is-open");
            openFallbackMenu = menu;
        }
        if (trigger) {
            trigger.setAttribute("aria-expanded", "true");
            positionMenu(menu, trigger);
        }
        if (focusFirst) {
            var items = menuItems(menu);
            if (items.length) items[0].focus();
        }
    }

    function closeMenu(menu) {
        if (!menu) return;
        if (supportsPopover && menu.hasAttribute("popover")) {
            if (menu.matches(":popover-open")) menu.hidePopover();
        } else {
            menu.classList.remove("is-open");
            if (openFallbackMenu === menu) openFallbackMenu = null;
        }
        if (menu._m3Trigger) menu._m3Trigger.setAttribute("aria-expanded", "false");
    }

    function isMenuOpen(menu) {
        return supportsPopover && menu.hasAttribute("popover")
            ? menu.matches(":popover-open")
            : menu.classList.contains("is-open");
    }

    function onMenuKeydown(event) {
        var menu = event.target.closest(".m3-menu");
        if (!menu) return;
        var items = menuItems(menu);
        var index = items.indexOf(document.activeElement);
        var next = null;
        if (event.key === "ArrowDown") next = items[(index + 1) % items.length];
        else if (event.key === "ArrowUp") next = items[(index - 1 + items.length) % items.length];
        else if (event.key === "Home") next = items[0];
        else if (event.key === "End") next = items[items.length - 1];
        else if (event.key === "Escape" || event.key === "Tab") {
            closeMenu(menu);
            if (event.key === "Escape" && menu._m3Trigger) menu._m3Trigger.focus();
            return;
        }
        if (next) {
            event.preventDefault();
            next.focus();
        }
    }

    // ── Tabs ─────────────────────────────────────────────────────────────

    function selectTab(tab, focus) {
        var list = tab.closest("[role='tablist']");
        if (!list) return;
        var tabs = list.querySelectorAll("[role='tab']");
        Array.prototype.forEach.call(tabs, function (other) {
            var selected = other === tab;
            other.setAttribute("aria-selected", selected ? "true" : "false");
            other.tabIndex = selected ? 0 : -1;
            var panelId = other.getAttribute("aria-controls");
            var panel = panelId && document.getElementById(panelId);
            if (panel) panel.hidden = !selected;
        });
        if (focus) tab.focus();
        list.dispatchEvent(new CustomEvent("m3:tabchange", { bubbles: true, detail: { tab: tab } }));
    }

    function onTabKeydown(event) {
        var tab = event.target.closest("[role='tab']");
        if (!tab) return;
        var tabs = Array.prototype.slice.call(tab.closest("[role='tablist']").querySelectorAll("[role='tab']"));
        var i = tabs.indexOf(tab);
        var forward = isRtl() ? "ArrowLeft" : "ArrowRight";
        var back = isRtl() ? "ArrowRight" : "ArrowLeft";
        var target = null;
        if (event.key === forward) target = tabs[(i + 1) % tabs.length];
        else if (event.key === back) target = tabs[(i - 1 + tabs.length) % tabs.length];
        else if (event.key === "Home") target = tabs[0];
        else if (event.key === "End") target = tabs[tabs.length - 1];
        if (target) {
            event.preventDefault();
            selectTab(target, true);
        }
    }

    // ── Snackbars ────────────────────────────────────────────────────────

    var host = null;

    function snackbarHost() {
        if (!host || !document.body.contains(host)) {
            host = document.createElement("div");
            host.className = "m3-snackbar-host";
            host.setAttribute("role", "status");
            host.setAttribute("aria-live", "polite");
            document.body.appendChild(host);
        }
        return host;
    }

    /**
     * Show a snackbar. options: { message, type: success|error|warning|info,
     * actionText, onAction, duration (ms, 0 = until closed) }.
     */
    function snackbar(options) {
        if (typeof options === "string") options = { message: options };
        options = options || {};
        var type = options.type || "";
        var el = document.createElement("div");
        el.className = "m3-snackbar" + (type ? " m3-snackbar--" + type : "");
        if (type) {
            var icon = document.createElement("span");
            icon.className = "m3-snackbar__icon";
            icon.setAttribute("aria-hidden", "true");
            el.appendChild(icon);
        }
        var text = document.createElement("span");
        text.className = "m3-snackbar__message";
        text.textContent = options.message || "";
        el.appendChild(text);

        var dismiss = function () {
            if (el.classList.contains("is-leaving")) return;
            el.classList.add("is-leaving");
            setTimeout(function () {
                el.remove();
            }, 200);
        };

        if (options.actionText) {
            var action = document.createElement("button");
            action.type = "button";
            action.className = "m3-button m3-button--text m3-snackbar__action";
            action.textContent = options.actionText;
            action.addEventListener("click", function () {
                if (options.onAction) options.onAction();
                dismiss();
            });
            el.appendChild(action);
        }
        var close = document.createElement("button");
        close.type = "button";
        close.className = "m3-icon-button m3-icon-button--small";
        close.setAttribute("aria-label", options.closeLabel || "Close");
        close.innerHTML = "<svg class='m3-icon' viewBox='0 0 24 24' aria-hidden='true'><path fill='currentColor' d='M19 6.4 17.6 5 12 10.6 6.4 5 5 6.4 10.6 12 5 17.6 6.4 19 12 13.4 17.6 19 19 17.6 13.4 12z'/></svg>";
        close.addEventListener("click", dismiss);
        el.appendChild(close);

        snackbarHost().appendChild(el);
        var duration = options.duration !== undefined ? options.duration : type === "error" ? 6000 : 4000;
        if (duration > 0) setTimeout(dismiss, duration);
        return { close: dismiss };
    }

    // ── Confirm dialog ───────────────────────────────────────────────────

    /**
     * An M3 alert dialog. options: { title, message, confirmText, cancelText,
     * destructive }. Resolves true when confirmed, false otherwise.
     */
    function confirmDialog(options) {
        options = options || {};
        return new Promise(function (resolve) {
            var dialog = document.createElement("dialog");
            dialog.className = "m3-dialog";
            dialog.setAttribute("role", "alertdialog");
            var id = "m3-confirm-" + Date.now();
            dialog.setAttribute("aria-labelledby", id);
            var form = document.createElement("form");
            form.method = "dialog";
            form.className = "m3-dialog__form";

            var headline = document.createElement("h2");
            headline.className = "m3-dialog__headline";
            headline.id = id;
            headline.textContent = options.title || "";
            form.appendChild(headline);

            if (options.message) {
                var content = document.createElement("div");
                content.className = "m3-dialog__content";
                content.textContent = options.message;
                form.appendChild(content);
            }

            var actions = document.createElement("div");
            actions.className = "m3-dialog__actions";
            var cancel = document.createElement("button");
            cancel.className = "m3-button m3-button--text";
            cancel.value = "cancel";
            cancel.textContent = options.cancelText || "Cancel";
            var ok = document.createElement("button");
            ok.className = "m3-button m3-button--filled" + (options.destructive ? " m3-button--error" : "");
            ok.value = "confirm";
            ok.textContent = options.confirmText || "OK";
            actions.appendChild(cancel);
            actions.appendChild(ok);
            form.appendChild(actions);
            dialog.appendChild(form);

            dialog.addEventListener("close", function () {
                resolve(dialog.returnValue === "confirm");
                dialog.remove();
            });
            document.body.appendChild(dialog);
            openDialog(dialog);
            cancel.focus();
        });
    }

    // ── Wiring ───────────────────────────────────────────────────────────

    document.addEventListener("click", function (event) {
        var opener = event.target.closest("[data-m3-dialog-open]");
        if (opener) {
            event.preventDefault();
            openDialog(document.getElementById(opener.getAttribute("data-m3-dialog-open")));
            return;
        }
        var closer = event.target.closest("[data-m3-dialog-close]");
        if (closer) {
            event.preventDefault();
            closeDialog(closer.closest("dialog"), closer.getAttribute("data-m3-dialog-close") || "close");
            return;
        }
        var menuTrigger = event.target.closest("[data-m3-menu]");
        if (menuTrigger) {
            event.preventDefault();
            var menu = document.getElementById(menuTrigger.getAttribute("data-m3-menu"));
            if (menu) {
                if (isMenuOpen(menu)) closeMenu(menu);
                else openMenu(menu, menuTrigger, event.detail === 0);
            }
            return;
        }
        // Choosing an item closes its menu.
        var item = event.target.closest(".m3-menu__item");
        if (item) {
            closeMenu(item.closest(".m3-menu"));
        } else if (openFallbackMenu && !event.target.closest(".m3-menu")) {
            closeMenu(openFallbackMenu);
        }
        var tab = event.target.closest("[role='tab']");
        if (tab && tab.closest(".m3-tabs")) {
            selectTab(tab, false);
        }
        if (event.target instanceof HTMLDialogElement) onDialogClick(event);
    });

    document.addEventListener("keydown", function (event) {
        if (event.target.closest(".m3-menu")) onMenuKeydown(event);
        else if (event.target.closest(".m3-tabs [role='tab']")) onTabKeydown(event);
        else if (event.key === "Escape" && openFallbackMenu) closeMenu(openFallbackMenu);
        else if ((event.key === "ArrowDown") && event.target.closest("[data-m3-menu]")) {
            var trigger = event.target.closest("[data-m3-menu]");
            event.preventDefault();
            openMenu(document.getElementById(trigger.getAttribute("data-m3-menu")), trigger, true);
        }
    });

    // Popovers close themselves on outside click and Escape; keep the
    // trigger's aria-expanded in step.
    document.addEventListener("toggle", function (event) {
        var menu = event.target;
        if (menu.classList && menu.classList.contains("m3-menu") && event.newState === "closed" && menu._m3Trigger) {
            menu._m3Trigger.setAttribute("aria-expanded", "false");
        }
    }, true);

    // Menus stay anchored while the page scrolls or resizes.
    var reposition = function () {
        document.querySelectorAll(".m3-menu").forEach(function (menu) {
            if (menu._m3Trigger && isMenuOpen(menu)) positionMenu(menu, menu._m3Trigger);
        });
    };
    window.addEventListener("resize", reposition);
    window.addEventListener("scroll", reposition, true);

    // Top app bar: tint once content scrolls under it.
    var bars = [];
    var onScroll = function () {
        var scrolled = window.scrollY > 0;
        bars.forEach(function (bar) {
            bar.classList.toggle("is-scrolled", scrolled);
        });
    };

    function init() {
        bars = Array.prototype.slice.call(document.querySelectorAll(".m3-top-app-bar[data-m3-scroll]"));
        if (bars.length) {
            window.addEventListener("scroll", onScroll, { passive: true });
            onScroll();
        }
        // Tabs: roving tabindex from the initial selection.
        document.querySelectorAll(".m3-tabs[role='tablist']").forEach(function (list) {
            var selected = list.querySelector("[role='tab'][aria-selected='true']") || list.querySelector("[role='tab']");
            if (selected) selectTab(selected, false);
        });
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", init);
    } else {
        init();
    }

    window.M3 = {
        dialog: { open: openDialog, close: closeDialog },
        menu: { open: openMenu, close: closeMenu },
        tabs: { select: selectTab },
        snackbar: snackbar,
        confirm: confirmDialog,
    };
})();
