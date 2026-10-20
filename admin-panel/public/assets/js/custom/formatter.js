"use strict";

// Bootstrap Custom Column Formatters
function fileFormatter(value, row) {
    let file_upload = "<br><h6>" + trans("file_upload") + "</h6>";
    let youtube_link = "<br><h6>" + trans("youtube_link") + "</h6>";
    let video_upload = "<br><h6>" + trans("video_upload") + "</h6>";
    let other_link = "<br><h6>" + trans("other_link") + "</h6>";

    let file_upload_counter = 1;
    let youtube_link_counter = 1;
    let video_upload_counter = 1;
    let other_link_counter = 1;

    $.each(row.file, function (key, data) {
        //1 = File Upload , 2 = Youtube , 3 = Uploaded Video , 4 = Other
        if (data.type == 1) {
            // 1 = File Ulopad
            file_upload +=
                "<a href='" +
                escapeHtml(safeUrl(data.file_url)) +
                "' target='_blank' >" +
                file_upload_counter +
                ". " +
                escapeHtml(data.file_name) +
                "</a><br>";
            file_upload_counter++;
        } else if (data.type == 2) {
            // 2 = Youtube Link
            youtube_link +=
                "<a href='" +
                escapeHtml(safeUrl(data.file_url)) +
                "' target='_blank' >" +
                youtube_link_counter +
                ". " +
                escapeHtml(data.file_name) +
                "</a><br>";
            youtube_link_counter++;
        } else if (data.type == 3) {
            // 3 = Uploaded Video
            video_upload +=
                "<a href='" +
                escapeHtml(safeUrl(data.file_url)) +
                "' target='_blank' >" +
                video_upload_counter +
                ". " +
                escapeHtml(data.file_name) +
                "</a><br>";
            video_upload_counter++;
        } else if (data.type == 4) {
            // 4 = Other Link
            other_link +=
                "<a href='" +
                escapeHtml(safeUrl(data.file_url)) +
                "' target='_blank' >" +
                other_link_counter +
                ". " +
                escapeHtml(data.file_name) +
                "</a><br>";
            other_link_counter++;
        }
    });
    let html = "";
    if (file_upload_counter > 1) {
        html += file_upload;
    }

    if (youtube_link_counter > 1) {
        html += youtube_link;
    }

    if (video_upload_counter > 1) {
        html += video_upload;
    }

    if (other_link_counter > 1) {
        html += other_link;
    }

    return html;
}

function resubmissionFormatter(value, row) {
    let html = "";
    if (row.resubmission) {
        html = "<span class='badge badge-success'>" + trans("yes") + "</span>";
    } else {
        html = "<span class='badge badge-danger'>" + trans("no") + "</span>";
    }
    return html;
}

function assignmentFileFormatter(value, row) {
    let html =
        "<a target='_blank' href='" +
        escapeHtml(safeUrl(row.file)) +
        "'>" +
        escapeHtml(row.name) +
        "</a>";
    return html;
}

function assignmentSubmissionStatusFormatter(value, row) {
    let html = "";
    // 0 = Pending/In Review , 1 = Accepted , 2 = Rejected , 3 = Resubmitted
    if (row.status === 0) {
        html =
            "<span class='badge badge-warning'>" + trans("pending") + "</span>";
    } else if (row.status === 1) {
        html =
            "<span class='badge badge-success'>" +
            trans("accepted") +
            "</span>";
    } else if (row.status === 2) {
        html =
            "<span class='badge badge-danger'>" + trans("rejected") + "</span>";
    } else if (row.status === 3) {
        html =
            "<span class='badge badge-warning'>" +
            trans("resubmitted") +
            "</span>";
    }
    return html;
}

function imageFormatter(value, row) {
    if (row.image) {
        return (
            "<a data-toggle='lightbox' href='" +
            escapeHtml(row.image) +
            "'><img src='" +
            escapeHtml(row.image) +
            "' class='img-fluid'  alt='image' onerror='onErrorImage(event)'  /></a>"
        );
    } else {
        return "-";
    }
}

function renderPersonCell(name, subtitle, image, url = null) {
    // escapeHtml (not $("<div>").text().html()) because these values are also
    // placed inside single-quoted title/alt attributes, so quotes must be escaped.
    const safeName = escapeHtml(name || "-");
    const safeSubtitle = escapeHtml(subtitle || "-");

    const truncate =
        "overflow:hidden;text-overflow:ellipsis;white-space:nowrap;display:block;";

    const avatar = image
        ? "<a data-toggle='lightbox' href='" +
          escapeHtml(image) +
          "' style='margin-right:8px;flex:none;line-height:0;'>" +
          "<img src='" +
          escapeHtml(image) +
          "' alt='" +
          safeName +
          "' class='rounded-circle' " +
          "style='width:36px;height:36px;object-fit:cover;' " +
          "onerror='onErrorAvatar(event)' />" +
          "</a>"
        : "";

    const nameHtml = url
        ? "<a href='" +
          escapeHtml(url) +
          "' class='font-weight-medium' style='" +
          truncate +
          "' title='" +
          safeName +
          "'>" +
          safeName +
          "</a>"
        : "<span class='font-weight-medium' style='" +
          truncate +
          "' title='" +
          safeName +
          "'>" +
          safeName +
          "</span>";

    return (
        "<div class='d-flex align-items-center' style='width:100%;min-width:0;'>" +
        avatar +
        "<div class='d-flex flex-column' style='min-width:0;flex:1 1 auto;'>" +
        nameHtml +
        "<small class='text-muted' style='" +
        truncate +
        "' title='" +
        safeSubtitle +
        "'>" +
        safeSubtitle +
        "</small>" +
        "</div>" +
        "</div>"
    );
}

