// ============================================================
//  STATE
// ============================================================
var selectedStaffId = null;
var currentSelectedDate = new Date().toISOString().split("T")[0];
var currentView = "daily";
var flatpickrInstance = null;
var monthlyLoaded = null;

var SA_AVATAR = window.SA_AVATAR;
var Lang = window.SA_LANG;

function markIconHtml() {
    var tpl = document.getElementById("markIconTpl");
    return tpl ? tpl.innerHTML : "";
}

function escapeHtml(str) {
    return String(str === null || str === undefined ? "" : str)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#39;");
}

// ============================================================
//  DAILY QUERY PARAMS
// ============================================================
function dailyQueryParams(params) {
    return {
        limit: params.limit,
        offset: params.offset,
        sort: params.sort,
        order: params.order,
        search: params.search,
        date: currentSelectedDate,
        session_year_id: window.sessionYearId,
    };
}

function checkboxStateFormatter(value, row) {
    return {
        checked: false,
        disabled: false,
    };
}

function checkOnLeaveThenProceed(staffIds, sessionYearId, onPass) {
    $.ajax({
        url: window.SA_ROUTES.checkLeave,
        type: "GET",
        data: {
            staff_ids: staffIds,
            date: currentSelectedDate,
            session_year_id: sessionYearId,
        },
        success: function (response) {
            if (response.on_leave && response.on_leave.length > 0) {
                var names = response.on_leave
                    .map(function (item) {
                        return item.name;
                    })
                    .join(", ");
                var message =
                    names +
                    " " +
                    (response.on_leave.length > 1
                        ? Lang.onLeavePlural
                        : Lang.onLeaveSingular);
                Swal.fire({
                    icon: "error",
                    title: Lang.onLeaveTitle,
                    text: message,
                });
                return;
            }
            onPass();
        },
        error: function () {
            onPass();
        },
    });
}

document.addEventListener("DOMContentLoaded", function () {
    // Half-day section toggle in modal
    document.querySelectorAll('input[name="status"]').forEach(function (radio) {
        radio.addEventListener("change", function () {
            var section = document.getElementById("halfDaySection");
            if (this.value === "half_day") {
                section.style.display = "block";
            } else {
                section.style.display = "none";
                document
                    .querySelectorAll('input[name="halfType"]')
                    .forEach(function (el) {
                        el.checked = false;
                    });
            }
        });
    });

    // Month/year change handler
    function handleMonthYearChange(instance) {
        var year = instance.currentYear;
        var month = (instance.currentMonth + 1).toString().padStart(2, "0");
        var newMonth = year + "-" + month;
        var formatted = year + "-" + month + "-01";

        currentSelectedDate = formatted;
        instance.setDate(formatted, false);

        if (currentView === "monthly") {
            if (monthlyLoaded !== newMonth) {
                loadMonthlyTable(newMonth);
            }
        } else {
            $("#table_list").bootstrapTable("refresh");
        }
    }

    // Flatpickr
    flatpickrInstance = flatpickr("#attendanceDate", {
        dateFormat: "Y-m-d",
        defaultDate: "today",
        allowInput: true,

        onReady: function (selectedDates, dateStr) {
            currentSelectedDate = dateStr;
            $("#table_list").bootstrapTable("refresh");
        },

        onChange: function (selectedDates, dateStr) {
            currentSelectedDate = dateStr;
            var newMonth = dateStr.substring(0, 7);
            if (currentView === "monthly") {
                if (monthlyLoaded !== newMonth) {
                    loadMonthlyTable(newMonth);
                }
            } else {
                $("#table_list").bootstrapTable("refresh");
            }
        },

        onMonthChange: function (selectedDates, dateStr, instance) {
            handleMonthYearChange(instance);
        },
        onYearChange: function (selectedDates, dateStr, instance) {
            handleMonthYearChange(instance);
        },
    });

    // View switcher (daily / monthly)
    $("#attendance_view").on("change", function () {
        switchView(this.value);
    });

    // Close half-day dropdown when clicking outside
    document.addEventListener("click", function (e) {
        if (!e.target.closest(".attendance-dropdown")) {
            document.getElementById("halfDayBtn").classList.remove("open");
        }
    });

    // Checkbox listener: show / hide bulk action bar
    $("#table_list").on(
        "check.bs.table uncheck.bs.table check-all.bs.table uncheck-all.bs.table",
        function () {
            updateBulkBar();
        },
    );

    $("#table_list").on("load-success.bs.table", function (e, response) {
        updateBulkBar();
    });

    $("#table_list").on("click", ".sa-mark-btn", function () {
        var $btn = $(this);
        openModal(
            $btn.data("id"),
            $btn.data("name"),
            $btn.data("image") || null,
            $btn.data("status") || null,
            $btn.data("remark") || "",
        );
    });
});

