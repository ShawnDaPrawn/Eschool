$(function () {
    var $page = $("#student-report-page");

    if (!$page.length) {
        return;
    }

    var reportUrl = $page.data("report-url");
    var loadingText = $page.data("loading-text");

    function activeTab() {
        return $(".tab-link.active").data("tab") || "personal";
    }

    function selectedSessionYearId() {
        return $("#filter_session_year_global").val();
    }

    function loadTab(tab, extraData) {
        $(".tab-link").removeClass("active");
        $('.tab-link[data-tab="' + tab + '"]').addClass("active");

        $("#tab-content").html(
            '<div class="text-center p-3">' + loadingText + "</div>",
        );

        $.ajax({
            url: reportUrl,
            type: "GET",
            data: $.extend(
                { tab: tab, session_year_id: selectedSessionYearId() },
                extraData || {},
            ),
            success: function (response) {
                // Clean URL BEFORE injecting HTML so the new tab's
                // inline <script> never reads a stale semester_id.
                var url = new URL(window.location.href);
                url.searchParams.set("tab", tab);
                url.searchParams.delete("semester_id");
                url.searchParams.delete("status_filter");
                window.history.replaceState({}, "", url.toString());

                $("#tab-content").html(response);

                if ($("#tab-content").find("#table_list").length) {
                    try {
                        $("#table_list").bootstrapTable("destroy");
                    } catch (e) {}
                    $("#table_list").bootstrapTable();
                }
            },
        });
    }

    $(document).on("refresh.bs.table", "#table_list", function () {
        loadTab(activeTab());
    });

    $(document).on("click", ".tab-link", function () {
        loadTab($(this).data("tab"));
    });

    $(document).on("change", "#month-selector", function () {
        $.ajax({
            url: reportUrl,
            type: "GET",
            data: {
                tab: "attendance",
                session_year_id: selectedSessionYearId(),
                month: $(this).val(),
                calendar_only: 1,
            },
            success: function (response) {
                $("#attendance-calendar").html(response);
            },
        });
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