function studentSubmissionNameFormatter(value, row, index, field) {
    // Read the raw field from the row (bootstrap-table pre-escapes `value` on tables with
    // data-escape="true"), then escape once below.
    const rawValue =
        field && row && Object.prototype.hasOwnProperty.call(row, field)
            ? row[field]
            : value;
    const name = String(rawValue || row.student_name || "");
    const safeName = escapeHtml(name);
    const imageUrl = row.student_image;

    const hasImage =
        imageUrl && imageUrl.length > 0 && !imageUrl.match(/\/storage\/?$/);

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
    initials = escapeHtml(initials);

    const initialsHtml = `<div class="d-flex align-items-center justify-content-center rounded-circle mr-2 bg-primary text-white font-weight-bold" style="width:36px;height:36px;font-size:14px;flex-shrink:0;">${initials}</div>`;

    if (hasImage) {
        const hiddenInitialsHtml = `<div class="align-items-center justify-content-center rounded-circle mr-2 bg-primary text-white font-weight-bold" style="width:36px;height:36px;font-size:14px;flex-shrink:0;display:none;">${initials}</div>`;
        return `<div class="d-flex align-items-center"><img src="${escapeHtml(imageUrl)}" class="rounded-circle mr-2" width="36" height="36" style="object-fit:cover;flex-shrink:0;" onerror="this.style.display='none';$(this).next().css('display', 'flex');">${hiddenInitialsHtml}<span>${safeName}</span></div>`;
    } else {
        return `<div class="d-flex align-items-center">${initialsHtml}<span>${safeName}</span></div>`;
    }
}

function studentInfoFormatter(value, row) {
    const name = row.full_name || value || "";
    const grNumber = row.admission_no || "-";
    const studentId = row.id || row.student_id;
    const studentUrl = studentId ? "/student-report/" + studentId : "#";
    return renderPersonCell(
        name,
        trans("gr_number") + ": " + grNumber,
        row.image,
        studentUrl,
    );
}

function userInfoFormatter(value, row) {
    const name =
        ((row.first_name || "") + " " + (row.last_name || "")).trim() ||
        value ||
        "";
    const email = row.email || "-";
    return renderPersonCell(name, email, row.image);
}

function personInfoFormatter(value, row, index, field) {
    const prefix = (field || "").replace(/_full_name$/, "");
    const name = value || row[prefix + "_full_name"] || "";
    const email = row[prefix + "_email"] || "-";
    const image = row[prefix + "_image"];
    return renderPersonCell(name, email, image);
}

function parentGuardianInfoFormatter(value, row) {
    const hasGuardian = !!(
        row.guardian_email ||
        row.guardian_image ||
        (row.guardian_full_name && row.guardian_full_name !== "-")
    );
    const prefix = hasGuardian ? "guardian" : "father";
    return renderPersonCell(
        row[prefix + "_full_name"] || "-",
        row[prefix + "_email"] || "-",
        row[prefix + "_image"],
        null,
    );
}

function renderPersonText(name, subtitle) {
    // escapeHtml (not $("<div>").text().html()) because these values are also
    // placed inside single-quoted title/alt attributes, so quotes must be escaped.
    const safeName = escapeHtml(name || "-");
    const safeSubtitle = escapeHtml(subtitle || "-");
    const truncate =
        "overflow:hidden;text-overflow:ellipsis;white-space:nowrap;display:block;";
    return (
        "<div class='d-flex flex-column' style='min-width:0;'>" +
        "<span class='font-weight-medium' style='" +
        truncate +
        "' title='" +
        safeName +
        "'>" +
        safeName +
        "</span>" +
        "<small class='text-muted' style='" +
        truncate +
        "' title='" +
        safeSubtitle +
        "'>" +
        safeSubtitle +
        "</small>" +
        "</div>"
    );
}

function genderFormatter(value, row) {
    const gender = (value || "").toString().toLowerCase();
    if (gender === "male") {
        return "Male";
    }
    if (gender === "female") {
        return "Female";
    }
    return value ? $("<div>").text(value).html() : "-";
}

function parentGuardianTextFormatter(value, row) {
    const hasGuardian = !!(
        row.guardian_email ||
        row.guardian_image ||
        (row.guardian_full_name && row.guardian_full_name !== "-")
    );
    const prefix = hasGuardian ? "guardian" : "father";
    return renderPersonText(
        row[prefix + "_full_name"] || "-",
        row[prefix + "_email"] || "-",
    );
}

