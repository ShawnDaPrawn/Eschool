$(function () {
    var $page = $("#student-report-page");

    if (!$page.length) {
        return;
    }

    var reportUrl = $page.data("report-url");
    var loadingText = $page.data("loading-text");

    function activeTab() {
        return $page.find(".tab-link.active").data("tab") || "personal";
    }

    function workspaceFor(tab) {
        return tab === "attendance" || tab === "leave" ? tab : "people";
    }

    function selectedSessionYearId() {
        return $("#filter_session_year_global").val();
    }

    var pendingRequest;
    function loadTab(tab, extraData) {
        if (pendingRequest) pendingRequest.abort();
        var $content = $("#tab-content");
        var $status = $page.find("[data-report-status]");
        $content.attr("aria-busy", "true");
        $status.attr("role", "status").text(loadingText);
        pendingRequest = $.ajax({
            url: reportUrl,
            type: "GET",
            data: $.extend({ tab: tab, session_year_id: selectedSessionYearId() }, extraData || {}),
            success: function (response) {
                var url = new URL(window.location.href);
                url.searchParams.set("tab", tab);
                url.searchParams.delete("term_id");
                url.searchParams.delete("status_filter");
                if (extraData && extraData.month) url.searchParams.set("month", extraData.month);
                else url.searchParams.delete("month");
                window.history.replaceState({}, "", url.toString());
                $page.find(".tab-link").removeClass("active").removeAttr("aria-current");
                $page.find('.tab-link[data-tab="' + tab + '"]').addClass("active").attr("aria-current", "page");
                $content.html(response);
                $status.text("");
                if ($content.find("#table_list").length) {
                    try { $("#table_list").bootstrapTable("destroy"); } catch (e) {}
                    $("#table_list").bootstrapTable();
                }
            },
            error: function (_xhr, status) {
                if (status !== "abort") $status.attr("role", "alert").text($page.data("load-error"));
            },
            complete: function () { $content.removeAttr("aria-busy"); pendingRequest = null; },
        });
    }
    $(document).on("refresh.bs.table", "#table_list", function () { loadTab(activeTab()); });
    $page.on("click", ".tab-link", function (event) {
        if (event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
        // These sections belong to different global workspaces. Follow their
        // real links so the navigation trail and selected sidebar update too.
        if (workspaceFor($(this).data("tab")) !== workspaceFor(activeTab())) return;
        event.preventDefault();
        loadTab($(this).data("tab"));
    });
    $page.on("submit", ".sr-month-select-form", function (event) {
        event.preventDefault();
        loadTab("attendance", { month: $(this).find("[name=month]").val() });
    });

    $(document).on("session-year-synced", function () {
        loadTab(activeTab());
    });

    var resolvedSessionYearId = $page.data("resolved-session-year-id");
    var selectedNow = selectedSessionYearId();

    if (
        resolvedSessionYearId &&
        selectedNow &&
        String(resolvedSessionYearId) !== String(selectedNow)
    ) {
        var urlParams = new URLSearchParams(window.location.search);
        loadTab(activeTab(), { month: urlParams.get("month") || undefined });
    }
});
document.addEventListener("click", function (e) {
    const header = e.target.closest(".exam-report-toggle");
    if (!header) return;

    const target = document.querySelector(header.dataset.target);

    if (target) {
        target.classList.toggle("collapsed");
    }

    const icon = header.querySelector(".exam-toggle-icon");
    if (icon) {
        icon.classList.toggle("rotated");
    }
});
