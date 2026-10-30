"use strict";
//Bootstrap actionEvents
window.lessonEvents = {
    'click .edit-data': function (e, value, row, index) {
        //Reset Values
        $('.edit-extra-files').html('')
        $('.edit_file_type_div').show();
        $('#edit_id').val(row.id);
        $('#edit_class_section_id').html('<option value="' + row.class_section_id + '" selected>' + row.class_section_name + '</option>');
        $('#edit_subject_id').html('<option value="' + row.subject_id + '" data-term="' + (row.term_id || '') + '" selected>' + row.subject_name + '</option>');
        // Show/populate the term dropdown only when the class has terms.
        loadLessonEditTerm(row);
        $('#edit_name').val(row.name);
        $('#edit_description').val(row.description);
        if (row.file.length > 0) {
            $.each(row.file, function (key, data) {
                let html = '';
                if (key == 0) {
                    html = $('.edit_file_type_div');
                } else {
                    html = $('.edit_file_type_div:last').clone().show();
                    $('.edit-extra-files').append(html);
                }
                html.removeAttr('id');
                html.find('.error').remove();
                html.find('.has-danger').removeClass('has-danger');
                // This function will replace the last index value and increment in the multidimensional name attribute
                html.find(':input').each(function (key, element) {
                    this.name = this.name.replace(/\[(\d+)\]/, function (str, p1) {
                        return '[' + (parseInt(p1, 10) + 1) + ']';
                    });
                })

                // html.find('.edit-lesson-file i').addClass('fa-times').removeClass('fa-plus');
                html.find('.remove-lesson-file').attr('data-id', data.id);
                html.find('#edit_file_id').val(data.id);

                //1 = File Upload , 2 = Youtube Link , 3 = Uploaded Video , 4 = Other Link
                if (data.type == 1) {
                    // 1 = File Ulopad
                    html.find('#edit_file_type').val('file_upload').trigger('change');
                    html.find('#file_preview').attr('href', safeUrl(data.file_url)).text(data.file_name);
                    //Used class name as a selector instead of id because of jquery dynamic field validation.
                    html.find('.file_name').val(data.file_name);
                } else if (data.type == 2) {
                    // 2 = Youtube Link
                    html.find('#edit_file_type').val('youtube_link').trigger('change');
                    html.find('#file_thumbnail_preview').attr('src', data.file_thumbnail);
                    html.find('.file_link').val(data.file_url);

                    html.find('.file_name').val(data.file_name);
                } else if (data.type == 3) {
                    // 3 = Uploaded Video
                    html.find('#edit_file_type').val('video_upload').trigger('change');
                    html.find('#file_thumbnail_preview').attr('src', data.file_thumbnail);
                    html.find('#file_preview').attr('src', data.file_url).text(data.file_name);

                    html.find('.file_name').val(data.file_name);
                } else if (data.type == 4) {
                    // 4 = Other Link
                    html.find('#edit_file_type').val('other_link').trigger('change');
                    html.find('#file_thumbnail_preview').attr('src', data.file_thumbnail);

                    html.find('.file_name').val(data.file_name);
                    html.find('.file_link').val(data.file_url);
                }
            })
        } else {
            $('.edit_file_type_div').hide();
        }
    }
};


window.topicEvents = {
    'click .edit-data': function (e, value, row, index) {
        //Reset Values
        $('.edit-extra-files').html('')
        $('.edit_file_type_div').show();
        $('#edit_id').val(row.id);
        $('#edit_topic_class_section_id').html('<option value="' + row.class_section_id + '" selected>' + row.class_section_name + '</option>');
        $('#edit_topic_subject_id').html('<option value="' + row.subject_id + '" selected>' + row.subject_name + '</option>');
        $('#edit_topic_subject_id').trigger('change');
        setTimeout(() => {
            $('#edit_topic_lesson_id').val(row.lesson_id);
        }, 500);
        $('#edit_name').val(row.name);
        $('#edit_description').val(row.description);

        if (row.file.length > 0) {
            $.each(row.file, function (key, data) {
                let html = '';
                if (key == 0) {
                    html = $('.edit_file_type_div');
                } else {
                    html = $('.edit_file_type_div:last').clone().show();
                    $('.edit-extra-files').append(html);
                }
                html.removeAttr('id');
                html.find('.error').remove();
                html.find('.has-danger').removeClass('has-danger');
                // This function will replace the last index value and increment in the multidimensional name attribute
                html.find(':input').each(function (key, element) {
                    this.name = this.name.replace(/\[(\d+)\]/, function (str, p1) {
                        return '[' + (parseInt(p1, 10) + 1) + ']';
                    });
                })

                // html.find('.edit-lesson-file i').addClass('fa-times').removeClass('fa-plus');
                // html.find('.edit-lesson-file').addClass('btn-inverse-danger remove-lesson-file').removeClass('btn-inverse-success edit-lesson-file').attr('data-id', data.id);

                html.find('.remove-lesson-file').attr('data-id', data.id);
                html.find('#edit_file_id').val(data.id);

                //1 = File Upload , 2 = Youtube Link , 3 = Uploaded Video , 4 = Other Link
                if (data.type == 1) {
                    // 1 = File Ulopad
                    html.find('#edit_file_type').val('file_upload').trigger('change');
                    html.find('#file_preview').attr('href', safeUrl(data.file_url)).text(data.file_name);
                    //Used class name as a selector instead of id because of jquery dynamic field validation.
                    html.find('.file_name').val(data.file_name);
                } else if (data.type == 2) {
                    // 2 = Youtube Link
                    html.find('#edit_file_type').val('youtube_link').trigger('change');
                    html.find('#file_thumbnail_preview').attr('src', data.file_thumbnail);
                    html.find('.file_link').val(data.file_url);

                    html.find('.file_name').val(data.file_name);
                } else if (data.type == 3) {
                    // 3 = Uploaded Video
                    html.find('#edit_file_type').val('video_upload').trigger('change');
                    html.find('#file_thumbnail_preview').attr('src', data.file_thumbnail);
                    html.find('#file_preview').attr('src', data.file_url).text(data.file_name);

                    html.find('.file_name').val(data.file_name);
                } else if (data.type == 4) {
                    // 4 = Other Link
                    html.find('#edit_file_type').val('other_link').trigger('change');
                    html.find('#file_thumbnail_preview').attr('src', data.file_thumbnail);

                    html.find('.file_name').val(data.file_name);
                    html.find('.file_link').val(data.file_url);
                }
            })
        } else {
            $('.edit_file_type_div').hide();
        }
    }
};