function notificationImageFormatter(value, row) {
    if (row.image) {
        const imageURL = "storage/" + row.image;
        return (
            "<a data-toggle='lightbox' href='" +
            escapeHtml(imageURL) +
            "'><img src='" +
            escapeHtml(imageURL) +
            "' class='img-fluid'  alt='image' onerror='onErrorImage(event)'  /></a>"
        );
    } else {
        return "-";
    }
}

function thumbnailFormatter(value, row) {
    if (row.thumbnail) {
        return (
            "<a data-toggle='lightbox' href='" +
            escapeHtml(row.thumbnail) +
            "'><img src='" +
            escapeHtml(row.thumbnail) +
            "' class='img-fluid'  alt='image' onerror='onErrorImage(event)'  /></a>"
        );
    } else {
        return "-";
    }
}

function fatherImageFormatter(value, row) {
    if (row.father_image) {
        return (
            "<a data-toggle='lightbox' href='" +
            escapeHtml(row.father_image) +
            "'><img src='" +
            escapeHtml(row.father_image) +
            "' class='img-fluid'  alt='image' onerror='onErrorImage(event)'/></a>"
        );
    }
}

function motherImageFormatter(value, row) {
    if (row.mother_image) {
        return (
            "<a data-toggle='lightbox' href='" +
            escapeHtml(row.mother_image) +
            "'><img src='" +
            escapeHtml(row.mother_image) +
            "' class='img-fluid'  alt='image' onerror='onErrorImage(event)'/></a>"
        );
    }
}

function guardianImageFormatter(value, row) {
    if (row.guardian_image) {
        return (
            "<a data-toggle='lightbox' href='" +
            escapeHtml(row.guardian_image) +
            "'><img src='" +
            escapeHtml(row.guardian_image) +
            "' class='img-fluid'  alt='image' onerror='onErrorImage(event)'/></a>"
        );
    }
}

function examTimetableFormatter(value, row) {
    let html = [];
    if (row.timetable.length != null) {
        $.each(row.timetable, function (key, timetable) {
            html.push(
                "<p>" +
                    escapeHtml(timetable.subject.name) +
                    "(" +
                    escapeHtml(timetable.subject.type) +
                    ")  - " +
                    escapeHtml(timetable.total_marks) +
                    "/" +
                    escapeHtml(timetable.passing_marks) +
                    " - " +
                    escapeHtml(timetable.start_time) +
                    " - " +
                    escapeHtml(timetable.end_time) +
                    " - " +
                    moment(timetable.date, "YYYY-MM-DD").format("DD-MM-YYYY") +
                    "</p>",
            );
        });
    }
    return html.join("");
}

function examSubjectFormatter(index, row) {
    if (row.subject_name) {
        return escapeHtml(row.subject_name);
    } else {
        return escapeHtml($("#subject_id :selected").text());
    }
}

function examStudentNameFormatter(index, row) {
    return (
        "<input type='hidden' name='exam_marks[" +
        escapeHtml(row.no) +
        "][student_id]' class='form-control' value='" +
        escapeHtml(row.student_id) +
        "' />" +
        escapeHtml(row.student_name)
    );
}

function obtainedMarksFormatter(index, row) {
    if (row.obtained_marks) {
        return (
            "<input type='hidden' name='exam_marks[" +
            escapeHtml(row.no) +
            "][exam_marks_id]' class='form-control' value='" +
            escapeHtml(row.exam_marks_id) +
            "' />" +
            "<input type='number' name='exam_marks[" +
            escapeHtml(row.no) +
            "][obtained_marks]' class='form-control' min='0' value='" +
            escapeHtml(row.obtained_marks) +
            "' />" +
            "<input type='hidden' name='exam_marks[" +
            escapeHtml(row.no) +
            "][total_marks]' class='form-control' value='" +
            parseInt(row.total_marks) +
            "' />"
        );
    } else {
        return (
            "<input type='hidden' name='exam_marks[" +
            escapeHtml(row.no) +
            "][exam_marks_id]' class='form-control' value='" +
            escapeHtml(row.exam_marks_id) +
            "' />" +
            "<input type='number' name='exam_marks[" +
            escapeHtml(row.no) +
            "][obtained_marks]' class='form-control' min='0' value='" +
            " " +
            "' />" +
            "<input type='hidden' name='exam_marks[" +
            escapeHtml(row.no) +
            "][total_marks]' class='form-control' value='" +
            parseInt(row.total_marks) +
            "' />"
        );
    }
}

function teacherReviewFormatter(index, row) {
    if (row.teacher_review) {
        return (
            "<textarea name='exam_marks[" +
            escapeHtml(row.no) +
            "][teacher_review]' class='form-control'>" +
            escapeHtml(row.teacher_review) +
            "</textarea>"
        );
    } else {
        return (
            "<textarea name='exam_marks[" +
            escapeHtml(row.no) +
            "][teacher_review]' class='form-control'>" +
            " " +
            "</textarea>"
        );
    }
}

function examPublishFormatter(index, row) {
    if (index == 0) {
        return "<span class='badge badge-danger'>" + trans("no") + "</span>";
    } else {
        return "<span class='badge badge-success'>" + trans("yes") + "</span>";
    }
}

