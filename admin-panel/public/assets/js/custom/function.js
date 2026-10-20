"use strict";

function trans(label) {
    // return window.languageLabels.hasOwnProperty(label) ? window.languageLabels[label] : label;
    return window?.languageLabels[label] || label;
}

/**
 * Escape a value for safe insertion into HTML text or a quoted HTML attribute.
 * null / undefined become an empty string; numbers and booleans are stringified.
 * Shared by formatter.js, custom-bootstrap-table.js, custom.js and the Blade views
 * (this file is loaded before all of them in layouts/footer_js.blade.php).
 */
function escapeHtml(value) {
    if (value === null || value === undefined) {
        return "";
    }
    return String(value)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#39;")
        .replace(/`/g, "&#96;");
}

/**
 * Return the URL unchanged when it is http(s) or relative (no scheme); any other
 * scheme (javascript:, data:, vbscript:, ...) is replaced by the fallback ("#").
 * The result is NOT HTML-escaped: wrap it in escapeHtml() when building markup.
 */
function safeUrl(url, fallback) {
    if (fallback === undefined) {
        fallback = "#";
    }
    if (url === null || url === undefined) {
        return fallback;
    }
    var str = String(url).trim();
    if (str === "") {
        return fallback;
    }
    // Browsers drop tab/CR/LF anywhere and leading control chars/spaces before reading the scheme.
    var normalized = str.replace(/[\t\n\r]/g, "").replace(/^[\u0000- ]+/, "");
    var scheme = normalized.match(/^([a-zA-Z][a-zA-Z0-9+.\-]*):/);
    if (scheme) {
        var name = scheme[1].toLowerCase();
        return name === "http" || name === "https" ? str : fallback;
    }
    return str;
}

/**
 * $.toast inserts its text with .html(), so toast messages (often server/user supplied)
 * are escaped here. Arrays (rendered by the plugin as a list) are escaped item by item.
 */
function escapeToastText(message) {
    if (Array.isArray(message)) {
        return message.map(escapeHtml);
    }
    return escapeHtml(message);
}

function showErrorToast(message) {
    $.toast({
        text: escapeToastText(message),
        showHideTransition: 'slide',
        icon: 'error',
        loaderBg: '#dc3545', // color ignored — loader bar is tinted per type in content.css
        position: 'top-right',
        hideAfter: 6000,
        allowToastDuplicates: false,
        stack: 5
    });
}

function showSuccessToast(message) {
    $.toast({
        text: escapeToastText(message),
        showHideTransition: 'slide',
        icon: 'success',
        loaderBg: '#198754', // color ignored — loader bar is tinted per type in content.css
        position: 'top-right',
        hideAfter: 4000,
        allowToastDuplicates: false,
        stack: 5
    });
}

function ajaxRequest(type, url, data, beforeSendCallback, successCallback, errorCallback, finalCallback, processData = false) {
    /*
    * @param
    * beforeSendCallback : This function will be executed before Ajax sends its request
    * successCallback : This function will be executed if no Error will occur
    * errorCallback : This function will be executed if some error will occur
    * finalCallback : This function will be executed after all the functions are executed
    */
    $.ajax({
        type: type,
        url: url,
        data: data,
        cache: false,
        processData: processData,
        contentType: false,
        dataType: 'json',
        beforeSend: function () {
            if (beforeSendCallback != null) {
                beforeSendCallback();
            }
        },
        success: function (data) {
            if (!data.error) {
                if (successCallback != null) {
                    successCallback(data);
                }
            } else {
                if (errorCallback != null) {
                    errorCallback(data);
                }
            }

            if (finalCallback != null) {
                finalCallback(data);
            }
        }, error: function (jqXHR, textStatus, errorThrown) {
            let errorMessage = 'An error occurred while processing your request.';

            if (jqXHR.responseJSON) {
                if (jqXHR.responseJSON.message) {
                    errorMessage = jqXHR.responseJSON.message;
                }
                if (jqXHR.responseJSON.validation_errors && jqXHR.responseJSON.validation_errors.length > 0) {
                    errorMessage = 'Validation errors:\n' + jqXHR.responseJSON.validation_errors.join('\n');
                }
            } else if (jqXHR.status === 422) {
                errorMessage = 'Validation failed. Please check your input.';
            } else if (jqXHR.status === 500) {
                errorMessage = 'Server error. Please try again later.';
            } else if (jqXHR.status === 404) {
                errorMessage = 'Requested resource not found.';
            }

            showErrorToast(errorMessage);
            if (finalCallback != null) {
                finalCallback();
            }
        }
    })
}

function formAjaxRequest(type, url, data, formElement, submitButtonElement, successCallback, errorCallback) {
    // To Remove Red Border from the Validation tag.
    formElement.find('.has-danger').removeClass("has-danger");
    formElement.validate();
    if (formElement.valid()) {
        let submitButtonText = submitButtonElement.val();

        function beforeSendCallback() {
            submitButtonElement.val(trans('Please Wait') + '...').attr('disabled', true);
        }

        function mainSuccessCallback(response) {
            showSuccessToast(response.message);
            if (successCallback != null) {
                successCallback(response);
            }
        }

        function mainErrorCallback(response) {
            let errorMessage = response.message || trans('an_error_occurred');

            // Handle validation errors specifically
            if (response.validation_errors && response.validation_errors.length > 0) {
                errorMessage = trans('validation_errors') + ':\n' + response.validation_errors.join('\n');
            }

            showErrorToast(errorMessage);
            if (errorCallback != null) {
                errorCallback(response);
            }
        }

        function finalCallback(response) {
            submitButtonElement.val(submitButtonText).attr('disabled', false);
        }

        ajaxRequest(type, url, data, beforeSendCallback, mainSuccessCallback, mainErrorCallback, finalCallback)
    }
}

function cloneOldCoreSubjectTemplate() {
    let core_subject = $('.edit-core-subject-div:last').clone().show();
    // Remove the error label from the main html so that duplicate error will not be show
    core_subject.find('select').siblings('.error').remove();

    //Change the Name array attribute for jquery validation
    //Add incremental name value
    core_subject.find('.form-control').each(function (key, element) {
        this.name = this.name.replace(/\[(\d+)\]/, function (str, p1) {
            return '[' + (parseInt(p1, 10) + 1) + ']';
        });
        $(element).attr('disabled', false);
    })
    return core_subject;
}

function cloneOldMultipleEventTemplate() {

    let multievent = $('.edit-multiple-event-group-div:last').clone().show();
    multievent.find('.input').siblings('.error').remove();
    multievent.find('.form-control').each(function (key, element) {
        this.name = this.name.replace(/\[(\d+)\]/, function (str, p1) {
            return '[' + (parseInt(p1, 10) + 1) + ']';
        });
        $(element).attr('disabled', false);
    })
    return multievent;
}


function cloneNewCoreSubjectTemplate() {
    let core_subject = $('.core-subject-div:last').clone().show();
    //Change the Name array attribute for jquery validation
    //Add incremental name value
    core_subject.find('select').each(function (key, element) {
        this.name = this.name.replace(/\[(\d+)\]/, function (str, p1) {
            return '[' + (parseInt(p1, 10) + 1) + ']';
        });
        $(element).attr('disabled', false);
    })
    // Remove the error label from the main html so that duplicate error will not be show
    core_subject.find('select').siblings('.error').remove();
    // Swap the Lucide "plus" glyph for "close" as the add button becomes a remove button
    core_subject.find('.add-core-subject .icon-core-add').hide();
    core_subject.find('.add-core-subject .icon-core-remove').show();
    core_subject.find('.add-core-subject').addClass('btn-danger remove-core-subject').removeClass('btn-success add-core-subject');
    return core_subject;
}

function cloneOldElectiveSubjectGroup() {
    let html = $('.edit-elective-subject-group-div:last').clone().show();
    html.children('.subject-list').find('div').slice(1).remove();
    html.find('.form-control').each(function (key, element) {
        this.name = this.name.replace(/\[(\d+)\]/, function (str, p1) {
            return '[' + (parseInt(p1, 10) + 1) + ']';
        });
        $(element).attr('disabled', false);
    })
    return html;
}

function cloneNewElectiveSubjectGroup() {
    let html = $('.elective-subject-group-div:last').clone().show();
    html.children('.subject-list').find('div').slice(1).remove();
    html.find('.form-control').each(function (key, element) {
        this.name = this.name.replace(/\[(\d+)\]/, function (str, p1) {
            return '[' + (parseInt(p1, 10) + 1) + ']';
        });
        $(element).attr('disabled', false);
    })
    return html;
}

function cloneNewElectiveSubject(add_new_elective_subject_button_element) {
    //add-new-elective-subject class button element
    let subject_list = $(add_new_elective_subject_button_element).siblings('.elective-subject-div:last').clone();
    subject_list.find('.remove-elective-subject').show();
    subject_list.children('select').each(function (key, element) {
        this.name = this.name.replace(/\[subject_id]\[(\d+)\]/, function (str, p1) {
            return '[subject_id][' + (parseInt(p1, 10) + 1) + ']';
        });
    })
    subject_list.children('input').each(function (key, element) {
        this.name = this.name.replace(/\[class_subject_id]\[(\d+)\]/, function (str, p1) {
            return '[class_subject_id][' + (parseInt(p1, 10) + 1) + ']';
        });
    })
    subject_list.children('select').siblings('.error').remove()
    subject_list.find('.remove-elective-subject').css('visibility', 'visible');
    let or = $('.or:last').clone();
    return $.merge(or, subject_list);
}

/**
 *
 * @param searchElement
 * @param searchUrl
 * @param data Object
 * @param placeHolder
 * @param templateDesignEvent
 * @param onTemplateSelectEvent
 */
function parentSearch(searchElement, searchUrl, data, placeHolder, templateDesignEvent, onTemplateSelectEvent) {
    //Select2 Ajax Searching Functionality function
    if (!data) {
        data = {};
    }
    $(searchElement).select2({
        tags: true,
        ajax: {
            url: searchUrl,
            dataType: 'json',
            delay: 250,
            cache: true,
            data: function (params) {
                data.email = params.term;
                data.page = params.page;
                return data;
            },
            processResults: function (data, params) {
                params.page = params.page || 1;
                return {
                    results: data.data,
                    pagination: {
                        more: (params.page * 30) < data.total_count
                    }
                };
            }
        },
        placeholder: placeHolder,
        minimumInputLength: 1,
        templateResult: templateDesignEvent,
        templateSelection: onTemplateSelectEvent,
    });
}

function parentSearchSelect2DesignTemplate(repo) {
    /**
     * This function is used in Select2 Searching Functionality
     */
    if (repo.loading) {
        return repo.text;
    }
    if (repo.id && repo.text) {
        var $container = $(
            "<div class='select2-result-repository clearfix'>" +
            "<div class='select2-result-repository__title'></div>" +
            "</div>"
        );
        $container.find(".select2-result-repository__title").text(repo.text);
    } else {
        var $container = $(
            "<div class='select2-result-repository clearfix'>" +
            "<div class='row'>" +
            "<div class='col-1 select2-result-repository__avatar' style='width:20px'>" +
            "<img src='" + escapeHtml(repo.image) + "' class='w-100' onerror='onErrorImage(event)'/>" +
            "</div>" +
            "<div class='col-10'>" +
            "<div class='select2-result-repository__title'></div>" +
            "<div class='select2-result-repository__description'></div>" +
            "</div>" +
            "</div>"
        );

        $container.find(".select2-result-repository__title").text(repo.first_name + " " + repo.last_name);
        $container.find(".select2-result-repository__description").text(repo.email);
    }

    return $container;
}
// Online exam questions and options are edited with Jodit. Maths is written in
// TeX and stored as <span class="math-tex">\(...\)</span>, the format the old
// CKEditor maths plugin used, so existing questions and the apps keep working.
var equationEditors = [];

// jQuery Validate also checks contenteditable elements, such as the editors'
// text area, and fails on them because they have no name.
$.validator.setDefaults({ ignore: ":hidden, [contenteditable]" });

var EQUATION_DELIMITERS = [
    { left: "\\(", right: "\\)", display: false },
    { left: "\\[", right: "\\]", display: true },
];

function createEquationEditors() {
    destroyEquationEditors();
    $("textarea.editor_question, textarea.editor_options").each(function () {
        var editor = Jodit.make(this, {
            // Heights include the toolbar, which wraps in the narrow option columns.
            height: "auto",
            minHeight: $(this).hasClass("editor_question") ? 260 : 180,
            toolbarAdaptive: false,
            showCharsCounter: false,
            showWordsCounter: false,
            showXPathInStatusbar: false,
            askBeforePasteHTML: false,
            askBeforePasteFromWord: false,
            // Double-click edits a formula instead.
            addNewLineOnDBLClick: false,
            buttons: [
                "bold", "italic", "eraser", "|",
                "ul", "ol", "outdent", "indent", "|",
                "paragraph", "align", "brush", "|",
                "link", "image", "table", "symbols", "hr", "|",
                "source", "|",
                {
                    name: "math",
                    text: "TeX",
                    tooltip: trans("Insert maths"),
                    exec: function (editor) {
                        openMathDialog(editor);
                    },
                },
            ],
        });
        editor.e.on(editor.editor, "dblclick", function (e) {
            var math = $(e.target).closest(".math-tex")[0];
            if (math && editor.editor.contains(math)) {
                openMathDialog(editor, math);
            }
        });
        equationEditors.push(editor);
    });
    renderEquations(document);
}

function destroyEquationEditors() {
    syncEquationEditors();
    equationEditors.forEach(function (editor) {
        editor.destruct();
    });
    equationEditors = [];
}

// Copy each editor's content into its textarea before the form is read.
// Jodit's synchronizeValues() is throttled and can miss the last keystrokes,
// so the value is copied directly.
function syncEquationEditors() {
    equationEditors.forEach(function (editor) {
        var value = editor.value;
        // An empty editor holds <p><br></p>; keep the textarea empty so the
        // required checks still apply.
        var body = new DOMParser().parseFromString(value, "text/html").body;
        var empty = !$.trim(body.textContent) && !body.querySelector("img, table, hr");
        editor.element.value = empty ? "" : value;
    });
}

function clearEquationEditors() {
    equationEditors.forEach(function (editor) {
        editor.value = "";
    });
}

function setEquationEditorValue(name, html) {
    var editor = equationEditors.find(function (editor) {
        return editor.element.name === name;
    });
    if (editor) {
        editor.value = html;
    } else {
        $('textarea[name="' + name + '"]').val(html);
    }
}

function openMathDialog(editor, mathElement) {
    var source = mathElement ? $.trim(mathElement.textContent) : "";
    var display = /^\\\[/.test(source);
    var tex = source.replace(/^\\[([]/, "").replace(/\\[)\]]$/, "");
    if (!mathElement) {
        editor.s.save();
    }

    var content = $(
        '<div class="p-3" style="min-width: 320px">' +
            '<textarea class="form-control" rows="3" dir="ltr" placeholder="x^2 + \\frac{a}{b}"></textarea>' +
            '<div class="math-preview mt-3 text-center"></div>' +
            '<div class="text-right mt-3">' +
            '<button type="button" class="btn btn-light btn-sm math-cancel mr-2"></button>' +
            '<button type="button" class="btn btn-primary btn-sm math-insert"></button>' +
            "</div></div>",
    );
    var input = content.find("textarea").val(tex);
    var preview = content.find(".math-preview")[0];
    content.find(".math-cancel").text(trans("Cancel"));
    content.find(".math-insert").text(mathElement ? trans("Update") : trans("Insert"));

    function renderPreview() {
        katex.render(input.val(), preview, { throwOnError: false, displayMode: true });
    }
    input.on("input", renderPreview);
    renderPreview();

    var confirmed = false;
    var dialog = editor.dlg({ closeOnClickOverlay: true });
    dialog.setHeader(trans("Maths (TeX)"));
    dialog.setContent(content[0]);
    dialog.e.on(dialog, "afterClose", function () {
        if (!confirmed && !mathElement) {
            editor.s.restore();
        }
        dialog.destruct();
    });
    dialog.open();
    input.trigger("focus");

    content.find(".math-cancel").on("click", function () {
        dialog.close();
    });
    content.find(".math-insert").on("click", function () {
        var value = $.trim(input.val());
        var formula = display ? "\\[" + value + "\\]" : "\\(" + value + "\\)";
        confirmed = true;
        dialog.close();
        if (mathElement) {
            if (value) {
                mathElement.textContent = formula;
            } else {
                mathElement.remove();
            }
            editor.synchronizeValues();
            return;
        }
        editor.s.restore();
        if (!value) {
            return;
        }
        // Don't put a formula inside another one.
        var current = editor.s.current();
        var inside = current && $(current).closest(".math-tex")[0];
        if (inside && editor.editor.contains(inside)) {
            editor.s.setCursorAfter(inside);
        }
        var span = editor.createInside.element("span");
        span.className = "math-tex";
        span.textContent = formula;
        editor.s.insertNode(span);
    });
}

// Render the maths in read-only question and option previews.
function renderEquations(root) {
    $(root)
        .find(".equation-editor-inline")
        .each(function () {
            var element = this;
            if (element.tagName === "TEXTAREA") {
                // Some previews arrive as a textarea holding the question HTML.
                var preview = $('<div class="equation-editor-inline"></div>').append(sanitizedNodes(element.value));
                $(element).replaceWith(preview);
                element = preview[0];
            }
            if (!element.dataset.mathRendered) {
                renderMathInElement(element, { delimiters: EQUATION_DELIMITERS, throwOnError: false });
                element.dataset.mathRendered = "1";
            }
        });
}

// Parse HTML without running it, dropping scripts and event handlers.
function sanitizedNodes(html) {
    var body = new DOMParser().parseFromString(html, "text/html").body;
    $(body).find("script, iframe, object, embed").remove();
    $(body)
        .find("*")
        .each(function () {
            for (var i = this.attributes.length - 1; i >= 0; i--) {
                var attribute = this.attributes[i];
                if (/^on/i.test(attribute.name) || /^\s*javascript:/i.test(attribute.value)) {
                    this.removeAttribute(attribute.name);
                }
            }
        });
    return Array.from(body.childNodes);
}
function convertTimeRange(timeRange) {

    var formattedRange = timeRange.replace(' to ', ' - ');
    return formattedRange;
}

function convertDateRange(dateRange) {
    return dateRange.replace(' to ', ' - ');
}

function formatDate(dateString) {
    const date = new Date(dateString);
    const day = date.getDate().toString().padStart(2, '0');
    const month = (date.getMonth() + 1).toString().padStart(2, '0');
    const year = date.getFullYear();
    return `${day}-${month}-${year}`;
}


function classValidation() {
    $('.subject').each(function (index, subject) {
        $(subject).rules("remove", "noDuplicateValues");
        $(subject).rules("add", {
            "noDuplicateValues": {
                class: "subject",
                group: $(subject).attr('data-group'),
                value: $(subject).find("option:selected").text()
            }
        });
    })
}