window.examEvents = {
    'click .publish-exam-result': function (e, value, row, index) {
        e.preventDefault();
        // alert('working');
        Swal.fire({
            title: trans('Are you sure?'),
            text: trans('You won\'t be able to revert this!'),
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            cancelButtonText: trans('Cancel'),
            confirmButtonText: trans('Confirm!')
        }).then((result) => {
            if (result.isConfirmed) {
                let url = baseUrl + '/exams/publish/' + row.id;

                function successCallback(response) {
                    showSuccessToast(response.message);
                    $('#table_list').bootstrapTable('refresh');
                }
                function errorCallback(response) {
                    showErrorToast(response.message);
                }

                ajaxRequest('POST', url, null, null, successCallback, errorCallback);
            }
        })
    },
    'click .edit-data': function (e, value, row, index) {
        //Reset to Old Values
        $('.edit-extra-timetable').html('');
        $('.edit_exam_timetable').show();
        $('#edit_id').val(row.id);
        $('#edit_class_id').val(row.class_id);
        $('#edit_name').val(row.name);
        $('#edit_description').val(row.description);
        $('.js-example-basic-multiple').select2({
            placeholder: "Please Select",
            dropdownParent: $("#editModal"),

        });
        $('#edit_class_id').on('select2:unselecting', function (e) {
            // alert('working');
            e.preventDefault();

            var exam_id = document.getElementById('edit_id').value;
            let class_id = e.params.args.data.id;

            Swal.fire({
                title: trans('Are you sure?'),
                text: trans('You won\'t be able to revert this!'),
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                cancelButtonText: trans('Cancel'),
                confirmButtonText: trans('Confirm!')
            }).then((result) => {
                if (result.isConfirmed) {
                    let url = baseUrl + '/delete-exam-class/' + exam_id + '/' + class_id;

                    function successCallback(response) {
                        $('#editModal').modal('hide');
                        $('#table_list').bootstrapTable('refresh');
                        showSuccessToast(response.message);

                    }

                    function errorCallback(response) {
                        showErrorToast(response.message);
                    }

                    ajaxRequest('DELETE', url, null, null, successCallback, errorCallback);
                }
            })
        });
    }
};

window.assignmentEvents = {
    'click .edit-data': function (e, value, row, index) {
        //Reset to Old Values
        var html_file = '';
        $('#edit_id').val(row.id);
        $('#edit_name').val(row.name);
        $('#edit_instructions').val(row.instructions);

        var dueParts = row.due_date.match(/(\d{2})-(\d{2})-(\d{4}) (\d{2}):(\d{2}):(\d{2})/);
        var Fromdatetime = dueParts[3] + "-" + dueParts[2] + "-" + dueParts[1] + "T" + dueParts[4] + ":" + dueParts[5];
        setDateTimeValue($('#edit_due_date'), Fromdatetime);
        $('#edit_points').val(row.points);
        if (row.resubmission) {
            $('#edit_resubmission_allowed').prop('checked', true).trigger('change');
            $('#edit_extra_days_for_resubmission').val(row.extra_days_for_resubmission);
        } else {
            $('#edit_resubmission_allowed').prop('checked', false).trigger('change');
            $('#edit_extra_days_for_resubmission').val('');
        }

        if (row.file) {
            $.each(row.file, function (key, data) {
                html_file += '<div class="file"><a target="_blank" href="' + escapeHtml(safeUrl(data.file_url)) + '" class="m-1">' + escapeHtml(data.file_name) + '</a> <span class="fa fa-times text-danger remove-assignment-file" data-id="' + escapeHtml(data.id) + '"></span><br><br></div>'
            })

            $('#old_files').html(html_file);
        }

        // Both dropdowns are disabled (display-only) — populate directly from row data,
        // no need to re-fetch from the server.
        $('#edit_term_id').val(row.term_id || '');
        $('#edit_class_section_id').html(
            '<option value="' + row.class_section_id + '">' + row.class_section_name + '</option>'
        );
        $('#edit_subject_id').html(
            '<option value="' + row.subject_id + '">' + row.subject_name + '</option>'
        );
    }
};

window.announcementEvents = {
    'click .edit-announcement-form': function (e, value, row, index) {
        // Log the row for debugging
        // Set the ID in the hidden field
        $('#edit_id').val(row.id);

        // Set the title
        $('#title').val(row.title);

        // Set the description
        $('#description').val(row.description);

        // Set the assign type (e.g., Subject, Noticeboard, etc.)
        $('#edit_set_data').val(row.assign_to.class_section ? 'class_section' : 'noticeboard');
        // Update the form action URL to include the announcement ID
        var form = $('#edit-announcement-form');
        var baseUrl = form.attr('action');
        form.attr('action', baseUrl.replace(/\/\d+$/, '') + '/' + row.id);

        // If assigned to a Subject (class_section), show and set class section and subject dropdowns
        if (row.assign === 'Subject' && row.assign_to && row.assign_to.class_section_id) {
            $('.edit_show_class_section_id').show();
            $('#edit_class_section_id').val(row.assign_to.class_section_id).trigger('change', [row.assign_to.subject_id]);
            // Optionally set subject if you have a subject dropdown
            if ($('#edit_subject_id').length && row.assign_to.subject_id) {
                $('#edit_subject_id').val(row.assign_to.subject_id).trigger('change');
            }
        } else {
            $('.edit_show_class_section_id').hide();
        }

        // Display any attached files
        if (row.file && row.file.length > 0) {
            let fileHtml = '';
            row.file.forEach(function (file) {
                fileHtml += '<div class="mb-2">';
                fileHtml += '<a href="' + escapeHtml(safeUrl(file.file_url)) + '" target="_blank">' + escapeHtml(file.file_name) + '</a>';
                fileHtml += '</div>';
            });
            $('#old_files').html(fileHtml);
        } else {
            $('#old_files').html('No files attached');
        }
    }
};