function eventFormatter(value, row) {
    let count = 1;
    let html = "<div style='line-height: 20px;'>";
    $.each(row.description, function (key, value) {
        if (value) {
            html +=
                "<br>" +
                count +
                ". " +
                escapeHtml(value.title) +
                ". " +
                escapeHtml(value.start_time) +
                " - " +
                escapeHtml(value.end_time);
            count++;
        }
    });
    html += "</div>";
    return html;
}

function descriptionFormatter(value, row) {
    let count = 1;
    let html = "<div style='line-height: 20px;'>";
    if (row.type == "multiple") {
        $.each(row.timing, function (key, value) {
            if (value) {
                html += "<br>" + count + ". " + escapeHtml(value.description);
                count++;
            }
        });
    } else {
        html += "<br>" + escapeHtml(row.description);
    }

    html += "</div>";
    return html;
}

function classTeacherFormatter(value, row) {
    let class_teacher_count = 1;
    let html = "<div style='line-height: 20px;'>";
    $.each(row.teachers, function (key, value) {
        if (value) {
            html += "<br>" + class_teacher_count + ". " + escapeHtml(value);
            class_teacher_count++;
        }
    });
    html += "</div>";
    return html;
}

function coreSubjectFormatter(value, row) {
    console.log(row.core_subjects);
    if (row.include_semesters == 1) {
        let core_subject_count = 1;
        let html = "<div style='line-height: 20px;'>";
        let previous_sem_id = null;

        $.each(row.core_subjects, function (key, value) {
            if (value.semester_id !== previous_sem_id) {
                // Add the semester name only if it's different from the previous one
                html += `<br><strong>${escapeHtml(value?.semester?.name ?? "")}</strong><br>`;
                previous_sem_id = value.semester_id; // Update the previous_sem_id
                core_subject_count = 1;
            }

            if (value.subject) {
                // Add the subject details
                html +=
                    core_subject_count +
                    ". " +
                    escapeHtml(value.subject.name) +
                    " - " +
                    escapeHtml(value.subject.type);
                core_subject_count++;
            }
            html += "<br>";
        });

        html += "</div>";

        return html;
    } else {
        let core_subject_count = 1;
        let html = "<div style='line-height: 20px;'>";
        $.each(row.core_subjects, function (key, value) {
            if (value.subject) {
                html +=
                    "<br>" +
                    core_subject_count +
                    ". " +
                    escapeHtml(value.subject.name) +
                    " - " +
                    escapeHtml(value.subject.type);
                core_subject_count++;
            }
        });
        html += "</div>";

        return html;
    }
}

function electiveSubjectFormatter(value, row) {
    let html = "<div style='line-height: 20px;'>";
    let previous_sem_id = null;

    if (row.include_semesters == 1) {
        let semesterGroupCounts = {};
        let previous_sem_id = null;

        $.each(row.elective_subject_groups, function (key, group) {
            let elective_subject_count = 1;

            $.each(group.elective_subjects, function (key, elective_subject) {
                let semester_name =
                    elective_subject && elective_subject.semester
                        ? elective_subject.semester.name
                        : "";

                if (!semesterGroupCounts[semester_name]) {
                    semesterGroupCounts[semester_name] = 0;
                }

                if (
                    elective_subject.semester_id !== previous_sem_id ||
                    key === 0
                ) {
                    semesterGroupCounts[semester_name]++;
                    html +=
                        "<br><strong>" +
                        escapeHtml(semester_name) +
                        "</strong> (<b>Group " +
                        semesterGroupCounts[semester_name] +
                        "</b>)<br>";
                    previous_sem_id = elective_subject.semester_id;
                    elective_subject_count = 1;
                }

                html +=
                    elective_subject_count +
                    ". " +
                    escapeHtml(elective_subject.subject.name) +
                    " - " +
                    escapeHtml(elective_subject.subject.type) +
                    "<br>";
                elective_subject_count++;
            });

            html +=
                "<b>Total Subjects : </b>" +
                escapeHtml(group.total_subjects) +
                "<br>";
            html +=
                "<b>Total Selectable Subjects : </b>" +
                escapeHtml(group.total_selectable_subjects) +
                "<br><br>";
        });

        html += "</div>";
        return html;
    } else {
        let html = "<div style='line-height: 20px;'>";
        $.each(row.elective_subject_groups, function (key, group) {
            let elective_subject_count = 1;
            html += "<b>Group " + (key + 1) + "</b><br>";

            $.each(group.elective_subjects, function (key, elective_subject) {
                html +=
                    elective_subject_count +
                    ". " +
                    escapeHtml(elective_subject.subject.name) +
                    " - " +
                    escapeHtml(elective_subject.subject.type) +
                    "<br>";
                elective_subject_count++;
            });

            html +=
                "<b>Total Subjects : </b>" +
                escapeHtml(group.total_subjects) +
                "<br>";
            html +=
                "<b>Total Selectable Subjects : </b>" +
                escapeHtml(group.total_selectable_subjects) +
                "<br><br>";
        });
        html += "</div>";
        return html;
    }
}

