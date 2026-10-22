"use strict";

/*
 * The public website's behaviour (web/master.blade.php loads it after jQuery, the
 * Material 3 library's m3.js, Owl Carousel, jQuery Validate, Flatpickr,
 * custom/function.js and custom/validate.js). Dialogs, menus and snackbars come
 * from m3.js (window.M3); the labels below come from window.siteLabels.
 */

var siteLabels = window.siteLabels || {};
var siteIsRtl = function () {
    return document.documentElement.dir === "rtl";
};

$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
});

// ── Snackbars ───────────────────────────────────────────────────────────
// M3.snackbar sets the message as text, so server and user text is safe as is.
function siteMessage(message) {
    return Array.isArray(message) ? message.join("\n") : (message === null || message === undefined ? "" : String(message));
}

function showErrorToast(message) {
    M3.snackbar({ message: siteMessage(message), type: 'error', duration: 5000, closeLabel: siteLabels.close });
}

function showSuccessToast(message) {
    M3.snackbar({ message: siteMessage(message), type: 'success', duration: 5000, closeLabel: siteLabels.close });
}

// ── AJAX forms ──────────────────────────────────────────────────────────
function ajaxRequest(type, url, data, beforeSendCallback, successCallback, errorCallback, finalCallback, processData = false) {
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
            if (jqXHR.responseJSON) {
                showErrorToast(jqXHR.responseJSON.message);
            }
            if (finalCallback != null) {
                finalCallback();
            }
        }
    })
}