window.parentEvents = {
    'click .editdata': function (e, value, row, index) {
        $('#edit_id').val(row.id);
        $('#first_name').val(row.first_name);
        $('#last_name').val(row.last_name);
        // Stored as "Male"/"Female" by most paths; the radios' values are lower case.
        $('input[name=gender].edit').prop('checked', false)
            .filter((_, el) => el.value === String(row.gender || '').toLowerCase()).prop('checked', true);
        $('#email').val(row.email);
        $('#mobile').val(row.mobile);
        $('#occupation').val(row.occupation);
        $('#dob').val(row.dob);
        $('#edit-image-tag').attr('src', row.image);
        if (row.current_address) {
            $('#current_address_div').show();
            $('#current_address').val(row.current_address);
        } else {
            $('#current_address_div').hide();
        }
        if (row.permanent_address) {
            $('#permanent_address_div').show();
            $('#permanent_address').val(row.permanent_address);
        } else {
            $('#permanent_address_div').hide();
        }
        setTimeout(function () {
            $('input[type=checkbox].edit_checkbox').removeAttr('checked');
            $('.edit_text_number').val('');
            $('.edit_dropdown').val('');
            $('.edit_textarea').val('');
            $('.edit_radio').removeAttr('checked');
            // $('.edit_file').css('display', 'none');

            $.each(row.dynamic_field, function (key, value) {

                var id = Object.keys(row.dynamic_field[key])[0];
                var id_with_hash = '#' + Object.keys(row.dynamic_field[key])[0];
                $('#file-' + id + '').val('');
                $('#' + id + '-div').css('display', 'none');

                // Confirm is checkbox or not
                let count = 0;
                Object.keys(value).forEach(function (key) {
                    let value_1 = value[key];
                    if (typeof value_1 === 'object') {
                        count++;
                    }
                });

                if (count == 0) {
                    let myDiv = document.getElementById(id);
                    let tagName = '';
                    // TEXT / NUMBER / TEXTAERA / SELECT / FILE
                    if (myDiv) {
                        tagName = myDiv.tagName;
                        if (tagName == 'A') { // IF INPUT TYPE FILE
                            if (value[id]) {
                                $('#' + id + '-div').css('display', 'block');
                                $('#' + id + '-hidden').val(value[id]);
                                $(id_with_hash).attr('href', 'storage/' + value[id]);
                                $('#file-' + id + '').val(value[id]);
                                $(id_with_hash + '-name').val(value[id]);
                            }
                        } else { // TEXT / NUMBER / TEXTAERA / SELECT
                            // let input_type = $(id_with_hash).attr('type');
                            $(id_with_hash).val('');
                            $(id_with_hash).val(value[id]);
                        }

                    } else {
                        // RADIO
                        let input_type = $('#' + value[id]).attr('type');
                        if (input_type == 'radio') {
                            $('#' + value[id]).attr('checked', true);
                        }
                    }

                } else { // CHECKBOX
                    let checkbox_value = [];

                    $.each(value, function (key_1, value_1) {
                        if (value_1 != null) {
                            checkbox_value.push(Object.keys(value_1));
                        }
                    })

                    $.each(checkbox_value, function (key_1, value_1) {
                        $.each(value_1, function (key_2, value_2) {
                            $('#checkbox_' + value_2).attr('checked', true);
                        })
                    })

                }

            });
        }, 1000);
    }
};

// Toggle the online-registration edit modal between editable and read-only ("view") mode.
// Scoped to the online-registration form only (action -> update-active-status), so the shared
// .editdata handler on other student tables is unaffected.
window.setOnlineRegistrationFormMode = function (mode, row) {
    var $modal = $('#editModal');
    var $form = $modal.find('form.edit-student-registration-form[action*="update-active-status"]');
    if (!$form.length) {
        return; // Not on the online-registration page
    }
    var isView = mode === 'view';

    // Lock/unlock every editable control. Leave the disabled-by-design file-upload-info labels alone.
    $form.find('input, select, textarea').not('.file-upload-info').prop('disabled', isView);
    $form.find('.file-upload-browse').prop('disabled', isView);
    // Keep the select2 dropdowns (father/mother/guardian email) in sync with their disabled state.
    $form.find('.edit-father-search, .edit-mother-search, .edit-guardian-search').trigger('change.select2');

    // In view mode, hide submit and reflect the decision the admin already took.
    $form.find(':submit').toggle(!isView);
    $modal.find('.modal-title').text((isView ? trans('view') : trans('edit')) + ' ' + trans('students'));

    if (isView) {
        $('#student_active').prop('checked', !!row && row.user_status == 1);
        $('#student_inactive').prop('checked', !!row && row.user_status == 2);
        $('#class_section_div').hide();
    }
};