// Show the detail-view expand (+/-) icon only for classes that have semesters enabled.
function subjectSemesterDetailFilter(index, row) {
    return row.include_semesters == 1;
}

// Renders the semester-wise subject breakdown when a class row is expanded.
// Content is loaded over AJAX using the row's class id and the selected session year.
function subjectSemesterDetailFormatter(index, row, $detail) {
    var escapeHtml = function (value) {
        return $("<div>")
            .text(value == null ? "" : value)
            .html();
    };

    var $table = $("#table_list");
    var baseUrl = $table.data("semester-details-url");
    var sessionYearId =
        $(".filter_session_year").val() ||
        $("#filter_session_year_global").val();

    // bootstrap-table appends this formatter's return value into the detail cell,
    // so we return the loader markup and fill it in once the AJAX resolves.
    var loadingHtml =
        '<div class="cs-detail-wrap">' +
        '<div class="cs-detail-state">' +
        '<span class="cs-detail-spinner"></span>' +
        "<span>" +
        trans("loading") +
        "</span>" +
        "</div></div>";

    $.ajax({
        url: baseUrl + "/" + row.id,
        type: "GET",
        dataType: "json",
        data: { session_year_id: sessionYearId },
        success: function (response) {
            var $wrap = $detail.find(".cs-detail-wrap");
            if (
                response.error ||
                !response.semesters ||
                !response.semesters.length
            ) {
                $wrap.html(
                    '<div class="cs-detail-state cs-detail-empty">' +
                        trans("no_data_found") +
                        "</div>",
                );
                return;
            }

            var html = '<div class="cs-semester-grid">';

            $.each(response.semesters, function (sKey, semester) {
                html += '<div class="cs-semester-card">';
                html +=
                    '<div class="cs-semester-head"><span class="cs-semester-name">' +
                    escapeHtml(semester.name) +
                    "</span></div>";
                html += '<div class="cs-semester-body">';

                // Core subjects section
                html += '<div class="cs-section">';
                html +=
                    '<div class="cs-section-title">' +
                    trans("core_subject") +
                    "</div>";
                if (semester.core_subjects && semester.core_subjects.length) {
                    html += '<ol class="cs-subject-list">';
                    $.each(semester.core_subjects, function (cKey, subject) {
                        html +=
                            '<li><span class="cs-subject-name">' +
                            escapeHtml(subject.name) +
                            "</span>" +
                            '<span class="cs-subject-type">' +
                            escapeHtml(subject.type) +
                            "</span></li>";
                    });
                    html += "</ol>";
                } else {
                    html +=
                        '<div class="cs-section-empty">' +
                        trans("no_subjects_found") +
                        "</div>";
                }
                html += "</div>";

                // Elective subject groups section
                html += '<div class="cs-section">';
                html +=
                    '<div class="cs-section-title">' +
                    trans("elective_subject") +
                    "</div>";
                if (
                    semester.elective_groups &&
                    semester.elective_groups.length
                ) {
                    html += '<div class="cs-elective-groups">';
                    $.each(semester.elective_groups, function (gKey, group) {
                        html += '<div class="cs-elective-group">';
                        html +=
                            '<div class="cs-group-head">' +
                            trans("group") +
                            " " +
                            (gKey + 1) +
                            "</div>";
                        if (group.subjects && group.subjects.length) {
                            html += '<ol class="cs-subject-list">';
                            $.each(group.subjects, function (eKey, subject) {
                                html +=
                                    '<li><span class="cs-subject-name">' +
                                    escapeHtml(subject.name) +
                                    "</span>" +
                                    '<span class="cs-subject-type">' +
                                    escapeHtml(subject.type) +
                                    "</span></li>";
                            });
                            html += "</ol>";
                        }
                        html +=
                            '<div class="cs-group-meta">' +
                            "<span><b>" +
                            trans("total_selectable_subjects") +
                            ":</b> " +
                            escapeHtml(group.total_selectable_subjects) +
                            "</span>" +
                            "<span><b>" +
                            trans("subjects") +
                            ":</b> " +
                            escapeHtml(group.total_subjects) +
                            "</span>" +
                            "</div>";
                        html += "</div>";
                    });
                    html += "</div>"; // .cs-elective-groups
                } else {
                    html +=
                        '<div class="cs-section-empty">' +
                        trans("no_subjects_found") +
                        "</div>";
                }
                html += "</div>";

                html += "</div>"; // .cs-semester-body
                html += "</div>"; // .cs-semester-card
            });

            html += "</div>";
            $wrap.html(html);
        },
        error: function () {
            $detail
                .find(".cs-detail-wrap")
                .html(
                    '<div class="cs-detail-state cs-detail-empty">' +
                        trans("no_data_found") +
                        "</div>",
                );
        },
    });

    return loadingHtml;
}

function defaultYearFormatter(index, row) {
    if (index == 0) {
        return "<span class='badge badge-danger'>" + trans("no") + "</span>";
    } else {
        return "<span class='badge badge-success'>" + trans("yes") + "</span>";
    }
}

