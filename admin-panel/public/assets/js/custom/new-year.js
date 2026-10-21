/* ============================================================
   New-year wizard (resources/views/academics/new-year.blade.php):
   one step at a time, Back and Next, the step list as a menu,
   focus on each step's heading, and the preview refreshed from
   GET new-year/preview when the carry-forward choices change.
   Saving is page.js's form card. Without this file every step
   shows on one page and the forms still work.
   ============================================================ */
(function ($) {
    "use strict";

    var root = document.querySelector("[data-new-year]");
    if (!root) {
        return;
    }
    var panels = Array.prototype.slice.call(root.querySelectorAll("[data-wizard-step]"));
    var buttons = Array.prototype.slice.call(root.querySelectorAll("[data-wizard-goto]"));
    var current = 1;

    function show(step, focus) {
        var available = panels.map(function (panel) {
            return Number(panel.getAttribute("data-wizard-step"));
        });
        if (available.indexOf(step) === -1) {
            step = available[0];
        }
        current = step;
        panels.forEach(function (panel) {
            panel.hidden = Number(panel.getAttribute("data-wizard-step")) !== step;
        });
        buttons.forEach(function (button) {
            var n = Number(button.getAttribute("data-wizard-goto"));
            button.classList.toggle("is-current", n === step);
            button.classList.toggle("is-done", n < step);
            if (n === step) {
                button.setAttribute("aria-current", "step");
            } else {
                button.removeAttribute("aria-current");
            }
        });
        if (focus) {
            var heading = root.querySelector('[data-wizard-step="' + step + '"] h2');
            if (heading) {
                heading.focus();
            }
        }
        if (step === 4) {
            refreshPreview();
        }
    }

    buttons.forEach(function (button) {
        button.addEventListener("click", function () {
            show(Number(button.getAttribute("data-wizard-goto")), true);
        });
    });
    root.addEventListener("click", function (event) {
        if (event.target.closest("[data-wizard-next]")) {
            show(current + 1, true);
        } else if (event.target.closest("[data-wizard-back]")) {
            show(current - 1, true);
        }
    });

    /* ---- Preview ---------------------------------------------- */
    var previewUrl = root.getAttribute("data-preview-url");
    var previewList = root.querySelector("[data-new-year-preview]");

    function chosen() {
        return Array.prototype.slice
            .call(root.querySelectorAll("[data-carry-option]:checked"))
            .map(function (box) {
                return box.value;
            });
    }

    function item(text, key) {
        var li = document.createElement("li");
        li.textContent = text;
        if (key) {
            li.setAttribute("data-preview-copies", key);
        }
        return li;
    }

    function refreshPreview() {
        if (!previewUrl || !previewList) {
            return;
        }
        var params = new URLSearchParams();
        params.append("year_id", root.getAttribute("data-year-id"));
        chosen().forEach(function (key) {
            params.append("carry[]", key);
        });
        fetch(previewUrl + "?" + params.toString(), {
            credentials: "same-origin",
            headers: { Accept: "application/json", "X-Requested-With": "XMLHttpRequest" },
        })
            .then(function (response) {
                return response.ok ? response.json() : null;
            })
            .then(function (body) {
                if (!body || !body.data) {
                    return;
                }
                var data = body.data;
                var first = previewList.firstElementChild ? previewList.firstElementChild.cloneNode(true) : null;
                previewList.textContent = "";
                if (first) {
                    previewList.appendChild(first);
                }
                data.carry.forEach(function (option) {
                    if (option.copies) {
                        previewList.appendChild(
                            item(
                                trans("new_year_preview_copies").replace(":what", option.label).replace(":count", String(option.source)),
                                option.key
                            )
                        );
                    }
                });
                previewList.appendChild(
                    item(
                        trans("new_year_preview_promotions")
                            .replace(":promoted", String(data.promoted))
                            .replace(":learners", String(data.learners))
                            .replace(":leaving", String(data.leaving))
                    )
                );
            })
            .catch(function () {
                /* The server-rendered preview stays. */
            });
    }

    $(document).on("change", "[data-carry-option]", refreshPreview);

    $(function () {
        show(Number(root.getAttribute("data-start-step")) || 1, false);
    });
})(jQuery);