window.studentEvents = {
    // Sign-in set-up (LearnerInvitationController).
    'click .learner-invite': function (e) {
        e.preventDefault();
        ajaxRequest('POST', $(e.currentTarget).data('url'), new FormData(), null, function (response) {
            showSuccessToast(response.message);
        }, function (response) {
            showErrorToast(response.message);
        });
    },
    'click .learner-sheet': function (e) {
        e.preventDefault();
        openLearnerSignInSheet($(e.currentTarget).data('url'));
    },
    'click .editdata': function (e, value, row, index) {
        $("#edit_id").val(row.user_id);
        $("#edit_first_name").val(row.first_name);
        $("#edit_last_name").val(row.last_name);
        $("#edit_mobile").val(row.mobile);
        $("#edit_dob").val(row.dob);
        $("#edit_class_id").val(row.class_id).trigger("change");
        $("#edit_class_section_id").val(row.class_section_id);
        $("#edit_category_id").val(row.category_id);
        $("#edit_admission_no").val(row.admission_no);
        $("#edit_roll_number").val(row.roll_number);
        $("#edit_caste").val(row.caste);
        $("#edit_religion").val(row.religion);
        $("#edit_admission_date").val(row.admission_date);
        $("#edit_blood_group").val(row.blood_group);
        $("#edit_height").val(row.height);
        $("#edit_weight").val(row.weight);
        $("#edit_current_address").val(row.current_address);
        $("#edit_permanent_address").val(row.permanent_address);
        $("#edit-student-image-tag").attr("src", row.image_link);

        $("#student_active").prop("checked", false);
        $("#student_inactive").prop("checked", false);
        $("#class_section_div").hide();

        if (row.gender == "male") {
            $("#edit_female").removeAttr("checked");
            $("#edit_male").attr("checked", "true");
        } else {
            $("#edit_male").removeAttr("checked");
            $("#edit_female").attr("checked", "true");
        }
        // STUDENT DYNAMIC FIELDS
        setTimeout(function () {
            $("input[type=checkbox].edit_checkbox").removeAttr("checked");
            $(".edit_text_number").val("");
            $(".edit_dropdown").val("");
            $(".edit_textarea").val("");
            $(".edit_radio").removeAttr("checked");
            // $('.edit_file').css('display', 'none');

            $.each(row.dynamic_data_field, function (key, value) {
                var id = Object.keys(row.dynamic_data_field[key])[0];
                var id_with_hash =
                    "#" + Object.keys(row.dynamic_data_field[key])[0];
                $("#file-" + id + "").val("");
                $("#" + id + "-div").css("display", "none");

                // Confirm is checkbox or not
                let count = 0;
                Object.keys(value).forEach(function (key) {
                    let value_1 = value[key];
                    if (typeof value_1 === "object") {
                        count++;
                    }
                });

                if (count == 0) {
                    let myDiv = document.getElementById(id);
                    let tagName = "";
                    // TEXT / NUMBER / TEXTAERA / SELECT / FILE
                    if (myDiv) {
                        tagName = myDiv.tagName;
                        if (tagName == "A") {
                            // IF INPUT TYPE FILE
                            if (value[id]) {
                                $("#" + id + "-div").css("display", "block");
                                $("#" + id + "-hidden").val(value[id]);
                                $(id_with_hash).attr(
                                    "href",
                                    "storage/" + value[id],
                                );
                                $("#file-" + id + "").val(value[id]);
                                $(id_with_hash + "-name").val(value[id]);
                            }
                        } else {
                            // TEXT / NUMBER / TEXTAERA / SELECT
                            // let input_type = $(id_with_hash).attr('type');
                            $(id_with_hash).val("");
                            $(id_with_hash).val(value[id]);
                        }
                    } else {
                        // RADIO
                        let input_type = $("#" + value[id]).attr("type");
                        if (input_type == "radio") {
                            $("#" + value[id]).attr("checked", true);
                        }
                    }
                } else {
                    // CHECKBOX
                    let checkbox_value = [];

                    $.each(value, function (key_1, value_1) {
                        if (value_1 != null) {
                            checkbox_value.push(Object.keys(value_1));
                        }
                    });

                    $.each(checkbox_value, function (key_1, value_1) {
                        $.each(value_1, function (key_2, value_2) {
                            $("#checkbox_" + value_2).attr("checked", true);
                        });
                    });
                }
            });
        }, 1000);
        // END DYNAMIC FIELDS
        if (row.gender == "male") {
            $("#edit_female").removeAttr("checked");
            $("#edit_male").attr("checked", "true");
        } else {
            $("#edit_male").removeAttr("checked");
            $("#edit_female").attr("checked", "true");
        }

        if (row.father_id && row.mother_id) {
            $("#show-edit-parents-details").prop("checked", true);
            // $('input[name="edit_parent_guardian_type"]').val('parent').trigger('change');
            $('input[name="edit_parent_guardian_type"][value="parent"]')
                .prop("checked", true)
                .trigger("change");
            console.log("parent");
        } else {
            $("#show-edit-guardian-details").prop("checked", true);
            // $('input[name="edit_parent_guardian_type"]').val('guardian').trigger('change');
            $('input[name="edit_parent_guardian_type"][value="guardian"]')
                .prop("checked", true)
                .trigger("change");
            console.log("guardian");
        }

        //Father Data
        $("#edit_father_email").select2("trigger", "select", {
            data: {
                id: row.father_id ? row.father_id : "",
                text: row.father_email ? row.father_email : "",
            },
        });
        //Adding delay to fill data so that select2 code and this code don't conflict each other
        setTimeout(function () {
            $("#edit_father_first_name")
                .val(row.father_first_name)
                .attr("readonly", true);
            $("#edit_father_last_name")
                .val(row.father_last_name)
                .attr("readonly", true);
            $("#edit_father_mobile")
                .val(row.father_mobile)
                .attr("readonly", true);
            $("#edit_father_dob").val(row.father_dob).attr("readonly", true);
            $("#edit_father_occupation")
                .val(row.father_occupation)
                .attr("readonly", true);
            $("#edit-father-image-tag").attr("src", row.father_image_link);
            $(".edit-father-search").rules("remove", "email");
            $(".father_image").rules("remove", "required");
        }, 500);

        //Mother Data
        $("#edit_mother_email").select2("trigger", "select", {
            data: {
                id: row.mother_id ? row.mother_id : "",
                text: row.mother_email ? row.mother_email : "",
            },
        });
        //Adding delay to fill data so that select2 code and this code don't conflict each other

        setTimeout(function () {
            $("#edit_mother_first_name")
                .val(row.mother_first_name)
                .attr("readonly", true);
            $("#edit_mother_last_name")
                .val(row.mother_last_name)
                .attr("readonly", true);
            $("#edit_mother_mobile")
                .val(row.mother_mobile)
                .attr("readonly", true);
            $("#edit_mother_dob").val(row.mother_dob).attr("readonly", true);
            $("#edit_mother_occupation")
                .val(row.mother_occupation)
                .attr("readonly", true);
            $("#edit-mother-image-tag").attr("src", row.mother_image_link);
            $(".edit-mother-search").rules("remove", "email");
            $(".mother_image").rules("remove", "required");
        }, 500);

        // if (row.guardian_id) {
        //     $('input[name="edit_parent_guardian_type"]').attr('checked', true).trigger('change');
        // } else {
        //     $('input[name="edit_parent_guardian_type"]').attr('checked', false).trigger('change');
        // }

        // Guardian Data
        $("#edit_guardian_email").select2("trigger", "select", {
            data: {
                id: row.guardian_id ? row.guardian_id : "",
                text: row.guardian_email ? row.guardian_email : "",
                edit_data: true,
            },
        });

        //Adding delay to fill data so that select2 code and this code don't conflict each other
        setTimeout(function () {
            $("#edit_guardian_first_name")
                .val(row.guardian_first_name)
                .attr("readonly", true);
            $("#edit_guardian_last_name")
                .val(row.guardian_last_name)
                .attr("readonly", true);
            $("#edit_guardian_mobile")
                .val(row.guardian_mobile)
                .attr("readonly", true);
            if (row.guardian_gender == "Male") {
                $("#edit_guardian_female").removeAttr("checked");
                $("#edit_guardian_male").attr("checked", "true");
            } else {
                $("#edit_guardian_male").removeAttr("checked");
                $("#edit_guardian_female").attr("checked", "true");
            }
            $("#edit_guardian_dob")
                .val(row.guardian_dob)
                .attr("readonly", true);
            $("#edit_guardian_occupation")
                .val(row.guardian_occupation)
                .attr("readonly", true);
            $("#edit-guardian-image-tag").attr("src", row.guardian_image_link);
            $(".edit-guardian-search").rules("remove", "email");
            $(".guardian_image").rules("remove", "required");
        }, 500);

        // Ensure the online-registration modal is editable (resets any prior view-only state).
        window.setOnlineRegistrationFormMode("edit", row);
    },

    // Open the online-registration modal in read-only mode for already-processed (accepted/rejected) students.
    'click .viewdata': function (e, value, row, index) {
        // Reuse the edit population logic, then lock the form for viewing only.
        window.studentEvents['click .editdata'].call(this, e, value, row, index);
        window.setOnlineRegistrationFormMode('view', row);
    },

    'click .deletepermanentdata': function (e, value, row, index) {
        e.preventDefault();
        Swal.fire({
            title: trans('Are you sure?'),
            text: trans('You want to delete it permanently ?'),
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            cancelButtonText: trans('Cancel'),
            confirmButtonText: trans('Yes, delete it!')
        }).then((result) => {
            if (result.isConfirmed) {
                let url = baseUrl + '/permanent-delete/' + row.id;

                function successCallback(response) {
                    $('#editModal').modal('hide');
                    $('#table_list').bootstrapTable('refresh');
                    showSuccessToast(response.message);
                }

                function errorCallback(response) {
                    showErrorToast(response.message);
                }

                ajaxRequest('DELETE', url, null, null, successCallback, errorCallback);
            }
        })


    }
};