function feesTypeChoiceable(index, row) {
    if (row.choiceable) {
        return "<span class='badge badge-success'>" + trans("yes") + "</span>";
    } else {
        return "<span class='badge badge-danger'>" + trans("no") + "</span>";
    }
}

function feesTypeFormatter(index, row) {
    let html = [];
    if (row.fees_type.length) {
        let no = 1;
        $.each(row.fees_type, function (key, value) {
            html.push(
                "<span>" +
                    no +
                    ". " +
                    escapeHtml(value.fees_name) +
                    " - " +
                    escapeHtml(value.amount) +
                    "</span><br>",
            );
            no++;
        });
    } else {
        html.push("<p class='text-center'>-</p>");
    }
    return html.join("");
}

function feesPaidModeFormatter(index, row) {
    if (row.mode != null) {
        if (row.mode == 0) {
            return "<span class='badge badge-info'>" + lang_cash + "</span>";
        } else if (row.mode == 1) {
            return (
                "<span class='badge badge-warning'>" + lang_cheque + "</span>"
            );
        } else {
            return (
                "<span class='badge badge-success'>" + lang_online + "</span>"
            );
        }
    }
}

function feesOnlineTransactionLogParentGateway(index, row) {
    if (row.payment_gateway == 1) {
        return "<span class='badge badge-info'>RazorPay</span>";
    } else if (row.payment_gateway == 2) {
        return "<span class='badge badge-primary'>Stripe</span>";
    } else if (row.payment_gateway == 3) {
        return "<span class='badge badge-dark'>Paystack</span>";
    } else if (row.payment_gateway == 4) {
        return "<span class='badge badge-warning'>Flutterwave</span>";
    } else {
        return " ";
    }
}

function feesOnlineTransactionLogPaymentStatus(index, row) {
    if (row.payment_status == 1) {
        return "<span class='badge badge-success'>" + lang_success + "</span>";
    } else if (row.payment_status == 2) {
        return "<span class='badge badge-warning'>" + lang_pending + "</span>";
    } else {
        return "<span class='badge badge-danger'>" + lang_failed + "</span>";
    }
}

function questionTypeFormatter(index, row) {
    if (row.question_type) {
        return (
            "<span class='badge badge-secondary'>" +
            lang_equation_based +
            "</span>"
        );
    } else {
        return (
            "<span class='badge badge-info'>" + lang_simple_question + "</span>"
        );
    }
}

// Online-exam question text. Equation questions (question_type = 1) are CKEditor/MathJax
// markup that the server wraps in an .equation-editor-inline div, so they stay as HTML;
// simple questions are plain text and are escaped.
function onlineExamQuestionText(text, row) {
    return row && row.question_type ? text : escapeHtml(text);
}

function onlineExamQuestionFormatter(value, row) {
    return onlineExamQuestionText(row.question, row);
}

function optionsFormatter(index, row) {
    let html = "";
    $.each(row.options, function (index, value) {
        html += "<div class='row'>";
        html +=
            "<div class= 'col-md-1 text-center'><i class='fa fa-arrow-right small' aria-hidden='true'></i></div><div class='col-md-6'>" +
            onlineExamQuestionText(value.option, row) +
            "</div><br>";
        html += "</div>";
    });
    return html;
}

function answersFormatter(index, row) {
    let html = "";
    $.each(row.answers, function (index, value) {
        html += "<div class='row'>";
        html +=
            "<span class= 'col-md-1 text-center'><i class='fa fa-arrow-right small' aria-hidden='true'></i></span><div class='col-md-6'>" +
            onlineExamQuestionText(value.answer, row) +
            "</div><br>";
        html += "</div>";
    });
    return html;
}

function feesPaidStatusFormatter(value, row, index) {
    if (row.fees_status == 1) {
        return "<span class='badge badge-success'>" + lang_success + "</span>";
    } else if (row.fees_status == 0) {
        return (
            "<span class='badge badge-info'>" + lang_partial_paid + "</span>"
        );
    } else {
        return "<span class='badge badge-warning'>" + lang_pending + "</span>";
    }
}

function textFormatter(value, row) {
    if (row.text) {
        if (row.text.length > 20) {
            return "<p>" + escapeHtml(row.text.substring(0, 100)) + "..." + "</p>";
        } else {
            return "<p>" + escapeHtml(row.text) + "</p>";
        }
    } else {
        return "-";
    }
}

function messageFormatter(value, row) {
    // Contact-us messages come from the public website form: always escape.
    if (row.message) {
        if (row.message.length > 20) {
            return (
                "<p>" + escapeHtml(row.message.substring(0, 50)) + "..." + "</p>"
            );
        } else {
            return "<p>" + escapeHtml(row.message) + "</p>";
        }
    } else {
        return "-";
    }
}

function shiftStatusFormatter(value, row, index) {
    if (row.status == 1) {
        return "<span class='badge badge-success'>" + lang_active + "</span>";
    } else {
        return "<span class='badge badge-danger'>" + lang_inactive + "</span>";
    }
}

