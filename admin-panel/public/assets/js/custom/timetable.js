/* ==========================================================================
   Create-timetable form (resources/views/timetable/index.blade.php).
   Endpoint URLs arrive via #timetable-page data-* attributes so this file stays
   free of inline blade. Labels resolve through trans() (function.js).

   Day tabs (Bootstrap) each hold a jquery.repeater (.repeater) with period rows.
   Picking a session-year + class-section loads that day's saved timetable (or a
   blank row) and populates the subject / teacher selects.
   ========================================================================== */
(function () {
    "use strict";

    var root = document.getElementById("timetable-page");
    if (!root) {
        return;
    }

    var urls = {
        classSections: root.dataset.urlClassSections,
        subjects: root.dataset.urlSubjects,
        teachers: root.dataset.urlTeachers,
        check: root.dataset.urlCheck,
    };

    var dayIndex = {
        monday: 1,
        tuesday: 2,
        wednesday: 3,
        thursday: 4,
        friday: 5,
        saturday: 6,
        sunday: 7,
    };

    // module-scoped state shared by the handlers below
    var tab;
    var total_row;

    // guards against a stale request hiding the loader for a newer load
    var timetableLoadToken = 0;

    function showTimetableLoader() {
        $("#timetable-div").addClass("d-none");
        $("#timetable-loader").removeClass("d-none");
    }

    function hideTimetableLoader() {
        $("#timetable-loader").addClass("d-none");
        $("#timetable-div").removeClass("d-none");
    }

    function hideTimetableSection() {
        $("#timetable-loader").addClass("d-none");
        $("#timetable-div").addClass("d-none");
    }

    // Lucide trash glyph (mirrors <x-icon name="trash">) for JS-built rows.
    var trashSvg =
        '<svg class="lc-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" ' +
        'fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" ' +
        'aria-hidden="true" focusable="false"><path d="M3 6h18"/>' +
        '<path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/>' +
        '<path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>' +
        '<line x1="10" x2="10" y1="11" y2="17"/><line x1="14" x2="14" y1="11" y2="17"/></svg>';

    // Builds one saved-period row matching the addmore.blade.php markup.
    function buildPeriodRow(day_name, i, item) {
        var liveClassUrl = item.live_class_url ? item.live_class_url : "";
        var linkName = item.link_name ? item.link_name : "";

        return (
            '<div data-repeater-item="" class="tt-row ' + day_name + '_count" id="' + day_name + '_count[' + i + ']">' +
            '<input required hidden type="text" value="' + item.id + '" name="' + day_name + '_group[' + i + '][id]" ' +
            'id="' + day_name + '_id[' + i + ']" class="form-control" placeholder="Id">' +
            '<div class="tt-cell tt-cell--wide" data-label="' + trans("Subjects") + '">' +
            '<select required name="' + day_name + '_group[' + i + '][subject_id]" ' +
            'class="form-control set_subject_id ' + day_name + '_group[' + i + '][subject_id]" ' +
            'style="width:100%;" tabindex="-1" aria-hidden="true"></select></div>' +
            '<div class="tt-cell tt-cell--wide" data-label="' + trans("teacher") + '">' +
            '<select required name="' + day_name + '_group[' + i + '][teacher_id]" ' +
            'class="form-control set_teacher_id" style="width:100%;" tabindex="-1" aria-hidden="true"></select></div>' +
            '<div class="tt-cell" data-label="' + trans("Start time") + '">' +
            '<input required type="text" value="' + item.start_time + '" name="' + day_name + '_group[' + i + '][start_time]" ' +
            'class="timetable_start_time form-control timepicker-popup" placeholder="' + trans("Start time") + '"></div>' +
            '<div class="tt-cell" data-label="' + trans("End time") + '">' +
            '<input required type="text" value="' + item.end_time + '" name="' + day_name + '_group[' + i + '][end_time]" ' +
            'class="timetable_end_time form-control timepicker-popup" placeholder="' + trans("End time") + '"></div>' +
            '<div class="tt-cell" data-label="' + trans("Live Class Link") + '">' +
            '<input type="text" value="' + liveClassUrl + '" name="' + day_name + '_group[' + i + '][live_class_url]" ' +
            'class="form-control" placeholder="' + trans("Link") + '"></div>' +
            '<div class="tt-cell" data-label="' + trans("Name") + '">' +
            '<input type="text" value="' + linkName + '" name="' + day_name + '_group[' + i + '][link_name]" ' +
            'class="form-control" placeholder="' + trans("Name") + '"></div>' +
            '<button data-repeater-delete type="button" class="row_remove tt-row__remove" ' +
            'data-label="' + trans("Delete") + '" title="' + trans("Delete") + '" aria-label="' + trans("Delete") + '">' +
            trashSvg + "</button>" +
            "</div>"
        );
    }

    $("#timetable_class_section").on("change", function () {
        var tabName = $("#active_tab").val();
        clearTimetableRows(tabName);
        set_subject();
        checktimetable(tabName);
    });

    $("#session_year").on("change", function () {
        var session_year_id = $(this).val();
        var tabName = $("#active_tab").val();
        clearTimetableRows(tabName);

        // class section list is reloaded for the new session year
        hideTimetableSection();

        // update hidden input in active tab form
        $("#" + tabName + ' input[name="session_year_id"]').val(session_year_id);

        loadClassSectionsWithSemesters();
    });

    function loadClassSectionsWithSemesters() {
        var session_year_id = $("#session_year").val();

        if (!session_year_id) {
            $("#timetable_class_section").html('<option value="">' + trans("select") + "</option>");
            return;
        }

        $.ajax({
            url: urls.classSections,
            type: "GET",
            data: {
                session_year_id: session_year_id,
            },
            success: function (response) {
                var html = '<option value="">' + trans("select") + "</option>";
                for (var i = 0; i < response.length; i++) {
                    html +=
                        '<option value="' + response[i].class_section_id + '"' +
                        ' data-class="' + response[i].class_id + '"' +
                        ' data-semester="' + (response[i].semester_id || "") + '"' +
                        ">" + response[i].label + "</option>";
                }
                $("#timetable_class_section").html(html);
            },
        });
    }

    function set_subject() {
        $(".select_error").addClass("d-none");

        if ($("#timetable_class_section").val() !== "" && $("#session_year").val() !== "") {
            $(".addmore").removeClass("d-none");
        } else {
            $(".addmore").addClass("d-none");
            return;
        }

        var tabName = $("#active_tab").val();

        $("#" + tabName + ' input[name="class_section_id"]').val($("#timetable_class_section").val());
        $("#" + tabName + ' input[name="session_year_id"]').val($("#session_year").val());
        $("#" + tabName + ' input[name="semester_id"]').val(
            $("#timetable_class_section").find(":selected").attr("data-semester") || ""
        );

        var class_id = $("#timetable_class_section").find(":selected").attr("data-class");
        var session_year_id = $("#session_year").val();
        var semester_id = $("#timetable_class_section").find(":selected").attr("data-semester") || "";
        $.ajax({
            url: urls.subjects,
            type: "GET",
            data: {
                class_id: class_id,
                session_year_id: session_year_id,
                semester_id: semester_id,
            },
            success: function (response) {
                var html = "";
                if (response != "") {
                    html += '<option value="">' + trans("select") + " " + trans("subject") + "</option>";
                    for (var i = 0; i < response.length; i++) {
                        html +=
                            "<option value=" + response[i]["subject"]["id"] + ">" +
                            response[i]["subject"]["name"] + " - " + response[i]["subject"]["type"] + "</option>";
                    }
                } else {
                    html += '<option value="">' + trans("no_data_found") + "</option>";
                }
                $(".set_subject_id").html(html);
            },
        });
    }

    $(document).ready(function () {
        tab = $(".timetable_nav").find("li a.active").attr("data-id");
        total_row = $("." + tab + "_count").length;

        $("#" + tab + ' input[name="day"]').val(tab);
        $("#" + tab + ' input[name="class_section_id"]').val($("#timetable_class_section").val());
        $("#" + tab + ' input[name="session_year_id"]').val($("#session_year").val());
        $("#" + tab + ' input[name="semester_id"]').val(
            $("#timetable_class_section").find(":selected").attr("data-semester") || ""
        );

        $("#active_tab").val(tab);

        get_total();
        set_teacher_data();
    });

    // tab change data
    $('a[data-toggle="tab"]').on("show.bs.tab", function (e) {
        tab = $(e.target).attr("data-id");

        total_row = $("." + tab + "_count").length;

        $("#" + tab + ' input[name="day"]').val(tab);
        $("#" + tab + ' input[name="class_section_id"]').val($("#timetable_class_section").val());
        $("#" + tab + ' input[name="session_year_id"]').val($("#session_year").val());
        $("#" + tab + ' input[name="semester_id"]').val(
            $("#timetable_class_section").find(":selected").attr("data-semester") || ""
        );

        $("#active_tab").val(tab);

        get_total();
        set_teacher_data();
        checktimetable(tab);
    });

    function get_total() {
        $(".addmore").on("click", function () {
            total_row = $("." + tab + "_count").length;
            var data = total_row - 1;
            $('select[name="' + tab + "_group[" + data + '][subject_id]"]').html(
                $('select[name="' + tab + '_group[0][subject_id]"]').html()
            );
            set_teacher_data();
        });
        $(".row_remove").on("click", function () {
            total_row = $("." + tab + "_count").length;
            set_teacher_data();
        });
    }

    function set_teacher_data() {
        for (var k = 0; k < total_row; k++) {
            (function (k) {
                $('select[name="' + tab + "_group[" + k + '][subject_id]"]').on("change", function () {
                    var subject_id = $(this).val();
                    var class_section_id = $("#timetable_class_section").val();
                    var session_year_id = $("#session_year").val();

                    $.ajax({
                        url: urls.teachers,
                        type: "GET",
                        data: {
                            subject_id: subject_id,
                            class_section_id: class_section_id,
                            session_year_id: session_year_id,
                        },
                        success: function (response) {
                            var html = "";
                            if (response != "") {
                                html += '<option value="">' + trans("Select Teacher") + "</option>";
                                for (var i = 0; i < response.length; i++) {
                                    html +=
                                        "<option data-id=" + response[i]["id"] + " value=" + response[i]["teacher_id"] + ">" +
                                        response[i]["teacher"]["user"]["first_name"] + " " +
                                        response[i]["teacher"]["user"]["last_name"] + "</option>";
                                }
                            } else {
                                html += '<option value="">' + trans("no_data_found") + "</option>";
                            }
                            $('select[name="' + tab + "_group[" + k + '][teacher_id]"]').html(html);
                        },
                    });
                });
            })(k);
        }
    }

    function checktimetable(day_name) {
        var day = dayIndex[day_name] || "";
        var class_section_id = $("#timetable_class_section").val();
        var session_year_id = $("#session_year").val();
        var semester_id = $("#timetable_class_section").find(":selected").attr("data-semester") || "";

        // nothing selected yet -> keep the timetable section hidden, skip the request
        if (class_section_id === "" || session_year_id === "") {
            hideTimetableSection();
            return;
        }

        var loadToken = ++timetableLoadToken;
        showTimetableLoader();

        $.ajax({
            url: urls.check,
            type: "GET",
            data: {
                class_section_id: class_section_id,
                session_year_id: session_year_id,
                semester_id: semester_id,
                day: day,
            },
            success: function (response) {
                if (response != "") {
                    set_subject();
                    var html = "";
                    for (var i = 0; i < response.length; i++) {
                        html += buildPeriodRow(day_name, i, response[i]);
                    }
                    $(".set_" + day_name).html(html);
                    tab = day_name;
                    total_row = $("." + tab + "_count").length;
                    set_teacher_data();

                    for (var j = 0; j < response.length; j++) {
                        (function (j) {
                            setTimeout(function () {
                                $('select[name^="' + day_name + "_group[" + j + '][subject_id]"]')
                                    .children('option[value="' + response[j]["subject_teacher"]["subject"]["id"] + '"]')
                                    .attr("selected", true);
                                $('select[name^="' + day_name + "_group[" + j + '][subject_id]"]').trigger("change");
                                setTimeout(function () {
                                    $('select[name^="' + day_name + "_group[" + j + '][teacher_id]"]')
                                        .children('option[value="' + response[j]["subject_teacher"]["teacher"]["id"] + '"]')
                                        .attr("selected", true);
                                }, 1000);
                            }, 1000);
                        })(j);
                    }

                    // the deferred logic above fills subject & teacher after ~2s;
                    // keep the loader on until it settles so a complete timetable is revealed
                    setTimeout(function () {
                        if (loadToken === timetableLoadToken) {
                            hideTimetableLoader();
                        }
                    }, 2300);
                } else {
                    if (loadToken === timetableLoadToken) {
                        hideTimetableLoader();
                    }
                }
            },
            error: function () {
                if (loadToken === timetableLoadToken) {
                    hideTimetableLoader();
                }
            },
        });
    }

    function clearTimetableRows(tabName) {
        // clear subject & teacher dropdowns
        $('select[name^="' + tabName + '_group"]').each(function () {
            $(this).val("");
        });

        // clear time inputs
        $('input[name^="' + tabName + '_group"][name$="[start_time]"]').val("");
        $('input[name^="' + tabName + '_group"][name$="[end_time]"]').val("");

        // clear link inputs
        $('input[name^="' + tabName + '_group"][name$="[live_class_url]"]').val("");
        $('input[name^="' + tabName + '_group"][name$="[link_name]"]').val("");
    }
})();