window.assignmentSubmissionEvents = {
    'click .edit-data': function (e, value, row, index) {
        $('#edit_id').val(row.id);
        $('#assignment_name').text(row.assignment_name);
        $('#subject').text(row.subject);
        $('#student_name').text(row.student_name);
        $('#submitted_date').text(row.created_at);

        var $files = $('#files').empty();
        $.each(row.file, function (key, data) {
            $('<a>', {
                href: safeUrl(data.file_url),
                target: '_blank',
                class: 'submission-file-link',
                title: data.file_name
            }).append(
                $(document.createElementNS('http://www.w3.org/2000/svg', 'svg'))
                    .attr({
                        class: 'lc-icon',
                        viewBox: '0 0 24 24',
                        width: 16,
                        height: 16,
                        fill: 'none',
                        stroke: 'currentColor',
                        'stroke-width': 1.9,
                        'stroke-linecap': 'round',
                        'stroke-linejoin': 'round'
                    })
                    .append(
                        $(document.createElementNS('http://www.w3.org/2000/svg', 'path'))
                            .attr('d', 'm16 6-8.414 8.586a2 2 0 0 0 2.829 2.829l8.414-8.586a4 4 0 1 0-5.657-5.657l-8.379 8.551a6 6 0 1 0 8.485 8.485l8.379-8.551')
                    ),
                $('<span>').text(data.file_name)
            ).appendTo($files);
        });
        if (row.assignment_points) {
            $('#points_div').show();
            $('#assignment_points').text('/ ' + row.assignment_points);
            $('#points').prop('max', row.assignment_points);
            $('#points').val(row.points);
            window.assignmentHasPoints = true;
        } else {
            $('#points_div').hide();
            $('#assignment_points').text('');
            window.assignmentHasPoints = false;
        }
        $('#text').text(row.text || '');
        $('#feedback').val(row.feedback);
        
        if (typeof selectStatus === 'function') {
            selectStatus(row.status);
        } else {
            if (row.status === 1) {
                $('#status_accept').prop('checked', true);
            } else if (row.status === 2) {
                $('#status_reject').prop('checked', true);
            }
        }

        if (typeof setStudentAvatar === 'function') {
            setStudentAvatar(row.student_name, row.student_image);
        }
    }
};

window.examMarksEvents = {
    'click .edit-data': function (e, value, row, index) {
        $('.student_name').text(row.student_name);
        $('.subject_container').html('');
        var no = 0;
        $.each(row.data, function (key, data) {
            var html_data = '<div class="row"><input type="hidden" id="marks_id form-control" readonly name="edit[' + no + '][marks_id]" value="' + escapeHtml(data.id) + '"/><div class="row mx-2"><input type="hidden" id="marks_id form-control" readonly name="edit[' + no + '][exam_id]" value="' + escapeHtml(data.timetable.exam_id) + '"/><div class="row mx-2"><input type="hidden" id="marks_id form-control" readonly name="edit[' + no + '][student_id]" value="' + escapeHtml(row.student_id) + '"/><div class="row mx-2"><input type="hidden" id="marks_id form-control" readonly name="edit[' + no + '][passing_marks]" value="' + escapeHtml(data.timetable.passing_marks) + '"/><div class="form-group col-sm-12 col-md-4"><input type="text" class="subject_name form-control" readonly name="edit[' + no + '][subject_name]" value="' + escapeHtml(data.subject.name) + '" /></div><div class="form-group col-sm-12 col-md-4"><input type="text" class="total_marks form-control" readonly name="edit[' + no + '][total_marks]" value="' + escapeHtml(data.timetable.total_marks) + '" /></div><div class="form-group col-sm-12 col-md-4"><input type="text" class="obtained_marks form-control" name="edit[' + no + '][obtained_marks]" value="' + escapeHtml(data.obtained_marks) + '" /></div></div>';
            $('.subject_container').append(html_data);
            no++;
        });
    }
};

window.examTimetableEvents = {
    'click .edit-data': function (e, value, row, index) {

        $('.edit_timetable_exam_id').val(row.exam_id);
        $('.edit_timetable_class_id').val(row.class_id);
        $('.edit_timetable_session_year_id').val(row.session_year_id);

        $('.edit-timetable-container').html('');
        let select_subject_html = "";
        if (row.subjects && row.subjects.length > 0) {
            $.each(row.subjects, function (key, data) {
                select_subject_html += "<option value='" + data.id + "'>" + data.name + ' - ' + data.type + "</option>";
            });
        } else {
            select_subject_html = "<option value=''>No Data Found</option>";
        }
        $('.edit_exam_subjects_options').html(select_subject_html);
        if (row.timetable.length != 0) {
            $.each(row.timetable, function (key, value) {
                let html = '';
                if (!$('.edit-timetable-container:last').is(':empty')) {
                    html = $('.edit-timetable-container').find('.edit_exam_timetable:last').clone();
                } else {
                    html = $('.edit_exam_timetable_tamplate').clone();
                }
                html.addClass('edit_exam_timetable').removeClass('edit_exam_timetable_tamplate');
                html.css('display', 'block');
                html.find('.error').remove();
                html.find('.has-danger').removeClass('has-danger');
                // This function will replace the last index value and increment in the multidimensional name attribute
                html.find('.form-control').each(function (key, element) {
                    this.name = this.name.replace(/\[(\d+)\]/, function (str, p1) {
                        return '[' + (parseInt(p1, 10) + 1) + ']';
                    });
                })

                html.find('.remove-edit-exam-timetable-content').attr("data-timetable_id", value.id);

                html.find('.edit_timetable_id').val(value.id);

                html.find('.edit_timetable_exam_id').val(value.exam_id);

                html.find('.edit_timetable_class_id').val(value.class_id);

                html.find('.edit_exam_subjects_options').val(value.subject_id)

                html.find('.edit_total_marks').val(value.total_marks);

                html.find('.edit_passing_marks').val(value.passing_marks);

                html.find('.edit_start_time').val(value.start_time);

                html.find('.edit_end_time').val(value.end_time);

                html.find('.edit_venue').val(value.venue || '');

                var dateParts = value.date.match(/(\d{2})-(\d{2})-(\d{4})(?: (\d{2}):(\d{2}):(\d{2}))?/);
                var date = dateParts ? new Date(dateParts[3], dateParts[2] - 1, dateParts[1]) : new Date(value.date),
                    yr = date.getFullYear(),
                    month = date.getMonth() < 9 ? '0' + (date.getMonth() + 1) : date.getMonth() + 1,
                    day = date.getDate() < 9 ? '0' + date.getDate() : date.getDate(),
                    newDate = day + '-' + month + '-' + yr;

                html.find('.edit_date').val(newDate);
                $('.edit-timetable-container').append(html);
            });
            $(document).on('click', '.remove-edit-exam-timetable-content', function (e) {
                e.preventDefault();

                let $this = $(this);
                // If button has Data ID then Call ajax function to delete file
                let timetable_id = $(this).data('timetable_id');

                if (timetable_id) {
                    Swal.fire({
                        title: trans('Are you sure?'),
                        text: trans('You won\'t be able to revert this!'),
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        cancelButtonText: trans('Cancel'),
                        confirmButtonText: trans('Yes, delete it!')
                    }).then((result) => {
                        if (result.isConfirmed) {
                            let url = baseUrl + '/exams/delete-timetable/' + timetable_id;

                            function successCallback(response) {
                                $this.parent().parent().parent().remove();
                                $('#editModal').modal('hide');
                                $('#table_list').bootstrapTable('refresh');
                                showSuccessToast(response.message);
                            }

                            function errorCallback(response) {
                                showErrorToast(response.message);
                            }

                            ajaxRequest('DELETE', url, null, null, successCallback, errorCallback);
                        }
                    })
                } else {
                    $(this).parent().parent().parent().remove();
                }

            });
        }
    }
}