function StatusFormatter(value, row, index) {
    if (row.status == 1) {
        return "<span class='badge badge-success'>" + lang_enable + "</span>";
    } else {
        return "<span class='badge badge-danger'>" + lang_disable + "</span>";
    }
}

function sliderTypeFormatter(index, row) {
    if (row.type == 1) {
        return "<span class='badge badge-success'>" + trans("app") + "</span>";
    } else if (row.type == 2) {
        return (
            "<span class='badge badge-secondary'>" + trans("web") + "</span>"
        );
    } else if (row.type == 3) {
        return "<span class='badge badge-warning'>" + trans("both") + "</span>";
    } else {
        return "";
    }
}

$("#filter_exam_id").on("change", function () {
    $("#table_list").bootstrapTable("refresh");
});

$("#filter_medium_id").on("change", function () {
    $("#table_list").bootstrapTable("refresh");
});

$("#filter_shift_id").on("change", function () {
    $("#table_list").bootstrapTable("refresh");
});

$("#filter_subject_id").on("change", function () {
    $("#table_list").bootstrapTable("refresh");
});

$("#filter_class_id").on("change", function () {
    $("#table_list").bootstrapTable("refresh");
});

$("#filter_mode").on("change", function () {
    $("#table_list").bootstrapTable("refresh");
});

$("#filter_class_section_id").on("change", function () {
    $("#table_list").bootstrapTable("refresh");
});

$("#filter_teacher_id").on("change", function () {
    $("#table_list").bootstrapTable("refresh");
});

$("#filter_subject_id").on("change", function () {
    $("#table_list").bootstrapTable("refresh");
});

$("#filter-question-class-section-id").on("change", function () {
    $("#table_list_questions").bootstrapTable("refresh");
});

$("#filter-question-subject-id").on("change", function () {
    $("#table_list_questions").bootstrapTable("refresh");
});

$("#filter-view-question-class-id").on("change", function () {
    $("#table_list_questions").bootstrapTable("refresh");
});

function reasonFormatter(value, row) {
    if (row.reason) {
        if (row.reason.length > 20) {
            return "<p>" + escapeHtml(row.reason.substring(0, 30)) + "..." + "</p>";
        } else {
            return "<p>" + escapeHtml(row.reason) + "</p>";
        }
    } else {
        return "-";
    }
}

function startMonthFormatter(value, row) {
    const months = [
        "January",
        "February",
        "March",
        "April",
        "May",
        "June",
        "July",
        "August",
        "September",
        "October",
        "November",
        "December",
    ];

    // Check if start_month or end_month is within the valid range (1-12)
    if (row.start_month >= 1 && row.start_month <= 12) {
        return trans(months[row.start_month - 1]);
    } else {
        return "-";
    }
}

function endMonthFormatter(value, row) {
    const months = [
        "January",
        "February",
        "March",
        "April",
        "May",
        "June",
        "July",
        "August",
        "September",
        "October",
        "November",
        "December",
    ];

    // Check if start_month or end_month is within the valid range (1-12)
    if (row.end_month >= 1 && row.end_month <= 12) {
        return trans(months[row.end_month - 1]);
    } else {
        return "-";
    }
}

function semesterStatusFormatter(value, row) {
    // return `<span class="alert alert-${row.status ? "success" : "danger"}">${trans(row.status ? "yes" : "NO")}</span>`;

    return `<span class="alert alert-${row.status ? "success" : "danger"}">${row.status ? trans("YES") : trans("NO")}</span>`;
}

function semesterFormatter(value, row) {
    if (row.include_semesters === null) {
        return (
            "<span class='badge badge-warning'>" +
            trans("Not Configured") +
            "</span>"
        );
    } else if (row.include_semesters) {
        return "<span class='badge badge-success'>" + trans("YES") + "</span>";
    } else {
        return "<span class='badge badge-danger'>" + trans("NO") + "</span>";
    }
}

function formFieldRequiredFormatter(index, row) {
    if (row.is_required) {
        return "<span class='badge badge-success'>" + trans("yes") + "</span>";
    } else {
        return "<span class='badge badge-danger'>" + trans("no") + "</span>";
    }
}

function forFormFormatter(index, row) {
    if (row.for == 1) {
        return "<span class='badge badge-info'>" + trans("student") + "</span>";
    } else if (row.for == 2) {
        return (
            "<span class='badge badge-warning'>" + trans("parent") + "</span>"
        );
    } else if (row.for == 3) {
        return (
            "<span class='badge badge-success'>" + trans("teacher") + "</span>"
        );
    } else if (row.for == 4) {
        return (
            "<span class='badge badge-primary'>" +
            trans("Self Student Registration") +
            "</span>"
        );
    } else {
        return "-";
    }
}

function typeFormFormatter(index, row) {
    var typeLabels = {
        text: trans("Text"),
        number: trans("Numeric"),
        dropdown: trans("Dropdown"),
        radio: trans("Radio Button"),
        checkbox: trans("Checkbox"),
        textarea: trans("Textarea"),
        file: trans("File Upload"),
    };
    return typeLabels[row.type] || escapeHtml(row.type) || "-";
}

