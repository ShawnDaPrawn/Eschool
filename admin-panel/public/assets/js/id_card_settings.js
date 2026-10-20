$(function() {
    'use strict';

    // ---- Server-injected data (defined inline in the Blade view as window.idcSettings) ----
    var CFG = window.idcSettings || {};
    var savedBackground = CFG.savedBackground || '';
    var savedSignature = CFG.savedSignature || '';
    var schoolName = CFG.schoolName || 'School Name';
    var schoolAddress = CFG.schoolAddress || '';
    var logo1Url = CFG.logo1Url || '';
    var logo2Url = CFG.logo2Url || '';
    var fallbackLogo = CFG.fallbackLogo || '';
    var savedSelectedFields = CFG.savedSelectedFields || [];
    var savedStaffSelectedFields = CFG.savedStaffSelectedFields || [];

    var verticalLogo = logo1Url || fallbackLogo;
    var horizontalLogo = logo2Url || logo1Url || fallbackLogo;

    // i18n strings used in the renderer
    var T = CFG.T || {};

    // Neutral placeholder avatar (inline SVG) so the preview never 404s.
    var AVATAR = 'data:image/svg+xml;utf8,' + encodeURIComponent(
        '<svg xmlns="http://www.w3.org/2000/svg" width="120" height="120"><rect width="120" height="120" fill="#dfe3ea"/>' +
        '<circle cx="60" cy="46" r="24" fill="#b9c0cc"/><path d="M20 116c0-24 18-38 40-38s40 14 40 38z" fill="#b9c0cc"/></svg>'
    );

    // One structured sample student, reused consistently. Order follows the PDF template.
    var SAMPLE = {
        name: 'Mark Pawley',
        gr_no: '10024',
        class_section: 'X - A | English',
        roll_no: '1',
        dob: '2005-01-01',
        blood_group: 'B+',
        gender: 'Male',
        session_year: '2026-27',
        guardian_name: 'Jhon Pawley',
        guardian_contact: '8000000000',
        address: '123, Main Street, City'
    };
    // label + sample-value key, in PDF render order (student_name handled separately as the title).
    var FIELD_DEFS = [{
            key: 'gr_no',
            label: 'GR No.'
        },
        {
            key: 'class_section',
            label: 'Class Section'
        },
        {
            key: 'roll_no',
            label: 'Roll No.'
        },
        {
            key: 'dob',
            label: 'Date of Birth'
        },
        {
            key: 'blood_group',
            label: 'Blood Group'
        },
        {
            key: 'gender',
            label: 'Gender'
        },
        {
            key: 'session_year',
            label: 'Session Year'
        },
        {
            key: 'guardian_name',
            label: 'Father/Guardian Name'
        },
        {
            key: 'guardian_contact',
            label: 'Father/Guardian Contact'
        },
        {
            key: 'address',
            label: 'Address'
        }
    ];

    // Staff sample data + field defs (staff_name handled separately as the title, like student_name).
    var STAFF_SAMPLE = {
        name: 'John Smith',
        designation: 'Teacher',
        qualification: 'M.Sc, B.Ed',
        email: 'john@school.com',
        mobile: '8000000000',
        dob: '1990-01-01',
        gender: 'Male',
        address: '123, Main Street, City'
    };
    var STAFF_FIELD_DEFS = [{
            key: 'designation',
            label: 'Designation'
        },
        {
            key: 'qualification',
            label: 'Qualification'
        },
        {
            key: 'email',
            label: 'Email'
        },
        {
            key: 'mobile',
            label: 'Mobile'
        },
        {
            key: 'dob',
            label: 'Date of Birth'
        },
        {
            key: 'gender',
            label: 'Gender'
        },
        {
            key: 'address',
            label: 'Address'
        }
    ];

    // ---- Card-type (student vs staff) helpers ----
    function currentCardType() {
        return $('#id-card-setting').find('input[name="id_card_type"]:checked').val() || 'student';
    }

    function defsFor(type) {
        return type === 'staff' ? STAFF_FIELD_DEFS : FIELD_DEFS;
    }

    function sampleFor(type) {
        return type === 'staff' ? STAFF_SAMPLE : SAMPLE;
    }

    function nameKeyFor(type) {
        return type === 'staff' ? 'staff_name' : 'student_name';
    }

    var MM_TO_PX = 3.78;
    // Recommended/default card size (CR80 bank-card standard), per orientation.
    var LAYOUT_DEFAULTS = {
        vertical: {
            w: 53.98,
            h: 85.6
        },
        horizontal: {
            w: 85.6,
            h: 53.98
        }
    };
    // Design canvas the card CONTENT is authored against. Content is scaled FROM here TO the
    // actual card so a small card isn't dominated by oversized photo/fonts (mirrors the PDF $s).
    var CONTENT_REF = {
        vertical: {
            w: 95,
            h: 155
        },
        horizontal: {
            w: 140,
            h: 100
        }
    };
    // Dimension validation (mm), per orientation — keep in sync with the controller + input min/max.
    // The LONG side is 70–100 mm, the SHORT side 45–65 mm; aspect (long/short) 1.45–1.65.
    var DIM = {
        vertical: {
            wMin: 45,
            wMax: 65,
            hMin: 70,
            hMax: 100
        },
        horizontal: {
            wMin: 70,
            wMax: 100,
            hMin: 45,
            hMax: 65
        },
        arMin: 1.45,
        arMax: 1.65,
        std: 'CR80'
    };

    // Push the current orientation's mm bounds onto the number inputs (used by the clamp handler).
    function applyDimBounds(layout) {
        var b = DIM[layout] || DIM.horizontal;
        $('#card_width').attr({
            min: b.wMin,
            max: b.wMax
        });
        $('#card_height').attr({
            min: b.hMin,
            max: b.hMax
        });
    }

    var $preview = $('#id-card-preview');
    var pickedBackground = null; // data-URL of a newly chosen background, before save
    var pickedSignature = null; // data-URL of a newly chosen signature, before save
    var lastW = 0,
        lastH = 0; // last card px size, for resize re-fit without re-render

    function esc(s) {
        return $('<div>').text(s == null ? '' : s).html();
    }

    // ---- Field selection helpers (scoped to the active card-type grid) ----
    function fieldSet(type) {
        return $('.idc-field-set[data-card-type="' + (type || currentCardType()) + '"]');
    }

    function getSelectedFields(type) {
        return fieldSet(type).find('.idc-field-cb:checked').map(function() {
            return this.value;
        }).get();
    }

    function updateFieldCount() {
        var $set = fieldSet();
        var total = $set.find('.idc-field-cb').length;
        var checked = $set.find('.idc-field-cb:checked').length;
        $('#idc-field-count').text(checked + ' / ' + total);
        // keep the is-checked styling in sync across BOTH grids (hidden one still persists)
        $('.idc-field-cb').each(function() {
            $(this).closest('.idc-check').toggleClass('is-checked', this.checked);
        });
    }

    // ---- Segmented pill sync ----
    function syncPills(name) {
        $('input[name="' + name + '"]').each(function() {
            $(this).closest('.idc-pill').toggleClass('is-active', this.checked);
        });
    }

    // ---- Dimensions ----
    function currentLayout() {
        return $('#id-card-setting').find('input[name="layout_type"]:checked').val() || 'vertical';
    }

    function updateDimensions(layout, applyDefaults) {
        var d = LAYOUT_DEFAULTS[layout] || LAYOUT_DEFAULTS.vertical;
        if (applyDefaults) {
            $('#card_width').val(d.w);
            $('#card_height').val(d.h);
        }
        // Show the recommended size + the standard it follows (CR80 = bank-card / aspect ~1.586).
        $('#idc-dim-reco span').text(T.recommended + ': ' + d.w + ' × ' + d.h + ' ' + T.mm + ' (' + DIM
            .std + ')');
    }

    // ---- Build the info rows from the checked fields ----
    function buildRows(selected, type) {
        var sample = sampleFor(type);
        return defsFor(type).filter(function(f) {
                return selected.indexOf(f.key) !== -1;
            })
            .map(function(f) {
                return '<div class="idc-row"><span class="idc-row-label">' + esc(f.label) +
                    ' :</span><span class="idc-row-val">' + esc(sample[f.key]) + '</span></div>';
            }).join('');
    }

    // ---- Card renderers ----
    function renderVerticalCard(o) {
        return '<div class="idc-card is-vertical is-' + o.profile + '" dir="ltr" style="width:' + o.wPx +
            'px;height:' + o.hPx + 'px;">' +
            o.bgEl +
            '<div class="idc-vhead" style="background:' + esc(o.headerColor) + ';color:' + esc(o
                .textColor) + ';">' + esc(schoolName) + '</div>' +
            '<div class="idc-vlogo"><img class="idc-logo" src="' + esc(verticalLogo) +
            '" alt="" onerror="this.style.display=\'none\'"></div>' +
            '<div class="idc-body">' +
            '<div class="idc-photo-wrap"><img class="idc-photo" src="' + AVATAR + '" alt=""></div>' +
            o.nameEl +
            '<div class="idc-fields">' + o.rowsHtml + '</div>' +
            '</div>' +
            o.signEl +
            '<div class="idc-footer" style="background:' + esc(o.footerColor) + ';color:' + esc(o
                .textColor) + ';">' + esc(schoolAddress) + '</div>' +
            '</div>';
    }

    function renderHorizontalCard(o) {
        return '<div class="idc-card is-horizontal is-' + o.profile + '" dir="ltr" style="width:' + o.wPx +
            'px;height:' + o.hPx + 'px;">' +
            o.bgEl +
            '<div class="idc-hhead" style="background:' + esc(o.headerColor) + ';color:' + esc(o
                .textColor) + ';">' +
            '<img class="idc-logo" src="' + esc(horizontalLogo) +
            '" alt="" onerror="this.style.display=\'none\'">' +
            '<span class="idc-school-name">' + esc(schoolName) + '</span>' +
            '</div>' +
            '<div class="idc-body">' +
            '<aside class="idc-photo-col"><div class="idc-photo-wrap"><img class="idc-photo" src="' +
            AVATAR + '" alt=""></div></aside>' +
            '<div class="idc-info-col">' + o.nameEl + '<div class="idc-fields">' + o.rowsHtml +
            '</div></div>' +
            '</div>' +
            o.signEl +
            '<div class="idc-footer" style="background:' + esc(o.footerColor) + ';color:' + esc(o
                .textColor) + ';">' + esc(schoolAddress) + '</div>' +
            '</div>';
    }

    // ---- Scale the rendered card to fit the stage, preserving aspect ratio (never upscale) ----
    function fitToStage() {
        var stage = document.querySelector('.idc-preview-stage');
        if (!stage || !lastW || !lastH) {
            return;
        }
        var cs = window.getComputedStyle(stage);
        var availW = stage.clientWidth - parseFloat(cs.paddingLeft) - parseFloat(cs.paddingRight);
        var availH = stage.clientHeight - parseFloat(cs.paddingTop) - parseFloat(cs.paddingBottom);
        // Fit-to-container: scale the card to fill the stage on its tighter axis, preserving aspect
        // ratio. Allow UPSCALING (no 1 cap) so small standard sizes — e.g. CR80 85.6×54mm — don't
        // render miniature; cap the zoom so a tiny config can't blow up unreasonably.
        var IDC_MAX_SCALE = 3;
        var scale = Math.min(availW / lastW, availH / lastH);
        if (!isFinite(scale) || scale <= 0) {
            scale = 1;
        }
        scale = Math.min(scale, IDC_MAX_SCALE);
        $preview[0].style.transform = 'scale(' + scale + ')';
    }

    // ---- Pin the preview to the viewport (desktop) so ONLY the config column scrolls ----
    // Uses position:fixed (via .idc-pinned) which is immune to the overflow:hidden ancestor
    // that breaks position:sticky. We mirror the preview column's left/width onto the fixed
    // card so it stays aligned, and clear it again below the lg breakpoint (stacked layout).
    var IDC_PIN_TOP_MIN = 86; // fixed navbar (70px) + gap

    // Vertical anchor: start the pinned card just BELOW the page-header (aligned with the
    // columns row), then slide it up to pin under the navbar as the page scrolls — sticky-like.
    function syncPinTop() {
        var card = document.querySelector('.idc-preview-col .card.idc-pinned');
        var configCol = document.querySelector('.idc-config-col');
        if (!card || !configCol) {
            return;
        }
        var top = Math.max(IDC_PIN_TOP_MIN, configCol.getBoundingClientRect().top);
        card.style.top = top + 'px';
        card.style.maxHeight = (window.innerHeight - top - 16) + 'px';
    }

    function pinPreview() {
        var col = document.querySelector('.idc-preview-col');
        var card = col && col.querySelector('.card');
        if (!card) {
            return;
        }
        if (window.matchMedia('(min-width: 992px)').matches) {
            var rect = col.getBoundingClientRect();
            var cs = window.getComputedStyle(col);
            var padL = parseFloat(cs.paddingLeft) || 0;
            var padR = parseFloat(cs.paddingRight) || 0;
            card.classList.add('idc-pinned');
            card.style.left = (rect.left + padL) + 'px';
            card.style.width = (rect.width - padL - padR) + 'px';
            syncPinTop();
        } else {
            card.classList.remove('idc-pinned');
            card.style.left = '';
            card.style.width = '';
            card.style.top = '';
            card.style.maxHeight = '';
        }
        fitToStage();
    }

    // ---- Master render ----
    function updatePreview() {
        var headerColor = $('#header_color').val() || '#07a5a5';
        var footerColor = $('#footer_color').val() || '#56cc99';
        var textColor = $('#header_footer_text_color').val() || '#ffffff';
        var layout = currentLayout();
        var profile = $('#id-card-setting').find('input[name="profile_image_style"]:checked').val() ||
            'round';
        var d0 = LAYOUT_DEFAULTS[layout] || LAYOUT_DEFAULTS.vertical;
        var widthMm = parseFloat($('#card_width').val()) || d0.w;
        var heightMm = parseFloat($('#card_height').val()) || d0.h;
        var cardType = currentCardType();
        var selected = getSelectedFields(cardType);

        // dims chip + orientation tag
        $('#idc-dim-w').text(widthMm);
        $('#idc-dim-h').text(heightMm);
        $('#idc-orient').html('&middot; ' + (widthMm < heightMm ? T.portrait : T.landscape));

        // Content scale vs the design canvas (mirrors the PDF's $s). Render the card at the design
        // size — where the authored content px look right — using the REAL aspect ratio, then let
        // fitToStage() zoom it to the stage. Keeps small CR80 cards from showing giant content.
        var ref = CONTENT_REF[layout] || CONTENT_REF.vertical;
        var contentScale = Math.min(widthMm / ref.w, heightMm / ref.h);
        if (!isFinite(contentScale) || contentScale <= 0) {
            contentScale = 1;
        }
        var wPx = (widthMm * MM_TO_PX) / contentScale;
        var hPx = (heightMm * MM_TO_PX) / contentScale;
        lastW = wPx;
        lastH = hPx;

        var bg = pickedBackground || savedBackground;
        var sig = pickedSignature || savedSignature;
        var trans = parseFloat($('#background_transparency').val());
        if (isNaN(trans)) { trans = 80; }
        var bgOpacity = (100 - trans) / 100;
        var bgSize = parseFloat($('#background_size').val());
        if (isNaN(bgSize)) { bgSize = 80; }
        // watermark width as a % of the card; height auto keeps aspect, card clips any overflow.
        var bgStyle = 'opacity:' + bgOpacity + ';width:' + bgSize + '%;max-width:' + bgSize +
            '%;max-height:none;height:auto;';
        var o = {
            headerColor: headerColor,
            footerColor: footerColor,
            textColor: textColor,
            profile: profile,
            wPx: wPx,
            hPx: hPx,
            rowsHtml: buildRows(selected, cardType),
            nameEl: selected.indexOf(nameKeyFor(cardType)) !== -1 ?
                '<div class="idc-name" style="color:' + esc(headerColor) + ';">' + esc(sampleFor(cardType).name) +
                '</div>' : '',
            bgEl: bg ? '<img class="idc-bg" src="' + esc(bg) + '" alt="" style="' + bgStyle + '">' : '',
            signEl: sig ?
                '<div class="idc-signature"><img src="' + esc(sig) + '" alt=""><span>Signature</span></div>' :
                ''
        };

        $preview.html(layout === 'horizontal' ? renderHorizontalCard(o) : renderVerticalCard(o));
        fitToStage();
    }

    // ---- Colour pickers: event-driven (asColorPicker fires asColorPicker::change on the input) ----
    // NOTE: .color-picker is auto-initialised globally in custom.js — we only LISTEN here, no re-init, no polling.
    var rafId = null;

    function scheduleRender() {
        if (rafId) {
            return;
        }
        rafId = window.requestAnimationFrame(function() {
            rafId = null;
            updatePreview();
        });
    }
    $('.color-picker').on(
        'asColorPicker::change asColorPicker::apply asColorPicker::clear change keyup input',
        scheduleRender);

    // ---- Event bindings (event-driven; no setInterval) ----
    $(document).on('change', '#id-card-setting input[name="layout_type"]', function() {
        var layout = this.value;
        syncPills('layout_type');
        applyDimBounds(layout); // flip the input mm bounds (portrait <-> landscape)
        updateDimensions(layout, true); // auto-fill recommended dims on an explicit layout switch
        updatePreview();
    });
    $(document).on('change', '#id-card-setting input[name="profile_image_style"]', function() {
        syncPills('profile_image_style');
        updatePreview();
    });
    // Card-type toggle: swap which field grid is visible + which sample data the preview uses.
    $(document).on('change', '#id-card-setting input[name="id_card_type"]', function() {
        var type = this.value;
        syncPills('id_card_type');
        $('.idc-field-set').each(function() {
            $(this).toggleClass('d-none', this.getAttribute('data-card-type') !== type);
        });
        $('#idc-field-set-label').text(type === 'staff' ? (T.staff || 'Staff') : (T.student || 'Student'));
        updateFieldCount();
        updatePreview();
    });
    // Live preview while typing; clamp to the valid mm range on blur (form is novalidate).
    $('#card_width, #card_height').on('input', updatePreview);
    $('#card_width, #card_height').on('change', function() {
        var min = +(this.min || 30),
            max = +(this.max || 300),
            v = parseFloat(this.value);
        if (!isNaN(v)) {
            this.value = Math.min(max, Math.max(min, v));
        }
        updatePreview();
    });
    $(document).on('change', '.idc-field-cb', function() {
        updateFieldCount();
        updatePreview();
    });

    // Background file chosen -> live data-URL preview + reveal the controls (thumb/slider)
    $('#background_image_input').on('change', function() {
        var file = this.files && this.files[0];
        if (!file) {
            return;
        }
        var reader = new FileReader();
        reader.onload = function(e) {
            pickedBackground = e.target.result;
            $('#idc-bg-thumb').attr('src', pickedBackground);
            $('#idc-bg-controls').show();
            updatePreview();
        };
        reader.readAsDataURL(file);
    });

    // Signature file chosen -> live data-URL preview (mirrors the background flow)
    $('#signature_input').on('change', function() {
        var file = this.files && this.files[0];
        if (!file) {
            return;
        }
        var reader = new FileReader();
        reader.onload = function(e) {
            pickedSignature = e.target.result;
            updatePreview();
        };
        reader.readAsDataURL(file);
    });

    // Transparency slider -> live watermark opacity
    $('#background_transparency').on('input change', function() {
        $('#idc-bg-trans-val').text(this.value + '%');
        updatePreview();
    });

    // Size slider -> live watermark size (% of card width)
    $('#background_size').on('input change', function() {
        $('#idc-bg-size-val').text(this.value + '%');
        updatePreview();
    });

    // Remove background. A freshly-PICKED (unsaved) image is discarded client-side here;
    // for a SAVED image we let the event bubble to the global .student-id-card-settings
    // handler in custom.js (Swal confirm -> DELETE /remove-image -> reload).
    $('#idc-bg-remove').on('click', function(e) {
        if (pickedBackground) {
            e.preventDefault();
            e.stopImmediatePropagation();
            pickedBackground = null;
            $('#background_image_input').val('');
            $('#bg-image-name').val('');
            if (savedBackground) {
                $('#idc-bg-thumb').attr('src', savedBackground);
            } else {
                $('#idc-bg-controls').hide();
            }
            updatePreview();
        }
    });

    // Debounced re-pin + re-fit on viewport / sidebar changes (re-measures the column)
    var resizeTimer = null;
    $(window).on('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(pinPreview, 150);
    });
    $(window).on('load', pinPreview);
    $(document).on('click', '.sidebar-toggler, .navbar-toggler, [data-toggle="minimize"]', function() {
        setTimeout(pinPreview, 350);
    });
    // Slide the pinned preview's top on scroll (sticky-like) — rAF-throttled, top only.
    var pinTicking = false;
    $(window).on('scroll', function() {
        if (pinTicking) {
            return;
        }
        pinTicking = true;
        window.requestAnimationFrame(function() {
            pinTicking = false;
            syncPinTop();
        });
    });

    // Client-side guard: block submit + toast on no field selected OR out-of-range dimensions
    // (width 70–100, height 45–65, width > height, aspect 1.45–1.65). Mirrors the controller.
    function dimensionsValid() {
        var w = parseFloat($('#card_width').val());
        var h = parseFloat($('#card_height').val());
        if (isNaN(w) || isNaN(h) || w <= 0 || h <= 0) {
            return false;
        }
        var layout = currentLayout();
        var b = DIM[layout] || DIM.horizontal;
        var inRange = w >= b.wMin && w <= b.wMax && h >= b.hMin && h <= b.hMax;
        // landscape must be wider than tall; portrait taller than wide
        var orientOk = layout === 'horizontal' ? w > h : h > w;
        var ar = Math.max(w, h) / Math.min(w, h);
        return inRange && orientOk && ar >= DIM.arMin && ar <= DIM.arMax;
    }
    // Activate a card-type tab (used to surface the grid that fails validation).
    function showCardType(type) {
        $('#id-card-setting input[name="id_card_type"][value="' + type + '"]').prop('checked', true)
            .trigger('change');
    }
    $('#create-btn').on('click', function(e) {
        // Both field sets persist & are required server-side — validate each, surfacing the empty one.
        var emptyType = getSelectedFields('student').length === 0 ? 'student' :
            (getSelectedFields('staff').length === 0 ? 'staff' : null);
        if (emptyType) {
            e.preventDefault();
            e.stopImmediatePropagation();
            if (emptyType !== currentCardType()) {
                showCardType(emptyType);
            }
            if (typeof showErrorToast === 'function') {
                showErrorToast(T.selectOne);
            }
        } else if (!dimensionsValid()) {
            e.preventDefault();
            e.stopImmediatePropagation();
            if (typeof showErrorToast === 'function') {
                showErrorToast(T.badDims);
            }
        }
    });

    // Reset: native reset reverts inputs to saved values; re-sync custom controls + redraw.
    $('#idc-reset').on('click', function() {
        pickedBackground = null;
        pickedSignature = null;
        setTimeout(function() {
            // re-tick checkboxes to the saved selection, per grid (student vs staff)
            fieldSet('student').find('.idc-field-cb').each(function() {
                this.checked = savedSelectedFields.indexOf(this.value) !== -1;
            });
            fieldSet('staff').find('.idc-field-cb').each(function() {
                this.checked = savedStaffSelectedFields.indexOf(this.value) !== -1;
            });
            // re-sync card-type pills + grid visibility to the reverted radio
            var resetType = currentCardType();
            $('.idc-field-set').each(function() {
                $(this).toggleClass('d-none', this.getAttribute('data-card-type') !== resetType);
            });
            $('#idc-field-set-label').text(resetType === 'staff' ? (T.staff || 'Staff') : (T.student ||
                'Student'));
            syncPills('id_card_type');
            updateFieldCount();
            syncPills('layout_type');
            syncPills('profile_image_style');
            // re-sync slider value labels to their reverted inputs
            $('#idc-bg-trans-val').text($('#background_transparency').val() + '%');
            $('#idc-bg-size-val').text($('#background_size').val() + '%');
            updateDimensions(currentLayout(),
                false); // refresh helper text only, do not clobber saved dims
            updatePreview();
        }, 50);
    });

    // ---- First paint (do NOT auto-fill dims on load — keep saved/server-defaulted values) ----
    updateFieldCount();
    applyDimBounds(currentLayout());
    updateDimensions(currentLayout(), false);
    updatePreview();
    pinPreview();
});