// ============================================================
//  VIEW SWITCHER
// ============================================================
function switchView(view) {
    currentView = view;

    if (view === "daily") {
        $("#dailySection").show();
        $("#monthlyTableSection").hide();
        $("#table_list").bootstrapTable("refresh");
        updateBulkBar();
    } else {
        $("#dailySection").hide();
        $("#monthlyTableSection").show();
        loadMonthlyTable(currentSelectedDate.substring(0, 7));
    }
}

// ============================================================
//  BULK BAR VISIBILITY
// ============================================================
function updateBulkBar() {
    if (currentView !== "daily") return;
    var checkedRows = $("#table_list").bootstrapTable("getSelections");
    var bar = document.getElementById("bulkActionBar");
    if (!bar) return;
    bar.classList.toggle("show-bulk", checkedRows && checkedRows.length > 0);
}

// ============================================================
//  HALF-DAY DROPDOWN TOGGLE (bulk bar)
// ============================================================
function toggleHalfDayOptions(event) {
    event.stopPropagation();
    document.getElementById("halfDayBtn").classList.toggle("open");
}

// ============================================================
//  FORMATTERS (daily table)
// ============================================================
function nameimageformater(value, row) {
    var avatar = renderAvatarHtml(row.image, value);
    return (
        '<div class="sa-name-cell">' +
        avatar +
        '<span class="sa-name-text">' +
        escapeHtml(value) +
        "</span></div>"
    );
}