function formFieldDefaultValuesFormatter(index, row) {
    let html = "";
    if (row.default_values && row.default_values.length) {
        html += "<ul>";
        $.each(row.default_values, function (index, value) {
            if (value != null) {
                html +=
                    "<i class='fa fa-arrow-right' aria-hidden='true'></i> " +
                    escapeHtml(value) +
                    "<br>";
            }
        });
    } else {
        html = '<div class="text-center">-</div>';
    }
    return html;
}

function formFieldOtherValueFormatter(index, row) {
    let otherObj = JSON.parse(row.other);
    let html = "";
    if (otherObj) {
        html += "<ul>";
        otherObj.forEach((value) => {
            Object.entries(value).forEach(([key, data]) => {
                html +=
                    "<i class='fa fa-arrow-right' aria-hidden='true'></i> " +
                    escapeHtml(key) +
                    " - " +
                    escapeHtml(data) +
                    "<br>";
            });
        });
    } else {
        html = '<div class="text-center">-</div>';
    }
    return html;
}

function leaveStatusFormatter(value) {
    if (value == 0) {
        return (
            "<span class='badge badge-warning'>" + trans("pending") + "</span>"
        );
    } else if (value == 1) {
        return (
            "<span class='badge badge-success'>" + trans("approved") + "</span>"
        );
    } else {
        return (
            "<span class='badge badge-danger'>" + trans("rejected") + "</span>"
        );
    }
}
function resultStatusFormatter(value, row) {
    if (value == 0) {
        return (
            "<span class='badge badge-danger border rounded border-danger text-danger bg-transparent'>" +
            trans("fail") +
            "</span>"
        );
    } else if (value == 1) {
        return (
            "<span class='badge badge-success border rounded border-success text-success bg-transparent'>" +
            trans("pass") +
            "</span>"
        );
    } else {
        return (
            "<span class='badge badge-warning border rounded border-warning text-warning bg-transparent'>" +
            trans("pending") +
            "</span>"
        );
    }
}
function continueStatusFormatter(value, row) {
    if (value == 0) {
        return (
            "<span class='badge badge-danger border rounded border-danger text-danger bg-transparent'>" +
            trans("no") +
            "</span>"
        );
    } else if (value == 1) {
        return (
            "<span class='badge badge-success border rounded border-success text-success bg-transparent'>" +
            trans("yes") +
            "</span>"
        );
    } else {
        return "<span>" + "-" + "</span>";
    }
}
function holidayDaysFormatter(value, row) {
    return value
        .split(",")
        .map((day) => trans(day))
        .join(", ");
}

function eventDescriptionFormatter(value, row) {
    return `<div class="text-wrap">${escapeHtml(row.description)}</div>`;
}
function studentResultFormatterold(value, row, index) {
    const val = row.studentResult;
    if (!val) {
        return "<span class='text-muted'>—</span>";
    }

    let percent = val.percentage ?? 0;
    let grade = val.grade ?? "-";

    return `
        <span>
           ${percent}% (${grade})
        </span>
    `;
}

function studentResultFormatter(value, row, index) {
    const val = row.studentResult;

    if (!val) {
        return "<span class='text-muted'>—</span>";
    }

    let percent = val.percentage ?? 0;
    let grade = val.grade ?? "-";

    return `
        <div class="d-flex align-items-center gap-2">
            <span>${escapeHtml(percent)}% (${escapeHtml(grade)})</span>
            <button
              class="btn btn-sm p-0 text-primary view-exam-result ml-1" 
              id="view-exam-result"
              data-student-id="${escapeHtml(row.id)}"
              data-class_section-id="${escapeHtml(val.class_section_id)}"
            >
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-eye" viewBox="0 0 16 16">
                  <path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8M1.173 8a13 13 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5s3.879 1.168 5.168 2.457A13 13 0 0 1 14.828 8q-.086.13-.195.288c-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5s-3.879-1.168-5.168-2.457A13 13 0 0 1 1.172 8z"/>
                  <path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5M4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0"/>
                </svg>
            </button>
            </div>
        `;
}

function updateBtnFormatter(value, row, index) {
    return value;
}

function amountFormatter(value) {
    const number = Number(value);
    return Number.isFinite(number) ? number.toLocaleString() : "-";
}

function onlineRegistrationStatusFormatter(value, row) {
    if (value == 2) {
        return (
            '<span class="badge badge-danger">' + trans("rejected") + "</span>"
        );
    }
    return '<span class="badge badge-warning">' + trans("pending") + "</span>";
}

function languageRtlStatusFormatter(value, row) {
    let html = "";
    if (row.rtl) {
        html = "<span class='badge badge-success'>" + trans("yes") + "</span>";
    } else {
        html = "<span class='badge badge-danger'>" + trans("no") + "</span>";
    }
    return html;
}

function notificationTypeFormatter(value) {
    var labels = {
        custom: trans("custom"),
        leave: trans("leave"),
        assignment: trans("assignment"),
        assignment_submission: trans("assignment_submission"),
        "fees-due": trans("fees_due"),
        online: trans("online"),
        Online: trans("online"),
        attendance: trans("attendance"),
    };
    return labels[value] || value;
}