window.onlineExamEvents = {
    'click .edit-data': function (e, value, row, index) {
        console.log(row);
        $('#edit_id').val(row.online_exam_id);
        $('#edit-online-exam-term').val(row.term_name || '');
        $('#edit-online-exam-term-id').val(row.term_id || '');
        $('#edit-online-exam-title').val(row.title);
        $('#edit-online-exam-key').val(row.exam_key);
        $('#edit-online-exam-duration').val(row.duration);

        // Convert "DD-MM-YYYY HH:MM:SS" to "YYYY-MM-DDTHH:MM" and push it into the Flatpickr date-time field
        // Split the date string by space to separate date and time
        if (row.start_date) {
            let startParts = row.start_date.split(' '); // ["10-10-2024", "14:30:00"]
            let startDateParts = startParts[0].split('-'); // ["10", "10", "2024"]
            let startTime = startParts[1].substring(0, 5); // "14:30" (remove seconds)
            // Rearrange to YYYY-MM-DDTHH:MM format
            setDateTimeValue($('#edit-online-exam-start-date'), startDateParts[2] + '-' + startDateParts[1] + '-' + startDateParts[0] + 'T' + startTime);
        }

        if (row.end_date) {
            let endParts = row.end_date.split(' '); // ["10-10-2024", "14:30:00"]
            let endDateParts = endParts[0].split('-'); // ["10", "10", "2024"]
            let endTime = endParts[1].substring(0, 5); // "14:30" (remove seconds)
            // Rearrange to YYYY-MM-DDTHH:MM format
            setDateTimeValue($('#edit-online-exam-end-date'), endDateParts[2] + '-' + endDateParts[1] + '-' + endDateParts[0] + 'T' + endTime);
        }
    },
};

window.onlineExamQuestionsEvents = {
    'click .edit-data': function (e, value, row, index) {
        $('#edit_id').val(row.online_exam_question_id);
        $('.edit_question_type').val(row.question_type);
        $('#edit-online-exam-class-id').val(row.class_id).trigger('change');

        //added the subject on class id after 0.5 seconds
        setTimeout(() => {
            $('#edit-online-exam-subject-id').val(row.subject_id).trigger('change');
        }, 1000);

        if (row.question_type) {
            $('.edit_question').html('')
            $('.edit_option_container').html('')
            // set data in question text area
            setEquationEditorValue('edit_equestion', row.question_row)

            $('#edit-simple-question').hide(100)
            $('#edit-equation-question').show(300);
            $('.edit_eoption_container').html('')

            let html_option = '';
            $.each(row.options, function (index, value) {
                if (index >= 2) {
                    html_option += '<div class="form-group col-md-6"><input type="hidden" class="edit_eoption_id" name="edit_eoption[' + (index + 1) + '][id]" value=' + value.id + '><label>' + lang_option + ' <span class="edit-eoption-number">' + (index + 1) + '</span> <span class="text-danger">*</span></label><textarea class="editor_options" name="edit_eoption[' + (index + 1) + '][option]" placeholder="' + lang_enter_option + '">' + escapeHtml(value.option_row) + '</textarea><div class="remove-edit-option-content"><button type="button" class="btn btn-inverse-danger remove-edit-option btn-sm mt-1" data-id="' + value.id + '"><i class="fa fa-times"></i></button></div></div>'
                    $('.edit_eoption_container').html(html_option);
                } else {
                    html_option += '<div class="form-group col-md-6"><input type="hidden" class="edit_eoption_id" name="edit_eoption[' + (index + 1) + '][id]" value=' + value.id + '><label>' + lang_option + ' <span class="edit-eoption-number">' + (index + 1) + '</span> <span class="text-danger">*</span></label><textarea class="editor_options" name="edit_eoption[' + (index + 1) + '][option]" placeholder="' + lang_enter_option + '">' + escapeHtml(value.option_row) + '</textarea></div>'
                    $('.edit_eoption_container').html(html_option);
                }
            });
            createEquationEditors();
        } else {
            $('#edit-equation-question').hide(100);
            $('#edit-simple-question').show(300);
            $('.edit_option_container').html('')

            $('.edit-question').text(row.question);
            // add options and add the options in answers
            let html = ''
            $.each(row.options, function (index, value) {
                if (index >= 2) {
                    html = '<div class="form-group col-md-6"><input type="hidden" class="edit_option_id" name="edit_options[' + (index + 1) + '][id]" value=' + value.id + '><label>' + lang_option + ' <span class="edit-option-number"> ' + (index + 1) + '</span> <span class="text-danger">*</span></label><input type="text" name="edit_options[' + (index + 1) + '][option]" value="' + escapeHtml(value.option) + '" placeholder="' + lang_enter_option + '" class="form-control add-edit-question-option" /><div class="remove-edit-option-content"><button type="button" class="btn btn-inverse-danger remove-edit-option btn-sm mt-1" data-id="' + value.id + '"><i class="fa fa-times"></i></button></div></div>';
                } else {
                    html = '<div class="form-group col-md-6"><input type="hidden" class="edit_option_id" name="edit_options[' + (index + 1) + '][id]" value=' + value.id + '><label>' + lang_option + ' <span class="edit-option-number"> ' + (index + 1) + '</span> <span class="text-danger">*</span></label><input type="text" name="edit_options[' + (index + 1) + '][option]" value="' + escapeHtml(value.option) + '" placeholder="' + lang_enter_option + '" class="form-control add-edit-question-option" /><div class="remove-edit-option-content"></div></div>';
                }
                $('.edit_option_container').append(html);
            });
        }
        $('.answers_db').html('');
        $('.edit_answer_select').html('');
        if (row.answers.length) {
            $.each(row.options, function (index, value) {
                $.each(row.answers, function (answer_index, answer_value) {
                    if (value.id == answer_value.option_id) {
                        if (row.answers.length == 1) {
                            let html = '<i class="fa fa-circle" aria-hidden="true"></i> ' + lang_option + ' ' + (index + 1) + '<br>';
                            $('.answers_db').append(html);
                            return false;
                        } else {
                            let html = '<i class="fa fa-circle" aria-hidden="true"></i> ' + lang_option + ' ' + (index + 1) + ' <span class="fa fa-times text-danger remove-answers" data-id=' + answer_value.id + ' style="cursor:pointer"></span><br>';
                            $('.answers_db').append(html);
                            return false;
                        }
                    }
                });
            });
        }

        if (row.options_not_answers) {
            $.each(row.options, function (index, value) {
                $.each(row.options_not_answers, function (answer_index, option_data) {
                    if (value.id == option_data.id) {
                        $('.edit_answer_select').append('<option value="' + (option_data.id) + '">' + lang_option + ' ' + (index + 1) + '</option>');
                        return false;
                    }
                });
            });
        }

        $('.edit_answer_select').ready(function () {
            if ($('.answers_db').html() == '') {
                $('.edit_answer_select').attr('required', true);
            } else {
                $('.edit_answer_select').removeAttr('required');
            }
        })
        $('#image_preview').attr('src', row.image);
        $('.edit_note').val(row.note);
    },
};