function formAjaxRequest(type, url, data, formElement, submitButtonElement, successCallback, errorCallback) {
    formElement.validate();
    if (formElement.valid()) {
        let submitButtonText = submitButtonElement.val();

        function beforeSendCallback() {
            submitButtonElement.val('Please Wait...').attr('disabled', true);
        }

        function mainSuccessCallback(response) {
            showSuccessToast(response.message);
            if (successCallback != null) {
                successCallback(response);
            }
        }

        function mainErrorCallback(response) {
            showErrorToast(response.message);
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

// ── jQuery Validate on M3 fields ────────────────────────────────────────
// The error text goes under the field (after the .m3-text-field label, never inside
// it, where it would stop the label floating), under a radio or checkbox group's
// fieldset, or under a file field's row. The field itself gets .error and
// aria-invalid, which m3.css draws in the error colour.
function siteErrorPlacement(label, element) {
    if (!label.text()) {
        return;
    }
    var field = element.closest('.site-file-field');
    if (field.length) {
        label.insertAfter(field.find('.site-file-field__row'));
    } else if (element.closest('.m3-fieldset').length) {
        label.insertAfter(element.closest('.m3-fieldset'));
    } else if (element.closest('.m3-text-field').length) {
        label.insertAfter(element.closest('.m3-text-field'));
    } else {
        label.insertAfter(element);
    }
}

function siteFieldsFor(element) {
    var $element = $(element);
    if ($element.is(':radio, :checkbox')) {
        return $element.closest('form').find('[name="' + element.name + '"]');
    }
    if ($element.is(':file')) {
        return $element.add($element.closest('.site-file-field').find('.file-upload-info'));
    }
    return $element;
}

function siteHighlight(element, errorClass) {
    siteFieldsFor(element).addClass(errorClass || 'error').attr('aria-invalid', 'true');
}

function siteUnhighlight(element, errorClass) {
    siteFieldsFor(element).removeClass(errorClass || 'error').removeAttr('aria-invalid');
}

$.validator.setDefaults({
    errorPlacement: siteErrorPlacement,
    highlight: siteHighlight,
    unhighlight: siteUnhighlight
});

$('#create-form,.create-form').on('submit', function (e) {
    e.preventDefault();
    let formElement = $(this);
    let submitButtonElement = $(this).find(':submit');
    let url = $(this).attr('action');
    let data = new FormData(this);

    function successCallback() {
        formElement[0].reset();
        if (typeof grecaptcha !== 'undefined' && formElement.find('.g-recaptcha').length) {
            grecaptcha.reset();
        }
    }

    formAjaxRequest('POST', url, data, formElement, submitButtonElement, successCallback);
})

// ── File fields (web/partials/file-field.blade.php) ──────────────────────
$(document).on('click', '.file-upload-browse', function () {
    $(this).closest('.site-file-field').find('.file-upload-default').trigger('click');
});

$(document).on('change', '.file-upload-default', function () {
    $(this).closest('.site-file-field').find('.file-upload-info').val($(this).val().replace(/C:\\fakepath\\/i, ''));
});

// ── Registration: parents or a guardian ─────────────────────────────────
$(document).on('change', 'input[name="parent_guardian_type"]', function () {
    var selectedValue = $(this).val();
    if (selectedValue === 'Guardian') {
        $('#guardian_div').show();
        $('#guardian_div input, #guardian_div select').prop('disabled', false);
        $('#guardian_image').prop('disabled', true);
        $('#parents_div').hide();
        $('#parents_div input, #parents_div select').prop('disabled', true);
    } else if (selectedValue === 'Parent') {
        $('#parents_div').show();
        $('#parents_div input, #parents_div select').prop('disabled', false);
        $('#father_image').prop('disabled', true);
        $('#mother_image').prop('disabled', true);
        $('#guardian_div').hide();
        $('#guardian_div input, #guardian_div select').prop('disabled', true);
    }
});

$(document).ready(function () {
    $('input[name="parent_guardian_type"]:checked').trigger('change');
});

// ── Date fields (Flatpickr) ──────────────────────────────────────────────
// Keeps dd-mm-yyyy values and classes. First focus initialises then opens;
// subsequent focuses are handled by Flatpickr. The browser's autocomplete
// dropdown (previously entered values) is switched off.
$(function () {
    document.querySelectorAll('.datepicker-popup, .datepicker-popup-no-future, .datepicker-popup-no-past').forEach(function (el) {
        el.setAttribute('autocomplete', 'off');
    });
});
$('body').on('focus', '.datepicker-popup, .datepicker-popup-no-future, .datepicker-popup-no-past', function () {
    this.setAttribute('autocomplete', 'off');
    if (!this._flatpickr) {
        var opts = { dateFormat: 'd-m-Y', allowInput: true, disableMobile: true };
        if (this.classList.contains('datepicker-popup-no-future')) {
            opts.maxDate = 'today';
        } else if (this.classList.contains('datepicker-popup-no-past')) {
            opts.minDate = 'today';
        }
        flatpickr(this, opts).open();
    }
});

$('#student-registration-form').on('submit', function (e) {

    e.preventDefault();
    let formElement = $(this);
    let submitButtonElement = $(this).find(':submit');
    let url = $(this).attr('action');
    let data = new FormData(this);

    function successCallback() {
        window.location.reload();
    }

    formAjaxRequest('POST', url, data, formElement, submitButtonElement, successCallback);
})

// ── Carousels (Owl Carousel) ─────────────────────────────────────────────
// data-site-carousel="hero": the home page's full-width slider, with its own
// arrows drawn as M3 icon buttons. data-site-carousel="strip": a row of cards
// moved by the [data-site-carousel-prev/next] icon buttons beside it.
var siteArrow = function (direction) {
    var path = direction === 'prev' ? 'm15 18-6-6 6-6' : 'm9 18 6-6-6-6';
    return '<svg class="m3-icon site-flip-rtl" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" ' +
        'stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="' + path + '"/></svg>';
};

$(function () {
    var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    $('[data-site-carousel="hero"]').each(function () {
        var $carousel = $(this);
        $carousel.owlCarousel({
            items: 1,
            loop: true,
            rtl: siteIsRtl(),
            autoplay: !reduceMotion,
            autoplayTimeout: 2000,
            autoplayHoverPause: true,
            dots: false,
            nav: true,
            navElement: 'button type="button"',
            navClass: ['owl-prev m3-icon-button site-hero__arrow', 'owl-next m3-icon-button site-hero__arrow'],
            navText: [siteArrow('prev'), siteArrow('next')],
        });
        $carousel.find('.owl-prev').attr('aria-label', siteLabels.previous || 'Previous');
        $carousel.find('.owl-next').attr('aria-label', siteLabels.next || 'Next');
        // Keyboard users: hold still while focus is inside.
        $carousel.on('focusin', function () {
            $carousel.trigger('stop.owl.autoplay');
        }).on('focusout', function () {
            if (!reduceMotion) {
                $carousel.trigger('play.owl.autoplay', [2000]);
            }
        });
    });

    $('[data-site-carousel="strip"]').each(function () {
        var $carousel = $(this);
        var owl = $carousel.owlCarousel({
            loop: false,
            rtl: siteIsRtl(),
            margin: 20,
            nav: false,
            dots: false,
            responsive: {
                0: { items: 1 },
                600: { items: 3 },
                1000: { items: 5 },
            },
        });
        var $slider = $carousel.closest('.site-slider');
        $slider.find('[data-site-carousel-prev]').on('click', function () {
            owl.trigger('prev.owl.carousel');
        });
        $slider.find('[data-site-carousel-next]').on('click', function () {
            owl.trigger('next.owl.carousel');
        });
    });
});

// ── Video dialog (web/partials/video-dialog.blade.php) ───────────────────
$(document).on('click', '.site-video-thumb', function () {
    var dialog = document.getElementById('videoModal');
    if (!dialog) {
        return;
    }
    var title = this.getAttribute('data-video-title');
    var heading = document.getElementById('videoModalTitle');
    if (!heading.hasAttribute('data-default')) {
        heading.setAttribute('data-default', heading.textContent);
    }
    heading.textContent = title || heading.getAttribute('data-default');
    document.getElementById('videoIframe').src = this.getAttribute('data-video-src') || '';
    M3.dialog.open(dialog);
});

// Closing the dialog stops the video ("close" doesn't bubble, so listen in the capture phase).
document.addEventListener('close', function (event) {
    if (event.target && event.target.id === 'videoModal') {
        document.getElementById('videoIframe').src = '';
    }
}, true);

// ── Event dialog (home page) ─────────────────────────────────────────────
// "View details" on an event card fills #eventModal from the button's
// data-event-* attributes; a multi-day event lists its programme by day.
function formatTime(timeString) {
    const [hours, minutes] = timeString.split(':');
    const formattedHours = parseInt(hours) % 12 || 12;
    const period = parseInt(hours) < 12 ? 'AM' : 'PM';
    return `${formattedHours}:${minutes} ${period}`;
}

function formatEventDate(dateString) {
    const date = new Date(dateString);
    const day = date.getDate().toString().padStart(2, '0');
    const month = (date.getMonth() + 1).toString().padStart(2, '0');
    const year = date.getFullYear();
    return `${day}-${month}-${year}`;
}

$(document).on('click', '.site-event__more', function () {
    var dialog = document.getElementById('eventModal');
    if (!dialog) {
        return;
    }
    var button = this;
    document.getElementById('fullEventTitle').textContent = button.getAttribute('data-event-title') || '';
    document.getElementById('fullEventDate').textContent = button.getAttribute('data-event-date') || '';
    document.getElementById('fullEventDescription').textContent = button.getAttribute('data-event-description') || '';

    var image = document.getElementById('eventImage');
    var imageUrl = button.getAttribute('data-event-image');
    if (imageUrl) {
        image.src = imageUrl;
        image.hidden = false;
    } else {
        image.removeAttribute('src');
        image.hidden = true;
    }

    var programme = document.getElementById('multiEventDetails');
    var template = document.getElementById('eventProgrammeItem');
    programme.innerHTML = '';
    var events = [];
    try {
        events = JSON.parse(button.getAttribute('data-event-programme') || '[]') || [];
    } catch (e) {
        events = [];
    }
    var currentDay = 0;
    var prevDate = null;
    events.forEach(function (event) {
        if (!event.date) {
            return;
        }
        var item = template.content.cloneNode(true);
        var day = item.querySelector('.site-programme__day');
        if (event.date !== prevDate) {
            currentDay++;
            prevDate = event.date;
            day.textContent = (siteLabels.day || 'Day') + ' ' + currentDay;
        } else {
            day.remove();
        }
        item.querySelector('.site-programme__time').textContent =
            formatTime(event.start_time) + ' ' + (siteLabels.to || 'To') + ' ' + formatTime(event.end_time);
        item.querySelector('.site-programme__title').textContent = event.title || '';
        item.querySelector('.site-programme__description').textContent = event.description || '';
        item.querySelector('.site-programme__date span').textContent = formatEventDate(event.date);
        programme.appendChild(item);
    });

    M3.dialog.open(dialog);
});

// ── Photo viewer (photo album page) ──────────────────────────────────────
(function () {
    var dialog = document.getElementById('imageModal');
    var data = document.getElementById('photoViewerData');
    if (!dialog || !data) {
        return;
    }
    var slides = [];
    try {
        slides = JSON.parse(data.textContent) || [];
    } catch (e) {
        slides = [];
    }
    var image = document.getElementById('photoViewerImage');
    var count = document.getElementById('photoViewerCount');
    var current = 0;

    function show(index) {
        if (!slides.length) {
            return;
        }
        current = (index + slides.length) % slides.length;
        image.src = slides[current].src;
        image.alt = slides[current].alt;
        count.textContent = '(' + (current + 1) + ' / ' + slides.length + ')';
        dialog.querySelectorAll('[data-photo-step]').forEach(function (button) {
            button.hidden = slides.length < 2;
        });
    }

    document.addEventListener('click', function (event) {
        var thumb = event.target.closest('[data-photo-index]');
        if (thumb) {
            show(parseInt(thumb.getAttribute('data-photo-index'), 10) || 0);
            M3.dialog.open(dialog);
            return;
        }
        var step = event.target.closest('[data-photo-step]');
        if (step && dialog.contains(step)) {
            show(current + parseInt(step.getAttribute('data-photo-step'), 10));
        }
    });

    // Arrow keys move through the album (the visual direction, so mirrored in RTL).
    dialog.addEventListener('keydown', function (event) {
        if (event.key !== 'ArrowLeft' && event.key !== 'ArrowRight') {
            return;
        }
        var forward = siteIsRtl() ? 'ArrowLeft' : 'ArrowRight';
        event.preventDefault();
        show(current + (event.key === forward ? 1 : -1));
    });
})();

// ── "Load more" (photo album and videos pages) ───────────────────────────
// Reveals the list's hidden items ([data-site-more]) and moves focus to the first.
$(document).on('click', '[data-site-load-more]', function () {
    var list = document.getElementById(this.getAttribute('data-site-load-more'));
    if (!list) {
        return;
    }
    var more = list.querySelectorAll('[data-site-more][hidden]');
    more.forEach(function (item) {
        item.hidden = false;
    });
    this.closest('.site-load-more').hidden = true;
    var first = more.length && more[0].querySelector('button, a');
    if (first) {
        first.focus();
    }
});
