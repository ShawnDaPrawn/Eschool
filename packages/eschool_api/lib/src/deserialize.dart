import 'package:eschool_api/src/model/academic_assignment.dart';
import 'package:eschool_api/src/model/academic_assignment_response.dart';
import 'package:eschool_api/src/model/academic_assignment_write.dart';
import 'package:eschool_api/src/model/academic_assignments_response.dart';
import 'package:eschool_api/src/model/academic_assignments_response_data.dart';
import 'package:eschool_api/src/model/academic_attendance_today_data.dart';
import 'package:eschool_api/src/model/academic_attendance_today_entry.dart';
import 'package:eschool_api/src/model/academic_attendance_today_response.dart';
import 'package:eschool_api/src/model/academic_bell_period.dart';
import 'package:eschool_api/src/model/academic_bell_period_write.dart';
import 'package:eschool_api/src/model/academic_bells.dart';
import 'package:eschool_api/src/model/academic_bells_response.dart';
import 'package:eschool_api/src/model/academic_bells_write.dart';
import 'package:eschool_api/src/model/academic_capture_data.dart';
import 'package:eschool_api/src/model/academic_capture_response.dart';
import 'package:eschool_api/src/model/academic_component_result.dart';
import 'package:eschool_api/src/model/academic_grade_level.dart';
import 'package:eschool_api/src/model/academic_learner_preview.dart';
import 'package:eschool_api/src/model/academic_lesson_write.dart';
import 'package:eschool_api/src/model/academic_mark.dart';
import 'package:eschool_api/src/model/academic_mark_capabilities.dart';
import 'package:eschool_api/src/model/academic_mark_result.dart';
import 'package:eschool_api/src/model/academic_mark_row.dart';
import 'package:eschool_api/src/model/academic_mark_write.dart';
import 'package:eschool_api/src/model/academic_matrix_data.dart';
import 'package:eschool_api/src/model/academic_matrix_learner.dart';
import 'package:eschool_api/src/model/academic_matrix_response.dart';
import 'package:eschool_api/src/model/academic_matrix_task.dart';
import 'package:eschool_api/src/model/academic_moderation_row.dart';
import 'package:eschool_api/src/model/academic_moderation_write.dart';
import 'package:eschool_api/src/model/academic_preview_data.dart';
import 'package:eschool_api/src/model/academic_preview_response.dart';
import 'package:eschool_api/src/model/academic_question_score.dart';
import 'package:eschool_api/src/model/academic_rating_level.dart';
import 'package:eschool_api/src/model/academic_reference.dart';
import 'package:eschool_api/src/model/academic_reference_response.dart';
import 'package:eschool_api/src/model/academic_register_choices_data.dart';
import 'package:eschool_api/src/model/academic_register_choices_response.dart';
import 'package:eschool_api/src/model/academic_register_entry_revision.dart';
import 'package:eschool_api/src/model/academic_register_learner.dart';
import 'package:eschool_api/src/model/academic_register_matrix_data.dart';
import 'package:eschool_api/src/model/academic_register_matrix_response.dart';
import 'package:eschool_api/src/model/academic_register_qr_data.dart';
import 'package:eschool_api/src/model/academic_register_qr_response.dart';
import 'package:eschool_api/src/model/academic_register_row_write.dart';
import 'package:eschool_api/src/model/academic_register_settings_data.dart';
import 'package:eschool_api/src/model/academic_register_settings_response.dart';
import 'package:eschool_api/src/model/academic_register_sync_data.dart';
import 'package:eschool_api/src/model/academic_register_sync_response.dart';
import 'package:eschool_api/src/model/academic_register_sync_write.dart';
import 'package:eschool_api/src/model/academic_register_target_write.dart';
import 'package:eschool_api/src/model/academic_release_commit_write.dart';
import 'package:eschool_api/src/model/academic_release_data.dart';
import 'package:eschool_api/src/model/academic_release_learner.dart';
import 'package:eschool_api/src/model/academic_release_period.dart';
import 'package:eschool_api/src/model/academic_release_periods_data.dart';
import 'package:eschool_api/src/model/academic_release_periods_response.dart';
import 'package:eschool_api/src/model/academic_release_preview_data.dart';
import 'package:eschool_api/src/model/academic_release_preview_response.dart';
import 'package:eschool_api/src/model/academic_release_response.dart';
import 'package:eschool_api/src/model/academic_release_write.dart';
import 'package:eschool_api/src/model/academic_released_data.dart';
import 'package:eschool_api/src/model/academic_released_response.dart';
import 'package:eschool_api/src/model/academic_released_result.dart';
import 'package:eschool_api/src/model/academic_room.dart';
import 'package:eschool_api/src/model/academic_room_response.dart';
import 'package:eschool_api/src/model/academic_room_write.dart';
import 'package:eschool_api/src/model/academic_stored_question.dart';
import 'package:eschool_api/src/model/academic_task_question.dart';
import 'package:eschool_api/src/model/academic_task_result.dart';
import 'package:eschool_api/src/model/academic_timetable_choice.dart';
import 'package:eschool_api/src/model/academic_timetable_clash.dart';
import 'package:eschool_api/src/model/academic_timetable_commit_data.dart';
import 'package:eschool_api/src/model/academic_timetable_commit_response.dart';
import 'package:eschool_api/src/model/academic_timetable_commit_write.dart';
import 'package:eschool_api/src/model/academic_timetable_configuration_data.dart';
import 'package:eschool_api/src/model/academic_timetable_configuration_response.dart';
import 'package:eschool_api/src/model/academic_timetable_import_defaults.dart';
import 'package:eschool_api/src/model/academic_timetable_import_write.dart';
import 'package:eschool_api/src/model/academic_timetable_lesson.dart';
import 'package:eschool_api/src/model/academic_timetable_next_data.dart';
import 'package:eschool_api/src/model/academic_timetable_next_response.dart';
import 'package:eschool_api/src/model/academic_timetable_preview_data.dart';
import 'package:eschool_api/src/model/academic_timetable_preview_response.dart';
import 'package:eschool_api/src/model/academic_timetable_preview_write.dart';
import 'package:eschool_api/src/model/academic_timetable_week_data.dart';
import 'package:eschool_api/src/model/academic_timetable_week_response.dart';
import 'package:eschool_api/src/model/accept_guardian_invitation_request.dart';
import 'package:eschool_api/src/model/approval_learner.dart';
import 'package:eschool_api/src/model/approval_state.dart';
import 'package:eschool_api/src/model/approval_state_response.dart';
import 'package:eschool_api/src/model/branding.dart';
import 'package:eschool_api/src/model/calendar_removed_response.dart';
import 'package:eschool_api/src/model/child.dart';
import 'package:eschool_api/src/model/class_grade_mapping.dart';
import 'package:eschool_api/src/model/class_grade_mapping_grade_level.dart';
import 'package:eschool_api/src/model/class_grade_response.dart';
import 'package:eschool_api/src/model/class_grade_write.dart';
import 'package:eschool_api/src/model/context.dart';
import 'package:eschool_api/src/model/context_response.dart';
import 'package:eschool_api/src/model/context_user.dart';
import 'package:eschool_api/src/model/create_learner_enrolment_request.dart';
import 'package:eschool_api/src/model/create_profile_category_request.dart';
import 'package:eschool_api/src/model/create_profile_field_request.dart';
import 'package:eschool_api/src/model/current_school.dart';
import 'package:eschool_api/src/model/curriculum_assessment.dart';
import 'package:eschool_api/src/model/curriculum_assessment_write.dart';
import 'package:eschool_api/src/model/curriculum_copy_write.dart';
import 'package:eschool_api/src/model/curriculum_plan.dart';
import 'package:eschool_api/src/model/curriculum_plan_response.dart';
import 'package:eschool_api/src/model/curriculum_plan_summary.dart';
import 'package:eschool_api/src/model/curriculum_plan_write.dart';
import 'package:eschool_api/src/model/curriculum_plans_response.dart';
import 'package:eschool_api/src/model/curriculum_plans_response_data.dart';
import 'package:eschool_api/src/model/curriculum_publish_write.dart';
import 'package:eschool_api/src/model/curriculum_question.dart';
import 'package:eschool_api/src/model/curriculum_question_write.dart';
import 'package:eschool_api/src/model/curriculum_task.dart';
import 'package:eschool_api/src/model/curriculum_task_write.dart';
import 'package:eschool_api/src/model/curriculum_topic.dart';
import 'package:eschool_api/src/model/curriculum_topic_write.dart';
import 'package:eschool_api/src/model/decide_guardian_approval_request.dart';
import 'package:eschool_api/src/model/decide_guardian_handle_proposal_request.dart';
import 'package:eschool_api/src/model/enrolment.dart';
import 'package:eschool_api/src/model/enrolment_list_response.dart';
import 'package:eschool_api/src/model/enrolment_list_response_data.dart';
import 'package:eschool_api/src/model/enrolment_response.dart';
import 'package:eschool_api/src/model/error_response.dart';
import 'package:eschool_api/src/model/guardian_approval.dart';
import 'package:eschool_api/src/model/guardian_approvals_response.dart';
import 'package:eschool_api/src/model/guardian_consent.dart';
import 'package:eschool_api/src/model/guardian_handle_proposal.dart';
import 'package:eschool_api/src/model/guardian_handle_proposals_response.dart';
import 'package:eschool_api/src/model/guardian_invitation_accepted_response.dart';
import 'package:eschool_api/src/model/guardian_invitation_issued.dart';
import 'package:eschool_api/src/model/guardian_invitation_issued_response.dart';
import 'package:eschool_api/src/model/guardian_invitation_preview.dart';
import 'package:eschool_api/src/model/guardian_invitation_preview_learners_inner.dart';
import 'package:eschool_api/src/model/guardian_invitation_preview_response.dart';
import 'package:eschool_api/src/model/handle_proposal.dart';
import 'package:eschool_api/src/model/handle_state.dart';
import 'package:eschool_api/src/model/handle_state_response.dart';
import 'package:eschool_api/src/model/module.dart';
import 'package:eschool_api/src/model/my_school_info.dart';
import 'package:eschool_api/src/model/my_school_response.dart';
import 'package:eschool_api/src/model/notification_channel_preference.dart';
import 'package:eschool_api/src/model/notification_preferences_response.dart';
import 'package:eschool_api/src/model/notification_rule.dart';
import 'package:eschool_api/src/model/notification_type_preference.dart';
import 'package:eschool_api/src/model/notification_type_preference_channels.dart';
import 'package:eschool_api/src/model/pagination.dart';
import 'package:eschool_api/src/model/profile_attachment.dart';
import 'package:eschool_api/src/model/profile_category_definition.dart';
import 'package:eschool_api/src/model/profile_category_response.dart';
import 'package:eschool_api/src/model/profile_configuration_response.dart';
import 'package:eschool_api/src/model/profile_definition.dart';
import 'package:eschool_api/src/model/profile_definition_response.dart';
import 'package:eschool_api/src/model/profile_field_summary.dart';
import 'package:eschool_api/src/model/profile_form.dart';
import 'package:eschool_api/src/model/profile_form_category.dart';
import 'package:eschool_api/src/model/profile_form_field.dart';
import 'package:eschool_api/src/model/profile_form_response.dart';
import 'package:eschool_api/src/model/profile_saved_response.dart';
import 'package:eschool_api/src/model/profile_validation_rules.dart';
import 'package:eschool_api/src/model/profile_value.dart';
import 'package:eschool_api/src/model/school_document.dart';
import 'package:eschool_api/src/model/school_membership.dart';
import 'package:eschool_api/src/model/school_subject_from_standard_write.dart';
import 'package:eschool_api/src/model/school_subject_standard.dart';
import 'package:eschool_api/src/model/school_subject_standard_response.dart';
import 'package:eschool_api/src/model/school_subject_standard_write.dart';
import 'package:eschool_api/src/model/school_summary.dart';
import 'package:eschool_api/src/model/school_term.dart';
import 'package:eschool_api/src/model/school_term_draft.dart';
import 'package:eschool_api/src/model/school_term_response.dart';
import 'package:eschool_api/src/model/school_term_write.dart';
import 'package:eschool_api/src/model/school_terms_response.dart';
import 'package:eschool_api/src/model/school_terms_response_data.dart';
import 'package:eschool_api/src/model/session_year.dart';
import 'package:eschool_api/src/model/set_my_handle_request.dart';
import 'package:eschool_api/src/model/subject_standard.dart';
import 'package:eschool_api/src/model/subject_standards_response.dart';
import 'package:eschool_api/src/model/subject_standards_response_data.dart';
import 'package:eschool_api/src/model/teaching_group.dart';
import 'package:eschool_api/src/model/teaching_group_response.dart';
import 'package:eschool_api/src/model/teaching_group_write.dart';
import 'package:eschool_api/src/model/teaching_groups_response.dart';
import 'package:eschool_api/src/model/teaching_groups_response_data.dart';
import 'package:eschool_api/src/model/term.dart';
import 'package:eschool_api/src/model/term_dates.dart';
import 'package:eschool_api/src/model/term_template.dart';
import 'package:eschool_api/src/model/term_template_apply_response.dart';
import 'package:eschool_api/src/model/term_template_apply_response_data.dart';
import 'package:eschool_api/src/model/term_template_apply_write.dart';
import 'package:eschool_api/src/model/term_template_response.dart';
import 'package:eschool_api/src/model/term_template_write.dart';
import 'package:eschool_api/src/model/term_templates_response.dart';
import 'package:eschool_api/src/model/term_templates_response_data.dart';
import 'package:eschool_api/src/model/update_my_notification_preferences_request.dart';
import 'package:eschool_api/src/model/update_my_profile_request.dart';
import 'package:eschool_api/src/model/update_person_profile_request.dart';
import 'package:eschool_api/src/model/update_profile_category_request.dart';
import 'package:eschool_api/src/model/update_profile_field_request.dart';
import 'package:eschool_api/src/model/validation_errors.dart';