window.teacherActionEvents = {
    // Deactivate or reactivate: the server signs a deactivated teacher out.
    'click .teacher-status': function (e) {
        e.preventDefault();
        var link = $(e.currentTarget);
        Swal.fire({
            text: link.data('confirm-text'),
            icon: 'warning',
            showCancelButton: true,
            cancelButtonText: trans('Cancel'),
            confirmButtonText: trans('Confirm!')
        }).then(function (result) {
            if (!result.isConfirmed) {
                return;
            }
            var data = new FormData();
            data.append('status', link.data('status'));
            ajaxRequest('POST', link.data('url'), data, null, function (response) {
                showSuccessToast(response.message);
                $('#table_list').bootstrapTable('refresh');
            }, function (response) {
                showErrorToast(response.message);
            });
        });
    },
    'click .editdata': function (e, value, row, index) {
        $('input[type=checkbox].edit_checkbox').removeAttr('checked');
        $('.edit_text_number').val('');
        $('.edit_dropdown').val('');
        $('.edit_textarea').val('');
        $('.edit_radio').removeAttr('checked');
        $('.edit_file').css('display', 'none');

        $('#id').val(row.id);
        $('#user_id').val(row.user_id);
        $('#first_name').val(row.first_name);
        $('#last_name').val(row.last_name);
        // Stored as "Male"/"Female" by most paths; the radios' values are lower case.
        $('input[name=gender].edit').prop('checked', false)
            .filter((_, el) => el.value === String(row.gender || '').toLowerCase()).prop('checked', true);
        $('#current_address').val(row.current_address);
        $('#permanent_address').val(row.permanent_address);
        $('#email').val(row.email);
        $('#mobile').val(row.mobile);
        $('#dob').val(row.dob);
        $('#qualification').val(row.qualification);

        // DYNAMIC FIELDS
        setTimeout(function () {
            $.each(row.dynamic_data_field, function (key, value) {
                $('#' + id + '-div').css('display', 'none');

                var id = Object.keys(row.dynamic_data_field[key])[0];
                var id_with_hash = '#' + Object.keys(row.dynamic_data_field[key])[0];
                $('#' + id).val('');


                // Confirm is checkbox or not
                let count = 0;
                Object.keys(value).forEach(function (key) {
                    let value_1 = value[key];
                    if (typeof value_1 === 'object') {
                        count++;
                    }
                });

                if (count == 0) {
                    let myDiv = document.getElementById(id);
                    let tagName = '';
                    // TEXT / NUMBER / TEXTAERA / SELECT / FILE
                    if (myDiv) {
                        tagName = myDiv.tagName;
                        if (tagName == 'A') { // IF INPUT TYPE FILE
                            if (value[id]) {
                                $('#' + id + '-div').css('display', 'block');
                                $(id_with_hash).attr('href', 'storage/' + value[id]);
                                $('#' + id + '-hidden').val(value[id]);
                                $('#file-' + id + '').val(value[id]);
                                $(id_with_hash + '-name').val(value[id]);
                            }
                        } else { // TEXT / NUMBER / TEXTAERA / SELECT
                            // let input_type = $(id_with_hash).attr('type');
                            $(id_with_hash).val('');
                            $(id_with_hash).val(value[id]);
                        }

                    } else {
                        // RADIO
                        let input_type = $('#' + value[id]).attr('type');
                        if (input_type == 'radio') {
                            $('#' + value[id]).attr('checked', true);
                        }
                    }

                } else { // CHECKBOX
                    let checkbox_value = [];
                    $.each(value, function (key_1, value_1) {
                        if (value_1 != null) {
                            checkbox_value.push(Object.keys(value_1));
                        }
                    })

                    $.each(checkbox_value, function (key_1, value_1) {
                        $.each(value_1, function (key_2, value_2) {
                            $('#checkbox_' + value_2).attr('checked', true);
                        })
                    })

                }

            });
        }, 1000);
        // END DYNAMIC FIELDS
        if (row.has_student_permissions) {
            $('.edit_permission_chk').prop("checked", true);
            $('.edit_permission_chk').addClass('warning_ckh');
            $(document).on('change', '.warning_ckh', function () {
                if (!this.checked) {
                    Swal.fire({
                        title: lang_delete_title,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: lang_yes_uncheck
                    }).then((result) => {
                        if (result.isConfirmed) {
                            $(this).prop("checked", false);
                        } else {
                            $(this).prop("checked", true);
                        }
                    })
                }
            });
        } else {
            $('.edit_permission_chk').prop("checked", false);
            $('.edit_permission_chk').removeClass('warning_ckh');
        }
    }
}

window.sliderEvents = {
    'click .edit-data': function (e, value, row, index) {
        $('#edit_id').val(row.id);
        $('#edit_type').val(row.type);
        // Select the file input field
        var fileInput = $('.edit_image');

        // Clear the selected file by resetting the input value
        fileInput.val(null);

        // Update the text input field to display "No file selected"
        fileInput.siblings('.form-control').val('');

        $('#edit_slider_image').attr('src', row.image);
    }
}

window.classEvents = {
    'click .edit-data': function (_e, _value, row) {

        $('#edit_id').val(row.id);
        $('#edit_name').val(row.name);
        $('#edit_stream_id').val(row.stream_id);
        $('#edit_shift_id').val(row.shift_id);
        $('#edit_medium_id').val(row.medium_id);
        $('#edit_educational_program_id').val(row.educational_program_id);
        $('#edit_section_id').val(row.section_id).trigger('change');

        if (row.include_terms == 1) {
            $('#edit_include_terms').prop('checked', true).change();
        } else {
            $('#edit_include_terms').prop('checked', false).change();
        }
    }
};

// Session Year
window.sessionYearEvents = {
    'click .editdata': function (_e, _value, row, _index) {
        $('#editModal').find('input[type="text"], input[type="hidden"], input[type="date"]').val('');
        $('#id').val(row.id);
        $('#name').val(row.name);
        setDatePickerValue($('#start_date'), row.start_date);
        setDatePickerValue($('#end_date'), row.end_date);
    }
};

window.formFieldsEvents = {
    'click .edit-data': function (_e, _value, row, _index) {

        $('#edit-id').val(row.id);
        $('#edit-name').val(row.name);
        $('#edit-type').val(row.type).trigger('change');
        $('#edit-type').prop('disabled', true);
        $('#edit-type-value').val(row.type);

        $('#edit-for').val(row.for).trigger('change');
        $('#edit-for-value').val(row.for);
        if (row.is_required) {
            $('#edit-required').prop('checked', true).change();
        } else {
            $('#edit-required').prop('checked', false).change();
        }
        $('.edit-remove-default-values').each(function () {
            $(this).trigger('click');
        })
        if (row.default_values) {
            for (let i = 2; i < row.default_values.length; i++) {
                $('.edit-add-more-default-values').trigger('click');
            }

            $('.edit_default_values').each(function (index, _value) {
                $(this).val(row.default_values[index]);
            })
        }
    }
};

