/* ==========================================================================
   Super Admin Dashboard hydration + charts (ApexCharts).
   All server data arrives via #dashboard data-* attributes and the
   home/widget/* JSON endpoints. Labels resolve through window.languageLabels.
   ========================================================================== */
(function () {
    "use strict";

    var root = document.getElementById("dashboard");
    if (!root || typeof ApexCharts === "undefined") {
        return;
    }

    var cfg = {
        sessionYearId: root.dataset.sessionYearId,
        currency: root.dataset.currency || "",
        defaultMonth: root.dataset.defaultMonth,
        urls: {
            fees: root.dataset.urlFees,
            attendance: root.dataset.urlAttendance,
            assessment: root.dataset.urlAssessment,
            leaves: root.dataset.urlLeaves,
            events: root.dataset.urlEvents,
            exams: root.dataset.urlExams,
            assignments: root.dataset.urlAssignments,
        },
    };

    var charts = {};

    /* ---- helpers ---- */
    function t(key) {
        try {
            return (window.languageLabels && window.languageLabels[key]) || key;
        } catch (e) {
            return key;
        }
    }

    function themeColors() {
        // Read the literal settings-driven colors from :root (--theme-color / --secondary-color).
        // Reading the chained --dash-* vars could return an unresolved "var(...)" string in some
        // browsers, which would break ApexCharts colors — so use the source values here.
        var rootStyles = getComputedStyle(document.documentElement);
        function readVar(name, fallback) {
            var v = (rootStyles.getPropertyValue(name) || "").trim();
            return !v || v.indexOf("var(") !== -1 ? fallback : v;
        }
        var green = readVar("--theme-color", "#2bb673");
        var navy = readVar("--secondary-color", "#1b3b5f");
        return {
            green: green,
            greenLight: mix(green, "#ffffff", 0.55),
            navy: navy,
            amber: "#f5b13d",
            red: "#fc5a6b",
            track: "#eef0f4",
        };
    }

    // Mix two hex colors (ratio = weight of color b).
    function mix(a, b, ratio) {
        var ca = hexToRgb(a);
        var cb = hexToRgb(b);
        if (!ca || !cb) return a;
        var r = Math.round(ca.r * (1 - ratio) + cb.r * ratio);
        var g = Math.round(ca.g * (1 - ratio) + cb.g * ratio);
        var bl = Math.round(ca.b * (1 - ratio) + cb.b * ratio);
        return "rgb(" + r + "," + g + "," + bl + ")";
    }

    function hexToRgb(hex) {
        hex = (hex || "").trim();
        var m = hex.replace("#", "");
        if (m.length === 3) {
            m = m[0] + m[0] + m[1] + m[1] + m[2] + m[2];
        }
        if (m.length !== 6) return null;
        return {
            r: parseInt(m.slice(0, 2), 16),
            g: parseInt(m.slice(2, 4), 16),
            b: parseInt(m.slice(4, 6), 16),
        };
    }

    function money(value) {
        var n = Number(value) || 0;
        return cfg.currency + n.toLocaleString(undefined, { minimumFractionDigits: 0, maximumFractionDigits: 2 });
    }

    function fmtNumber(value) {
        return (Number(value) || 0).toLocaleString();
    }

    function escapeHtml(str) {
        return String(str == null ? "" : str)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;");
    }

    function widgetUrl(base, params) {
        var url = new URL(base, window.location.origin);
        params = params || {};
        params.session_year_id = cfg.sessionYearId;
        Object.keys(params).forEach(function (k) {
            if (params[k] !== undefined && params[k] !== null && params[k] !== "") {
                url.searchParams.set(k, params[k]);
            }
        });
        return url.toString();
    }

    function fetchJSON(url) {
        return fetch(url, { credentials: "same-origin", headers: { "X-Requested-With": "XMLHttpRequest" } }).then(function (r) {
            if (!r.ok) throw new Error("Request failed: " + r.status);
            return r.json();
        });
    }

    // Toggle a widget card between skeleton and content state.
    function setLoaded(card) {
        if (!card) return;
        var sk = card.querySelector("[data-skeleton]");
        var content = card.querySelector("[data-content]");
        if (sk) sk.hidden = true;
        if (content) content.hidden = false;
    }

    function toggleEmpty(card, isEmpty) {
        if (!card) return;
        var empty = card.querySelector("[data-empty]");
        if (empty) empty.hidden = !isEmpty;
    }

    function renderChart(key, el, options) {
        if (!el) return;
        if (charts[key]) {
            charts[key].destroy();
        }
        charts[key] = new ApexCharts(el, options);
        charts[key].render();
    }

    /* ---- Total Students gauge (two concentric rings) + ratio bars ---- */
    function initTotalStudents() {
        var el = document.getElementById("students-donut");
        if (!el) return;

        var total = Number(el.dataset.total) || 0;
        var boys = Number(el.dataset.boys) || 0;
        var girls = Number(el.dataset.girls) || 0;
        var colors = themeColors();
        var navy = colors.navy; // outer ring (boys), matching the bar color (--dash-navy)

        // Shares of the total student body (outer = boys, inner = girls).
        var denom = total > 0 ? total : boys + girls;
        var boysPct = denom > 0 ? Math.round((boys * 100) / denom) : 0;
        var girlsPct = denom > 0 ? Math.round((girls * 100) / denom) : 0;

        renderChart("students", el, {
            chart: { type: "radialBar", height: 280 },
            series: [boysPct, girlsPct],
            labels: [t("boys"), t("girls")],
            colors: [navy, colors.green],
            stroke: { lineCap: "round" },
            legend: { show: false },
            plotOptions: {
                radialBar: {
                    hollow: { size: "48%" },
                    track: { background: colors.track, strokeWidth: "100%", margin: 8 },
                    dataLabels: {
                        name: { offsetY: 22, color: "#8a8f9c", fontSize: "13px" },
                        value: {
                            offsetY: -14,
                            fontSize: "28px",
                            fontWeight: 700,
                            color: "#1f2937",
                            formatter: function (val) {
                                return val + "%";
                            },
                        },
                        total: {
                            show: true,
                            label: t("total_students"),
                            color: "#8a8f9c",
                            fontSize: "13px",
                            formatter: function () {
                                return fmtNumber(total);
                            },
                        },
                    },
                },
            },
        });

        // Ratio bars: scaled to the larger group so its bar is full (matches the Figma).
        var maxGender = Math.max(boys, girls);
        var boysBar = document.getElementById("students-boys-bar");
        var girlsBar = document.getElementById("students-girls-bar");
        if (boysBar) boysBar.style.width = (maxGender > 0 ? (boys * 100) / maxGender : 0) + "%";
        if (girlsBar) girlsBar.style.width = (maxGender > 0 ? (girls * 100) / maxGender : 0) + "%";
    }

    /* ---- Fees Collection ---- */
    function loadFees() {
        var card = document.getElementById("fees-card");
        fetchJSON(widgetUrl(cfg.urls.fees, {}))
            .then(function (data) {
                cfg.currency = data.currency || cfg.currency;
                setText("fees-collected", money(data.collected));
                setText("fees-dues", money(data.upcoming_dues));
                setText("fees-overdue", money(data.overdue));

                var colors = themeColors();
                var sum = (Number(data.collected) || 0) + (Number(data.upcoming_dues) || 0) + (Number(data.overdue) || 0);
                var el = document.getElementById("fees-donut");

                renderChart("fees", el, {
                    chart: { type: "donut", height: 300 },
                    series: sum > 0 ? [data.collected, data.upcoming_dues, data.overdue] : [1],
                    labels: sum > 0 ? [t("collected"), t("upcoming_dues"), t("overdue_amount")] : [t("no_data_found")],
                    colors: sum > 0 ? [colors.green, colors.amber, colors.red] : [colors.track],
                    stroke: { width: 0 },
                    legend: { show: false },
                    dataLabels: { enabled: false },
                    tooltip: { enabled: sum > 0, y: { formatter: function (v) { return money(v); } } },
                    plotOptions: { pie: { donut: { size: "70%" } } },
                });

                setLoaded(card);
            })
            .catch(function () {
                setLoaded(card);
            });
    }

    /* ---- Attendance ---- */
    function loadAttendance(classId) {
        var card = document.getElementById("attendance-card");
        fetchJSON(widgetUrl(cfg.urls.attendance, { class_id: classId }))
            .then(function (data) {
                setLoaded(card);
                var sections = data.sections || [];
                var hasData = sections.some(function (s) {
                    return (Number(s.present_pct) || 0) + (Number(s.absent_pct) || 0) > 0;
                });
                toggleEmpty(card, !hasData);

                var el = document.getElementById("attendance-chart");
                if (!hasData) {
                    if (charts.attendance) charts.attendance.destroy();
                    if (el) el.innerHTML = "";
                    return;
                }

                var colors = themeColors();
                renderChart("attendance", el, {
                    chart: { type: "bar", stacked: true, height: 300, toolbar: { show: false } },
                    series: [
                        { name: t("present"), data: sections.map(function (s) { return Number(s.present_pct) || 0; }) },
                        { name: t("absent"), data: sections.map(function (s) { return Number(s.absent_pct) || 0; }) },
                    ],
                    colors: [colors.green, colors.red],
                    xaxis: { categories: sections.map(function (s) { return s.label; }) },
                    yaxis: { max: 100, min: 0, labels: { formatter: function (v) { return Math.round(v) + "%"; } } },
                    plotOptions: { bar: { horizontal: false, columnWidth: "45%", borderRadius: 4 } },
                    dataLabels: { enabled: false },
                    legend: { position: "top", horizontalAlign: "right" },
                    grid: { borderColor: "#eef0f4" },
                    tooltip: { y: { formatter: function (v) { return (Number(v) || 0).toFixed(1) + "%"; } } },
                });
            })
            .catch(function () {
                setLoaded(card);
                toggleEmpty(card, true);
            });
    }

    /* ---- Assessment ---- */
    function loadAssessment(classSectionId) {
        var card = document.getElementById("assessment-card");

        if (!classSectionId) {
            setLoaded(card);
            toggleEmpty(card, true);
            document.getElementById("assessment-list").innerHTML = "";
            return;
        }

        fetchJSON(widgetUrl(cfg.urls.assessment, { class_section_id: classSectionId }))
            .then(function (data) {
                setLoaded(card);
                var students = data.students || [];
                toggleEmpty(card, students.length === 0);

                var colors = themeColors();
                var html = students
                    .map(function (s) {
                        var pct = Number(s.percentage) || 0;
                        return (
                            '<div class="assessment-row">' +
                            '<span class="assessment-name" title="' + escapeHtml(s.name) + '">' + escapeHtml(s.name) + "</span>" +
                            '<div class="dash-progress"><div class="dash-progress-bar" style="width:' + pct + "%;background:" + colors.green + '"></div></div>' +
                            '<span class="assessment-pct">' + pct.toFixed(2) + "%</span>" +
                            "</div>"
                        );
                    })
                    .join("");
                document.getElementById("assessment-list").innerHTML = html;
            })
            .catch(function () {
                setLoaded(card);
                toggleEmpty(card, true);
            });
    }

    /* ---- Leaves ---- */
    function leaveTypeLabel(type) {
        var map = { Full: "full_day", "First Half": "first_half", "Second Half": "second_half" };
        return map[type] ? t(map[type]) : type;
    }

    function loadLeaves(filter) {
        var card = document.getElementById("leaves-card");
        fetchJSON(widgetUrl(cfg.urls.leaves, { filter: filter }))
            .then(function (data) {
                setLoaded(card);
                var pendingCount = data.pending_count != null ? data.pending_count : 0;
                setText("leaves-pending-count", pendingCount);
                var pendingLink = document.getElementById("leaves-pending-link");
                if (pendingLink) pendingLink.hidden = pendingCount < 1;

                var rows = data.rows || [];
                toggleEmpty(card, rows.length === 0);

                var html = rows
                    .map(function (r) {
                        var status = r.status || "approved";
                        var statusCls = status === "pending" ? "leave-status leave-status--pending" : "leave-status";
                        return (
                            '<div class="leave-item">' +
                            '<img class="leave-avatar" src="' + escapeHtml(r.image) + '" alt="" onerror="onErrorImage(event)">' +
                            '<div class="leave-info">' +
                            '<span class="leave-name">' + escapeHtml(r.name) + "</span>" +
                            '<div class="leave-meta">' +
                            '<span class="leave-date">' + escapeHtml(r.date) + "</span>" +
                            '<span class="leave-type-chip">' + escapeHtml(leaveTypeLabel(r.type)) + "</span>" +
                            "</div></div>" +
                            '<span class="' + statusCls + '">' + t(status) + "</span>" +
                            "</div>"
                        );
                    })
                    .join("");
                document.getElementById("leaves-list").innerHTML = html;
            })
            .catch(function () {
                setLoaded(card);
                toggleEmpty(card, true);
            });
    }

    /* ---- Events ---- */
    function loadEvents(month) {
        var card = document.getElementById("events-card");
        fetchJSON(widgetUrl(cfg.urls.events, { month: month }))
            .then(function (data) {
                setLoaded(card);
                renderEventList(data.events || []);
                renderCalendar(data.calendar || {});
            })
            .catch(function () {
                setLoaded(card);
            });
    }

    function renderEventList(events) {
        var list = document.getElementById("events-list");
        if (!list) return;
        if (!events.length) {
            list.innerHTML = '<div class="dash-empty">' + t("no_events_found") + "</div>";
            return;
        }
        list.innerHTML = events
            .map(function (e) {
                var thumb = e.image
                    ? '<img class="event-thumb" src="' + escapeHtml(e.image) + '" alt="" onerror="onErrorImage(event)">'
                    : '<span class="event-thumb-placeholder"><i class="fa fa-calendar"></i></span>';
                var when = e.date + (e.time ? " · " + e.time : "");
                return (
                    '<div class="event-item">' +
                    thumb +
                    '<div class="event-info">' +
                    '<span class="event-title" title="' + escapeHtml(e.title) + '">' + escapeHtml(e.title) + "</span>" +
                    '<span class="event-when">' + escapeHtml(when) + "</span>" +
                    "</div></div>"
                );
            })
            .join("");
    }

    function renderCalendar(cal) {
        var wrap = document.getElementById("events-calendar");
        if (!wrap || !cal.days_in_month) return;

        var dow = ["Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun"];
        var eventDays = cal.event_days || [];
        var holidayDays = cal.holiday_days || [];

        var html = '<div class="cal-head">' + escapeHtml(cal.month_label || "") + "</div>";
        html += '<div class="cal-grid">';
        dow.forEach(function (d) {
            html += '<div class="cal-dow">' + d + "</div>";
        });

        // Leading blanks (first_weekday: 1=Mon .. 7=Sun)
        var lead = (cal.first_weekday || 1) - 1;
        for (var i = 0; i < lead; i++) {
            html += '<div class="cal-day is-muted"></div>';
        }

        for (var day = 1; day <= cal.days_in_month; day++) {
            var cls = "cal-day";
            if (cal.today && day === cal.today) cls += " is-today";
            if (eventDays.indexOf(day) !== -1) cls += " has-event";
            if (holidayDays.indexOf(day) !== -1) cls += " has-holiday";
            html += '<div class="' + cls + '">' + day + "</div>";
        }
        html += "</div>";
        wrap.innerHTML = html;
    }

    function setText(id, value) {
        var el = document.getElementById(id);
        if (el) el.textContent = value;
    }

    /* ---- Upcoming Exams (teacher) ---- */
    function loadExams(month) {
        var card = document.getElementById("exams-card");
        fetchJSON(widgetUrl(cfg.urls.exams, { month: month }))
            .then(function (data) {
                setLoaded(card);
                var exams = data.exams || [];
                var list = document.getElementById("exams-list");
                if (!list) return;
                if (!exams.length) {
                    list.innerHTML = '<div class="dash-empty">' + t("no_exams_found") + "</div>";
                    return;
                }
                list.innerHTML = exams
                    .map(function (e) {
                        return (
                            '<div class="exam-item">' +
                            '<div class="exam-date">' +
                            '<span class="exam-day">' + escapeHtml(e.day) + "</span>" +
                            '<span class="exam-month">' + escapeHtml(e.month) + "</span>" +
                            '<span class="exam-year">' + escapeHtml(e.year) + "</span>" +
                            "</div>" +
                            '<div class="exam-info">' +
                            '<span class="exam-name" title="' + escapeHtml(e.name) + '">' + escapeHtml(e.name) + "</span>" +
                            '<span class="exam-sub">' + escapeHtml(e.class_label) + (e.subjects ? " - " + escapeHtml(e.subjects) : "") + "</span>" +
                            "</div></div>"
                        );
                    })
                    .join("");
            })
            .catch(function () {
                setLoaded(card);
            });
    }

    /* ---- Assignments To Review (teacher) ---- */
    function loadAssignments(classSectionId) {
        var card = document.getElementById("assignments-card");
        fetchJSON(widgetUrl(cfg.urls.assignments, classSectionId ? { class_section_id: classSectionId } : {}))
            .then(function (data) {
                setLoaded(card);
                var rows = data.assignments || [];
                toggleEmpty(card, rows.length === 0);
                var list = document.getElementById("assignments-list");
                if (!list) return;
                list.innerHTML = rows
                    .map(function (a) {
                        return (
                            '<div class="assignment-item">' +
                            '<span class="assignment-thumb"><i class="fa fa-file-text-o"></i></span>' +
                            '<div class="assignment-info">' +
                            '<span class="assignment-title" title="' + escapeHtml(a.name) + '">' + escapeHtml(a.name) + "</span>" +
                            '<span class="assignment-meta">' + escapeHtml(a.subject) + " &middot; " + t("due_date") + ": " + escapeHtml(a.due_date) + "</span>" +
                            "</div>" +
                            '<span class="assignment-submitted">' + t("submitted") + ": " + escapeHtml(a.submitted) + "/" + escapeHtml(a.total) + "</span>" +
                            "</div>"
                        );
                    })
                    .join("");
            })
            .catch(function () {
                setLoaded(card);
                toggleEmpty(card, true);
            });
    }

    /* ---- Wiring ---- */
    function bindFilters() {
        var attendanceSelect = document.getElementById("attendance-class");
        if (attendanceSelect) {
            attendanceSelect.addEventListener("change", function () {
                loadAttendance(this.value);
            });
        }

        var assessmentSelect = document.getElementById("assessment-select");
        if (assessmentSelect) {
            assessmentSelect.addEventListener("change", function () {
                loadAssessment(this.value);
            });
        }

        var leavesSelect = document.getElementById("leaves-date");
        if (leavesSelect) {
            leavesSelect.addEventListener("change", function () {
                loadLeaves(this.value);
            });
        }

        var eventsSelect = document.getElementById("events-month");
        if (eventsSelect) {
            eventsSelect.addEventListener("change", function () {
                loadEvents(this.value);
            });
        }

        var examsSelect = document.getElementById("exams-month");
        if (examsSelect) {
            examsSelect.addEventListener("change", function () {
                loadExams(this.value);
            });
        }

        var assignmentsSelect = document.getElementById("assignments-class");
        if (assignmentsSelect) {
            assignmentsSelect.addEventListener("change", function () {
                loadAssignments(this.value);
            });
        }

        // Global session-year switch (dispatched by custom.js) → reload for a consistent re-render.
        document.addEventListener("session-year-synced", function () {
            window.location.reload();
        });
    }

    // Run a widget loader only when its card is present (the same JS serves
    // both the Super Admin and Teacher dashboards).
    function whenPresent(id, fn) {
        if (document.getElementById(id)) fn();
    }

    function initSelectWidget(cardId, selectId, loader) {
        if (!document.getElementById(cardId)) return;
        var select = document.getElementById(selectId);
        if (select && select.value) {
            loader(select.value);
        } else {
            setLoaded(document.getElementById(cardId));
            toggleEmpty(document.getElementById(cardId), true);
        }
    }

    function init() {
        whenPresent("students-donut", initTotalStudents);
        whenPresent("fees-card", loadFees);

        initSelectWidget("attendance-card", "attendance-class", loadAttendance);
        initSelectWidget("assessment-card", "assessment-select", loadAssessment);

        whenPresent("leaves-card", function () {
            loadLeaves("today");
        });
        whenPresent("events-card", function () {
            loadEvents(cfg.defaultMonth || (document.getElementById("events-month") || {}).value);
        });
        whenPresent("exams-card", function () {
            loadExams(cfg.defaultMonth || (document.getElementById("exams-month") || {}).value);
        });
        whenPresent("assignments-card", function () {
            var select = document.getElementById("assignments-class");
            loadAssignments(select && select.value ? select.value : "");
        });

        bindFilters();
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", init);
    } else {
        init();
    }
})();