function renderAvatarHtml(image, name) {
    var initials = getInitials(name || "");
    if (isInvalidImage(image)) {
        return (
            '<div class="d-flex align-items-center justify-content-center rounded-circle bg-primary text-white font-weight-bold" style="width:36px;height:36px;font-size:14px;flex-shrink:0;text-transform:uppercase;">' +
            escapeHtml(initials) +
            "</div>"
        );
    }
    return (
        '<div class="position-relative" style="width:36px;height:36px;">' +
        '<img src="' +
        escapeHtml(image) +
        '" class="sa-avatar-img" alt="" onerror="showInitialsFallback(this, \'' +
        initials.replace(/'/g, "\\'") +
        "')\">" +
        "</div>"
    );
}

function getInitials(name) {
    var parts = name.trim().split(/\s+/);
    var initials = "";
    if (parts.length >= 2) {
        initials =
            parts[0].charAt(0).toUpperCase() +
            parts[parts.length - 1].charAt(0).toUpperCase();
    } else if (parts.length === 1 && parts[0]) {
        initials = parts[0].charAt(0).toUpperCase();
    } else {
        initials = "?";
    }
    return initials;
}

function showInitialsFallback(imgElement, initials) {
    var container = imgElement.parentElement;
    if (container) {
        container.innerHTML =
            '<div class="d-flex align-items-center justify-content-center rounded-circle bg-primary text-white font-weight-bold" style="width:36px;height:36px;font-size:14px;flex-shrink:0;text-transform:uppercase;">' +
            initials +
            "</div>";
    }
}

// An empty profile image resolves to the storage root URL (the User image
// accessor never returns null) — treat that, and other junk values, as missing.
function isInvalidImage(image) {
    return (
        !image ||
        /\/storage\/?$/.test(image) ||
        image.indexOf("undefined") !== -1 ||
        image === "null" ||
        image.indexOf("default.png") !== -1
    );
}

function statusFormatter(value, row) {
    return (
        '<span class="badge ' +
        escapeHtml(row.status_badge) +
        '">' +
        escapeHtml(value) +
        "</span>"
    );
}

var statusTextToValue = {
    Present: "present",
    Absent: "absent",
    "First half": "first_half",
    "Second half": "second_half",
    "Half day": "half_day",
    Holiday: "holiday",
};

function actionFormatter(value, row) {
    var rawStatus = statusTextToValue[row.status_text] || "";
    return (
        '<button type="button" class="btn btn-xs btn-gradient-primary btn-rounded btn-icon sa-mark-btn"' +
        ' title="' +
        escapeHtml(Lang.markAttendance) +
        '"' +
        ' data-id="' +
        escapeHtml(row.id) +
        '"' +
        ' data-name="' +
        escapeHtml(row.name) +
        '"' +
        ' data-image="' +
        escapeHtml(row.image || "") +
        '"' +
        ' data-status="' +
        escapeHtml(rawStatus) +
        '"' +
        ' data-remark="' +
        escapeHtml(row.remark || "") +
        '">' +
        markIconHtml() +
        "</button>"
    );
}

// ============================================================
//  MONTHLY TABLE
// ============================================================
var dotClassMap = {
    present: "dot-present",
    absent: "dot-absent",
    first_half: "dot-half",
    second_half: "dot-half",
    half_day: "dot-half",
    holiday: "dot-holiday",
};

function retryMonthly() {
    monthlyLoaded = null;
    loadMonthlyTable(currentSelectedDate.substring(0, 7));
}

function loadMonthlyTable(month) {
    var parts = month.split("-");
    var year = parts[0];
    var mon = parts[1];
    var daysInMonth = new Date(year, parseInt(mon), 0).getDate();

    // Build header
    var headRow = "<th>" + escapeHtml(Lang.staffDetails) + "</th>";
    for (var d = 1; d <= daysInMonth; d++) {
        headRow += "<th>" + d + "</th>";
    }

    // Skeleton with loading row
    $("#monthly_attendance_wrapper").html(
        '<div class="monthly-table-wrap">' +
            '<table class="monthly-table">' +
            "<thead><tr>" +
            headRow +
            "</tr></thead>" +
            '<tbody id="monthly_attendance_tbody">' +
            '<tr><td colspan="' +
            (daysInMonth + 1) +
            '" class="sa-monthly-msg">' +
            '<span class="spinner-border spinner-border-sm"></span> ' +
            escapeHtml(Lang.loading) +
            "</td></tr>" +
            "</tbody>" +
            "</table>" +
            "</div>",
    );

    $.ajax({
        url: window.SA_ROUTES.list,
        type: "GET",
        data: {
            date: month + "-01",
            session_year_id: window.sessionYearId,
        },

        success: function (res) {
            monthlyLoaded = month;
            var rows = "";

            (res.data || []).forEach(function (staff) {
                var avatar = renderAvatarHtml(staff.image, staff.name);

                var dayCells = "";
                (res.days || []).forEach(function (d) {
                    var status = staff["day_" + d];
                    var dotClass = status
                        ? dotClassMap[status] || "dot-none"
                        : "dot-none";
                    dayCells +=
                        '<td><span class="attendance-dot ' +
                        dotClass +
                        '"></span></td>';
                });

                rows +=
                    "<tr>" +
                    '<td><div class="sa-name-cell">' +
                    avatar +
                    '<span class="sa-name-text">' +
                    escapeHtml(staff.name) +
                    "</span></div></td>" +
                    dayCells +
                    "</tr>";
            });

            if (!res.data || res.data.length === 0) {
                rows =
                    '<tr><td colspan="' +
                    ((res.days ? res.days.length : daysInMonth) + 1) +
                    '" ' +
                    'class="sa-monthly-msg">' +
                    escapeHtml(Lang.noStaffFound) +
                    "</td></tr>";
            }

            $("#monthly_attendance_tbody").html(rows);
        },

        error: function (xhr) {
            console.error("loadMonthlyTable error:", xhr.responseText);
            $("#monthly_attendance_wrapper").html(
                '<p class="sa-monthly-error">' +
                    escapeHtml(Lang.failedToLoad) +
                    " " +
                    '<a href="#" onclick="retryMonthly(); return false;" class="sa-retry-link">' +
                    escapeHtml(Lang.tryAgain) +
                    "</a>" +
                    "</p>",
            );
        },
    });
}

// ============================================================
//  BULK ATTENDANCE
// ============================================================
function bulkAttendance(type) {
    var sessionYearId = window.sessionYearId;
    var selectedRows = $("#table_list").bootstrapTable("getSelections");

    if (selectedRows.length === 0) {
        showErrorToast(Lang.selectStaff);
        return;
    }

    var staffIds = selectedRows.map(function (row) {
        return row.id;
    });

    if (type === "present") {
        checkOnLeaveThenProceed(staffIds, sessionYearId, function () {
            submitBulkAttendance(type, staffIds, sessionYearId);
        });
    } else {
        submitBulkAttendance(type, staffIds, sessionYearId);
    }
}

function submitBulkAttendance(type, staffIds, sessionYearId) {
    document.querySelectorAll(".attendance-btn").forEach(function (b) {
        b.disabled = true;
    });

    $.ajax({
        url: window.SA_ROUTES.bulkStore,
        type: "POST",
        data: {
            _token: window.csrfToken,
            staff_ids: staffIds,
            session_year_id: sessionYearId,
            date: currentSelectedDate,
            type: type,
            remark: "",
        },

        success: function (response) {
            document.querySelectorAll(".attendance-btn").forEach(function (b) {
                b.disabled = false;
            });
            document.getElementById("halfDayBtn").classList.remove("open");

            // Explicitly hide the bulk action bar — refresh() doesn't
            // reliably fire check/uncheck events, so updateBulkBar()
            // alone can't be trusted to catch this.
            document
                .getElementById("bulkActionBar")
                .classList.remove("show-bulk");

            var currentMonth = currentSelectedDate.substring(0, 7);
            if (monthlyLoaded === currentMonth) {
                monthlyLoaded = null;
            }
            $("#table_list").bootstrapTable("refresh");
            showSuccessToast(response.message);
        },

        error: function (xhr) {
            document.querySelectorAll(".attendance-btn").forEach(function (b) {
                b.disabled = false;
            });
            showErrorToast(
                xhr.responseJSON?.message || Lang.somethingWentWrong,
            );
        },
    });
}

// ============================================================
//  MODAL — OPEN
// ============================================================
function openModal(id, name, image, status, remark) {
    selectedStaffId = id;
    $("#staff_id").val(id);
    $("#modalName").text(name);

    var initials = getInitials(name || "");
    var avatarHtml = "";
    if (isInvalidImage(image)) {
        avatarHtml =
            '<div class="d-flex align-items-center justify-content-center rounded-circle bg-primary text-white font-weight-bold" style="width:48px;height:48px;font-size:18px;text-transform:uppercase;">' +
            initials +
            "</div>";
    } else {
        avatarHtml =
            '<img src="' +
            image +
            '" alt="" class="avatar-circle-img" onerror="showInitialsFallbackModal(this, \'' +
            initials.replace(/'/g, "\\'") +
            "')\">";
    }
    $("#modalAvatar").html(avatarHtml);

    $("#halfDaySection").hide();
    $('input[name="halfType"]').prop("checked", false);

    var radioValue = "present";
    var halfType = null;

    if (status === "present" || status === "absent" || status === "holiday") {
        radioValue = status;
    } else if (status === "first_half") {
        radioValue = "half_day";
        halfType = "first";
    } else if (status === "second_half") {
        radioValue = "half_day";
        halfType = "second";
    }

    $('input[name="status"][value="' + radioValue + '"]').prop("checked", true);

    if (radioValue === "half_day") {
        $("#halfDaySection").show();
        if (halfType) {
            $('input[name="halfType"][value="' + halfType + '"]').prop(
                "checked",
                true,
            );
        }
    }

    $("#reasonInput").val(remark || "");
    $("#attendanceModal").modal("show");
}

function showInitialsFallbackModal(imgElement, initials) {
    var parent = imgElement.parentElement;
    if (parent) {
        parent.innerHTML =
            '<div class="d-flex align-items-center justify-content-center rounded-circle bg-primary text-white font-weight-bold" style="width:48px;height:48px;font-size:18px;text-transform:uppercase;">' +
            initials +
            "</div>";
    }
}

// ============================================================
//  MODAL — SAVE
// ============================================================
function saveAttendance() {
    var sessionYearId = window.sessionYearId;
    var status = $('input[name="status"]:checked').val();
    var halfType = $('input[name="halfType"]:checked').val();
    var remark = $("#reasonInput").val();

    var finalType = status;
    if (status === "half_day") {
        if (!halfType) {
            showErrorToast(Lang.selectHalfType);
            return;
        }
        finalType = halfType === "first" ? "first_half" : "second_half";
    }

    if (finalType === "present") {
        checkOnLeaveThenProceed([selectedStaffId], sessionYearId, function () {
            submitSaveAttendance(finalType, remark, sessionYearId);
        });
    } else {
        submitSaveAttendance(finalType, remark, sessionYearId);
    }
}

function submitSaveAttendance(finalType, remark, sessionYearId) {
    var btn = $("#saveAttendanceBtn");
    var originalHTML = btn.html();
    btn.html(Lang.saving).prop("disabled", true);

    $.ajax({
        url: window.SA_ROUTES.store,
        type: "POST",
        data: {
            _token: window.csrfToken,
            staff_id: selectedStaffId,
            session_year_id: sessionYearId,
            date: currentSelectedDate,
            type: finalType,
            remark: remark,
        },

        success: function (response) {
            btn.html(originalHTML).prop("disabled", false);
            $("#attendanceModal").modal("hide");
            var currentMonth = currentSelectedDate.substring(0, 7);
            if (monthlyLoaded === currentMonth) {
                monthlyLoaded = null;
            }
            $("#table_list").bootstrapTable("refresh");
            showSuccessToast(response.message);
        },

        error: function (xhr) {
            btn.html(originalHTML).prop("disabled", false);
            showErrorToast(
                xhr.responseJSON?.message || Lang.somethingWentWrong,
            );
        },
    });
}