window.leavesSettingEvents = {
    'click .edit-data': function (e, value, row, index) {
        $('#edit_id').val(row.id);
        $('#total_leave').val(row.total_leave);
        $('#session_year').val(row.session_year_id);

        let holidays = row.holiday_days;
        var holiday = holidays.split(",");

        $('#holiday_days').val(holiday).trigger('change');

    },

};

/* ============================================================================
 * Bootstrap-table dropdown bridge for the Bootstrap 4 admin theme.
 *
 * bootstrap-table v1.23 is the Bootstrap 5 build: it renders the pagination
 * "rows per page" selector — and the toolbar column-toggle / export menus —
 * with data-bs-toggle="dropdown". This admin theme ships Bootstrap 4 JS, which
 * only wires up data-toggle="dropdown", so those menus never open on click.
 *
 * Toggle them manually via event delegation, so it keeps working across every
 * (re)render of the table without depending on render timing. Action-cell menus
 * use data-toggle (native Bootstrap 4) and are intentionally left untouched.
 * ========================================================================== */
(function ($) {
    'use strict';

    function closeMenus($keepOpen) {
        $('.bootstrap-table .dropdown.show, .bootstrap-table .dropup.show, .bootstrap-table .btn-group.show').each(function () {
            if ($keepOpen && this === $keepOpen[0]) {
                return;
            }
            $(this).removeClass('show').children('.dropdown-menu').removeClass('show');
            $(this).children('[data-bs-toggle="dropdown"]').attr('aria-expanded', 'false');
        });
    }

    // Open/close a bootstrap-table dropdown (page size, column toggle, export).
    $(document).on('click', '.bootstrap-table [data-bs-toggle="dropdown"]', function (e) {
        e.preventDefault();
        e.stopPropagation();

        var $toggle = $(this);
        var $wrap = $toggle.parent(); // .dropdown / .dropup / .btn-group wrapper
        var willOpen = !$wrap.hasClass('show');

        closeMenus($wrap);
        $wrap.toggleClass('show', willOpen);
        $wrap.children('.dropdown-menu').toggleClass('show', willOpen);
        $toggle.attr('aria-expanded', willOpen ? 'true' : 'false');
    });

    // Close when clicking anywhere outside an open bootstrap-table dropdown.
    $(document).on('click', function (e) {
        if (!$(e.target).closest('.bootstrap-table .dropdown, .bootstrap-table .dropup, .bootstrap-table .btn-group').length) {
            closeMenus();
        }
    });
})(jQuery);

window.leavesEvents = {
    'click .edit-data': function (e, value, row) {

        let html_file = '';
        $('#id').val(row.id);
        $('#edit_from_date').val(row.from_date);
        $('#edit_to_date').val(row.to_date);
        $('#edit_reason').val(row.reason);
        $('#reject_reason').val(row.reason_of_rejection);

        if (row.file) {
            $.each(row.file, function (key, data) {
                html_file += '<div class="file"><a target="_blank" href="' + escapeHtml(safeUrl(data.file_url)) + '" class="m-1">' + escapeHtml(data.file_name) + '</a></span><br><br></div>'
            })

            $('#attachment').html(html_file);
        }

        setTimeout(() => {
            $('#edit_to_date').trigger('change');
        }, 500);
        setTimeout(() => {
            $.each(row.leave_detail, function (index, value) {
                $('input[name="type[' + moment(value.date, 'YYYY-MM-DD').format('DD-MM-YYYY') + '][]"][value="' + value.type + '"].form-check-input').prop('checked', true);
            });
        }, 500);


        // Set radio value
        $('input[name=status][value=' + row.status + '].leave-status').prop('checked', true);

        // Force change event so your show/hide logic runs
        $('input[name="status"]:checked').trigger('change');
    }
};

function formatDate(dateString) {
    const dateParts = dateString && dateString.match(/(\d{2})-(\d{2})-(\d{4})(?: (\d{2}):(\d{2}):(\d{2}))?/);
    const d = dateParts ? new Date(dateParts[3], dateParts[2] - 1, dateParts[1]) : new Date(dateString);
    if (isNaN(d)) return ''; // safety
    const day = String(d.getDate()).padStart(2, '0');
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const year = d.getFullYear();
    return `${day}-${month}-${year}`;
}

window.termEvents = {
    'click .edit-data': function (e, value, row, index) {
        $('#edit_id').val(row.id);
        $('#edit_name').val(row.name);

        setDatePickerValue($('#edit_start_date'), formatDate(row.start_date));
        setDatePickerValue($('#edit_end_date'), formatDate(row.end_date));
    }
};

function studentNameFormatter(value, row, index, field) {
    // Use the raw row field: on tables with data-escape="true" bootstrap-table passes an
    // already-escaped `value`, and the name is escaped exactly once below.
    const name =
        field && row && Object.prototype.hasOwnProperty.call(row, field)
            ? row[field]
            : value;
    const safeName = escapeHtml(name);

    // Default placeholder image path ("'" is also encoded: the URL ends up inside a
    // single-quoted JS string in the onerror handler below)
    const placeholderImage =
        `https://ui-avatars.com/api/?name=${encodeURIComponent(name).replace(/'/g, "%27")}&background=10B981&color=fff`

    // Get image path from row data
    const imagePath = row.photo ? row.photo : placeholderImage;
    // const imagePath = row.photo ? "{{ asset('') }}" + row.photo : placeholderImage;

    // Return HTML with image and name
    return `
        <div class="d-flex align-items-center">
            <img src="${escapeHtml(imagePath)}"
                 alt="${safeName}"
                 class="rounded-circle me-2"
                 style="width: 35px; height: 35px; object-fit: cover; margin-right: 6px;"
                 onerror="this.onerror=null; this.src='${escapeHtml(placeholderImage)}';">
            <span>${safeName}</span>
        </div>
    `;
}

$(document).ready(function () {
    // Dynamic bounds update for Session Year Edit modal
    // When start_date or end_date are modified in the edit modal, cascade new bounds to all date pickers
    $('#start_date, #end_date').on('change', function () {
        // Re-read selected dates from the Flatpickr instances
        var sDateObj = getDatePickerDate($('#start_date'));
        var eDateObj = getDatePickerDate($('#end_date'));

        // Update stored raw data attributes so "add new installment" handler gets updated bounds
        if (sDateObj) {
            var sy = sDateObj.getFullYear(), sm = String(sDateObj.getMonth() + 1).padStart(2, '0'), sd = String(sDateObj.getDate()).padStart(2, '0');
            $('#id').attr('data-start-date-raw', sy + '-' + sm + '-' + sd);
        }
        if (eDateObj) {
            var ey = eDateObj.getFullYear(), em = String(eDateObj.getMonth() + 1).padStart(2, '0'), ed = String(eDateObj.getDate()).padStart(2, '0');
            $('#id').attr('data-end-date-raw', ey + '-' + em + '-' + ed);
        }

    });
});