final _regList = RegExp(r'^List<(.*)>$');
final _regSet = RegExp(r'^Set<(.*)>$');
final _regMap = RegExp(r'^Map<String,(.*)>$');

ReturnType deserialize<ReturnType, BaseType>(
  dynamic value,
  String targetType, {
  bool growable = true,
}) {
  switch (targetType) {
    case 'String':
      return '$value' as ReturnType;
    case 'int':
      return (value is int ? value : int.parse('$value')) as ReturnType;
    case 'bool':
      if (value is bool) {
        return value as ReturnType;
      }
      final valueString = '$value'.toLowerCase();
      return (valueString == 'true' || valueString == '1') as ReturnType;
    case 'double':
      return (value is double ? value : double.parse('$value')) as ReturnType;
    case 'AcademicAssignment':
      return AcademicAssignment.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'AcademicAssignmentResponse':
      return AcademicAssignmentResponse.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'AcademicAssignmentWrite':
      return AcademicAssignmentWrite.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'AcademicAssignmentsResponse':
      return AcademicAssignmentsResponse.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'AcademicAssignmentsResponseData':
      return AcademicAssignmentsResponseData.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'AcademicAttendanceTodayData':
      return AcademicAttendanceTodayData.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'AcademicAttendanceTodayEntry':
      return AcademicAttendanceTodayEntry.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'AcademicAttendanceTodayResponse':
      return AcademicAttendanceTodayResponse.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'AcademicBellPeriod':
      return AcademicBellPeriod.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'AcademicBellPeriodWrite':
      return AcademicBellPeriodWrite.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'AcademicBells':
      return AcademicBells.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'AcademicBellsResponse':
      return AcademicBellsResponse.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'AcademicBellsWrite':
      return AcademicBellsWrite.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'AcademicCaptureData':
      return AcademicCaptureData.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'AcademicCaptureResponse':
      return AcademicCaptureResponse.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'AcademicComponentResult':
      return AcademicComponentResult.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'AcademicGradeLevel':
      return AcademicGradeLevel.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'AcademicLearnerPreview':
      return AcademicLearnerPreview.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'AcademicLessonWrite':
      return AcademicLessonWrite.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'AcademicMark':
      return AcademicMark.fromJson(value as Map<String, dynamic>) as ReturnType;
    case 'AcademicMarkCapabilities':
      return AcademicMarkCapabilities.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'AcademicMarkResult':
      return AcademicMarkResult.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'AcademicMarkRow':
      return AcademicMarkRow.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'AcademicMarkWrite':
      return AcademicMarkWrite.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'AcademicMatrixData':
      return AcademicMatrixData.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'AcademicMatrixLearner':
      return AcademicMatrixLearner.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'AcademicMatrixResponse':
      return AcademicMatrixResponse.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'AcademicMatrixTask':
      return AcademicMatrixTask.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'AcademicModerationRow':
      return AcademicModerationRow.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'AcademicModerationWrite':
      return AcademicModerationWrite.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'AcademicPreviewData':
      return AcademicPreviewData.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'AcademicPreviewResponse':
      return AcademicPreviewResponse.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'AcademicQuestionScore':
      return AcademicQuestionScore.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'AcademicRatingLevel':
      return AcademicRatingLevel.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'AcademicReference':
      return AcademicReference.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'AcademicReferenceResponse':
      return AcademicReferenceResponse.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'AcademicRegisterChoicesData':
      return AcademicRegisterChoicesData.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'AcademicRegisterChoicesResponse':
      return AcademicRegisterChoicesResponse.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'AcademicRegisterEntryRevision':
      return AcademicRegisterEntryRevision.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'AcademicRegisterLearner':
      return AcademicRegisterLearner.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'AcademicRegisterMatrixData':
      return AcademicRegisterMatrixData.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'AcademicRegisterMatrixResponse':
      return AcademicRegisterMatrixResponse.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'AcademicRegisterQrData':
      return AcademicRegisterQrData.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'AcademicRegisterQrResponse':
      return AcademicRegisterQrResponse.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'AcademicRegisterRowWrite':
      return AcademicRegisterRowWrite.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'AcademicRegisterSettingsData':
      return AcademicRegisterSettingsData.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'AcademicRegisterSettingsResponse':
      return AcademicRegisterSettingsResponse.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'AcademicRegisterSyncData':
      return AcademicRegisterSyncData.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'AcademicRegisterSyncResponse':
      return AcademicRegisterSyncResponse.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'AcademicRegisterSyncWrite':
      return AcademicRegisterSyncWrite.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'AcademicRegisterTargetWrite':
      return AcademicRegisterTargetWrite.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'AcademicReleaseCommitWrite':
      return AcademicReleaseCommitWrite.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'AcademicReleaseData':
      return AcademicReleaseData.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'AcademicReleaseLearner':
      return AcademicReleaseLearner.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'AcademicReleasePeriod':
      return AcademicReleasePeriod.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'AcademicReleasePeriodsData':
      return AcademicReleasePeriodsData.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'AcademicReleasePeriodsResponse':
      return AcademicReleasePeriodsResponse.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'AcademicReleasePreviewData':
      return AcademicReleasePreviewData.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'AcademicReleasePreviewResponse':
      return AcademicReleasePreviewResponse.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'AcademicReleaseResponse':
      return AcademicReleaseResponse.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'AcademicReleaseWrite':
      return AcademicReleaseWrite.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'AcademicReleasedData':
      return AcademicReleasedData.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'AcademicReleasedResponse':
      return AcademicReleasedResponse.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'AcademicReleasedResult':
      return AcademicReleasedResult.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'AcademicRoom':
      return AcademicRoom.fromJson(value as Map<String, dynamic>) as ReturnType;
    case 'AcademicRoomResponse':
      return AcademicRoomResponse.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'AcademicRoomWrite':
      return AcademicRoomWrite.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'AcademicStoredQuestion':
      return AcademicStoredQuestion.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'AcademicTaskQuestion':
      return AcademicTaskQuestion.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'AcademicTaskResult':
      return AcademicTaskResult.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'AcademicTimetableChoice':
      return AcademicTimetableChoice.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'AcademicTimetableClash':
      return AcademicTimetableClash.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'AcademicTimetableCommitData':
      return AcademicTimetableCommitData.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'AcademicTimetableCommitResponse':
      return AcademicTimetableCommitResponse.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'AcademicTimetableCommitWrite':
      return AcademicTimetableCommitWrite.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'AcademicTimetableConfigurationData':
      return AcademicTimetableConfigurationData.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'AcademicTimetableConfigurationResponse':
      return AcademicTimetableConfigurationResponse.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'AcademicTimetableImportDefaults':
      return AcademicTimetableImportDefaults.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'AcademicTimetableImportWrite':
      return AcademicTimetableImportWrite.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'AcademicTimetableLesson':
      return AcademicTimetableLesson.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'AcademicTimetableNextData':
      return AcademicTimetableNextData.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'AcademicTimetableNextResponse':
      return AcademicTimetableNextResponse.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'AcademicTimetablePreviewData':
      return AcademicTimetablePreviewData.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'AcademicTimetablePreviewResponse':
      return AcademicTimetablePreviewResponse.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'AcademicTimetablePreviewWrite':
      return AcademicTimetablePreviewWrite.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'AcademicTimetableWeekData':
      return AcademicTimetableWeekData.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'AcademicTimetableWeekResponse':
      return AcademicTimetableWeekResponse.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'AcceptGuardianInvitationRequest':
      return AcceptGuardianInvitationRequest.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'ApprovalLearner':
      return ApprovalLearner.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'ApprovalState':
      return ApprovalState.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'ApprovalStateResponse':
      return ApprovalStateResponse.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'Branding':
      return Branding.fromJson(value as Map<String, dynamic>) as ReturnType;
    case 'CalendarRemovedResponse':
      return CalendarRemovedResponse.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'Child':
      return Child.fromJson(value as Map<String, dynamic>) as ReturnType;
    case 'ClassGradeMapping':
      return ClassGradeMapping.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'ClassGradeMappingGradeLevel':
      return ClassGradeMappingGradeLevel.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'ClassGradeResponse':
      return ClassGradeResponse.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'ClassGradeWrite':
      return ClassGradeWrite.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'Context':
      return Context.fromJson(value as Map<String, dynamic>) as ReturnType;
    case 'ContextResponse':
      return ContextResponse.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'ContextUser':
      return ContextUser.fromJson(value as Map<String, dynamic>) as ReturnType;
    case 'CreateLearnerEnrolmentRequest':
      return CreateLearnerEnrolmentRequest.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'CreateProfileCategoryRequest':
      return CreateProfileCategoryRequest.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'CreateProfileFieldRequest':
      return CreateProfileFieldRequest.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'CurrentSchool':
      return CurrentSchool.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'CurriculumAssessment':
      return CurriculumAssessment.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'CurriculumAssessmentWrite':
      return CurriculumAssessmentWrite.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'CurriculumCopyWrite':
      return CurriculumCopyWrite.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'CurriculumPlan':
      return CurriculumPlan.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'CurriculumPlanResponse':
      return CurriculumPlanResponse.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'CurriculumPlanSummary':
      return CurriculumPlanSummary.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'CurriculumPlanWrite':
      return CurriculumPlanWrite.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'CurriculumPlansResponse':
      return CurriculumPlansResponse.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'CurriculumPlansResponseData':
      return CurriculumPlansResponseData.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'CurriculumPublishWrite':
      return CurriculumPublishWrite.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'CurriculumQuestion':
      return CurriculumQuestion.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'CurriculumQuestionWrite':
      return CurriculumQuestionWrite.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'CurriculumTask':
      return CurriculumTask.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'CurriculumTaskWrite':
      return CurriculumTaskWrite.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'CurriculumTopic':
      return CurriculumTopic.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'CurriculumTopicWrite':
      return CurriculumTopicWrite.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'DecideGuardianApprovalRequest':
      return DecideGuardianApprovalRequest.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'DecideGuardianHandleProposalRequest':
      return DecideGuardianHandleProposalRequest.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'Enrolment':
      return Enrolment.fromJson(value as Map<String, dynamic>) as ReturnType;
    case 'EnrolmentListResponse':
      return EnrolmentListResponse.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'EnrolmentListResponseData':
      return EnrolmentListResponseData.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'EnrolmentResponse':
      return EnrolmentResponse.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'ErrorCode':
    case 'ErrorResponse':
      return ErrorResponse.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'GuardianApproval':
      return GuardianApproval.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'GuardianApprovalsResponse':
      return GuardianApprovalsResponse.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'GuardianConsent':
      return GuardianConsent.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'GuardianHandleProposal':
      return GuardianHandleProposal.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'GuardianHandleProposalsResponse':
      return GuardianHandleProposalsResponse.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'GuardianInvitationAcceptedResponse':
      return GuardianInvitationAcceptedResponse.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'GuardianInvitationIssued':
      return GuardianInvitationIssued.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'GuardianInvitationIssuedResponse':
      return GuardianInvitationIssuedResponse.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'GuardianInvitationPreview':
      return GuardianInvitationPreview.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'GuardianInvitationPreviewLearnersInner':
      return GuardianInvitationPreviewLearnersInner.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'GuardianInvitationPreviewResponse':
      return GuardianInvitationPreviewResponse.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'HandleProposal':
      return HandleProposal.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'HandleState':
      return HandleState.fromJson(value as Map<String, dynamic>) as ReturnType;
    case 'HandleStateResponse':
      return HandleStateResponse.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'Module':
      return Module.fromJson(value as Map<String, dynamic>) as ReturnType;
    case 'MySchoolInfo':
      return MySchoolInfo.fromJson(value as Map<String, dynamic>) as ReturnType;
    case 'MySchoolResponse':
      return MySchoolResponse.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'NotificationChannelPreference':
      return NotificationChannelPreference.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'NotificationPreferencesResponse':
      return NotificationPreferencesResponse.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'NotificationRule':
      return NotificationRule.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'NotificationTypePreference':
      return NotificationTypePreference.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'NotificationTypePreferenceChannels':
      return NotificationTypePreferenceChannels.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'Pagination':
      return Pagination.fromJson(value as Map<String, dynamic>) as ReturnType;
    case 'ProfileAttachment':
      return ProfileAttachment.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'ProfileCategoryDefinition':
      return ProfileCategoryDefinition.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'ProfileCategoryResponse':
      return ProfileCategoryResponse.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'ProfileConfigurationResponse':
      return ProfileConfigurationResponse.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'ProfileDefinition':
      return ProfileDefinition.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'ProfileDefinitionResponse':
      return ProfileDefinitionResponse.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'ProfileFieldSummary':
      return ProfileFieldSummary.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'ProfileForm':
      return ProfileForm.fromJson(value as Map<String, dynamic>) as ReturnType;
    case 'ProfileFormCategory':
      return ProfileFormCategory.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'ProfileFormField':
      return ProfileFormField.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'ProfileFormResponse':
      return ProfileFormResponse.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'ProfileSavedResponse':
      return ProfileSavedResponse.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'ProfileValidationRules':
      return ProfileValidationRules.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'ProfileValue':
      return ProfileValue.fromJson(value as Map<String, dynamic>) as ReturnType;
    case 'SchoolDocument':
      return SchoolDocument.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'SchoolMembership':
      return SchoolMembership.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'SchoolSubjectFromStandardWrite':
      return SchoolSubjectFromStandardWrite.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'SchoolSubjectStandard':
      return SchoolSubjectStandard.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'SchoolSubjectStandardResponse':
      return SchoolSubjectStandardResponse.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'SchoolSubjectStandardWrite':
      return SchoolSubjectStandardWrite.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'SchoolSummary':
      return SchoolSummary.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'SchoolTerm':
      return SchoolTerm.fromJson(value as Map<String, dynamic>) as ReturnType;
    case 'SchoolTermDraft':
      return SchoolTermDraft.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'SchoolTermResponse':
      return SchoolTermResponse.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'SchoolTermWrite':
      return SchoolTermWrite.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'SchoolTermsResponse':
      return SchoolTermsResponse.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'SchoolTermsResponseData':
      return SchoolTermsResponseData.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'SessionYear':
      return SessionYear.fromJson(value as Map<String, dynamic>) as ReturnType;
    case 'SetMyHandleRequest':
      return SetMyHandleRequest.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'SubjectStandard':
      return SubjectStandard.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'SubjectStandardsResponse':
      return SubjectStandardsResponse.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'SubjectStandardsResponseData':
      return SubjectStandardsResponseData.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'TeachingGroup':
      return TeachingGroup.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'TeachingGroupResponse':
      return TeachingGroupResponse.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'TeachingGroupWrite':
      return TeachingGroupWrite.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'TeachingGroupsResponse':
      return TeachingGroupsResponse.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'TeachingGroupsResponseData':
      return TeachingGroupsResponseData.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'Term':
      return Term.fromJson(value as Map<String, dynamic>) as ReturnType;
    case 'TermDates':
      return TermDates.fromJson(value as Map<String, dynamic>) as ReturnType;
    case 'TermTemplate':
      return TermTemplate.fromJson(value as Map<String, dynamic>) as ReturnType;
    case 'TermTemplateApplyResponse':
      return TermTemplateApplyResponse.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'TermTemplateApplyResponseData':
      return TermTemplateApplyResponseData.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'TermTemplateApplyWrite':
      return TermTemplateApplyWrite.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'TermTemplateResponse':
      return TermTemplateResponse.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'TermTemplateWrite':
      return TermTemplateWrite.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'TermTemplatesResponse':
      return TermTemplatesResponse.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'TermTemplatesResponseData':
      return TermTemplatesResponseData.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'UpdateMyNotificationPreferencesRequest':
      return UpdateMyNotificationPreferencesRequest.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'UpdateMyProfileRequest':
      return UpdateMyProfileRequest.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'UpdatePersonProfileRequest':
      return UpdatePersonProfileRequest.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'UpdateProfileCategoryRequest':
      return UpdateProfileCategoryRequest.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'UpdateProfileFieldRequest':
      return UpdateProfileFieldRequest.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'ValidationErrors':
      return ValidationErrors.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    default:
      RegExpMatch? match;

      if (value is List && (match = _regList.firstMatch(targetType)) != null) {
        targetType = match![1]!; // ignore: parameter_assignments
        return value
                .map<BaseType>(
                  (dynamic v) => deserialize<BaseType, BaseType>(
                    v,
                    targetType,
                    growable: growable,
                  ),
                )
                .toList(growable: growable)
            as ReturnType;
      }
      if (value is Set && (match = _regSet.firstMatch(targetType)) != null) {
        targetType = match![1]!; // ignore: parameter_assignments
        return value
                .map<BaseType>(
                  (dynamic v) => deserialize<BaseType, BaseType>(
                    v,
                    targetType,
                    growable: growable,
                  ),
                )
                .toSet()
            as ReturnType;
      }
      if (value is Map && (match = _regMap.firstMatch(targetType)) != null) {
        targetType = match![1]!.trim(); // ignore: parameter_assignments
        return Map<String, BaseType>.fromIterables(
              value.keys as Iterable<String>,
              value.values.map(
                (dynamic v) => deserialize<BaseType, BaseType>(
                  v,
                  targetType,
                  growable: growable,
                ),
              ),
            )
            as ReturnType;
      }
      break;
  }
  throw Exception('Cannot deserialize');
}
