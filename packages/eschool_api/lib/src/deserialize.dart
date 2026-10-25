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
import 'package:eschool_api/src/model/academic_exit_document_choice.dart';
import 'package:eschool_api/src/model/academic_exit_document_evidence.dart';
import 'package:eschool_api/src/model/academic_exit_issue.dart';
import 'package:eschool_api/src/model/academic_exit_package.dart';
import 'package:eschool_api/src/model/academic_exit_package_response.dart';
import 'package:eschool_api/src/model/academic_exit_prepare.dart';
import 'package:eschool_api/src/model/academic_exit_preview.dart';
import 'package:eschool_api/src/model/academic_exit_preview_response.dart';
import 'package:eschool_api/src/model/academic_exit_report_choice.dart';
import 'package:eschool_api/src/model/academic_exit_report_evidence.dart';
import 'package:eschool_api/src/model/academic_exit_selection.dart';
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
import 'package:eschool_api/src/model/academic_report_access_data.dart';
import 'package:eschool_api/src/model/academic_report_access_response.dart';
import 'package:eschool_api/src/model/academic_report_action_write.dart';
import 'package:eschool_api/src/model/academic_report_attendance.dart';
import 'package:eschool_api/src/model/academic_report_attendance_day.dart';
import 'package:eschool_api/src/model/academic_report_bank_saved_response.dart';
import 'package:eschool_api/src/model/academic_report_batch_data.dart';
import 'package:eschool_api/src/model/academic_report_batch_response.dart';
import 'package:eschool_api/src/model/academic_report_batch_summary.dart';
import 'package:eschool_api/src/model/academic_report_batches_data.dart';
import 'package:eschool_api/src/model/academic_report_batches_response.dart';
import 'package:eschool_api/src/model/academic_report_card.dart';
import 'package:eschool_api/src/model/academic_report_comment_bank.dart';
import 'package:eschool_api/src/model/academic_report_comment_bank_write.dart';
import 'package:eschool_api/src/model/academic_report_comment_data.dart';
import 'package:eschool_api/src/model/academic_report_comment_response.dart';
import 'package:eschool_api/src/model/academic_report_comment_row.dart';
import 'package:eschool_api/src/model/academic_report_comment_write.dart';
import 'package:eschool_api/src/model/academic_report_comments_data.dart';
import 'package:eschool_api/src/model/academic_report_comments_response.dart';
import 'package:eschool_api/src/model/academic_report_configuration_data.dart';
import 'package:eschool_api/src/model/academic_report_configuration_response.dart';
import 'package:eschool_api/src/model/academic_report_design.dart';
import 'package:eschool_api/src/model/academic_report_design_snapshot.dart';
import 'package:eschool_api/src/model/academic_report_detail_card.dart';
import 'package:eschool_api/src/model/academic_report_detail_data.dart';
import 'package:eschool_api/src/model/academic_report_detail_response.dart';
import 'package:eschool_api/src/model/academic_report_enrolment.dart';
import 'package:eschool_api/src/model/academic_report_family_card.dart';
import 'package:eschool_api/src/model/academic_report_family_data.dart';
import 'package:eschool_api/src/model/academic_report_family_response.dart';
import 'package:eschool_api/src/model/academic_report_homeroom.dart';
import 'package:eschool_api/src/model/academic_report_payload.dart';
import 'package:eschool_api/src/model/academic_report_prepare_write.dart';
import 'package:eschool_api/src/model/academic_report_preview_data.dart';
import 'package:eschool_api/src/model/academic_report_preview_response.dart';
import 'package:eschool_api/src/model/academic_report_read_data.dart';
import 'package:eschool_api/src/model/academic_report_read_response.dart';
import 'package:eschool_api/src/model/academic_report_release_preview_data.dart';
import 'package:eschool_api/src/model/academic_report_release_preview_response.dart';
import 'package:eschool_api/src/model/academic_report_roster_data.dart';
import 'package:eschool_api/src/model/academic_report_roster_response.dart';
import 'package:eschool_api/src/model/academic_report_saved_revision.dart';
import 'package:eschool_api/src/model/academic_report_selection.dart';
import 'package:eschool_api/src/model/academic_report_signature.dart';
import 'package:eschool_api/src/model/academic_report_signatures.dart';
import 'package:eschool_api/src/model/academic_report_source_data.dart';
import 'package:eschool_api/src/model/academic_report_source_response.dart';
import 'package:eschool_api/src/model/academic_report_stored_comment.dart';
import 'package:eschool_api/src/model/academic_report_subject_result.dart';
import 'package:eschool_api/src/model/academic_report_template.dart';
import 'package:eschool_api/src/model/academic_report_template_response.dart';
import 'package:eschool_api/src/model/academic_report_template_write.dart';
import 'package:eschool_api/src/model/academic_report_term.dart';
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
import 'package:eschool_api/src/model/academic_transition_assignment.dart';
import 'package:eschool_api/src/model/academic_transition_commit.dart';
import 'package:eschool_api/src/model/academic_transition_configuration.dart';
import 'package:eschool_api/src/model/academic_transition_configuration_response.dart';
import 'package:eschool_api/src/model/academic_transition_enrolment.dart';
import 'package:eschool_api/src/model/academic_transition_fact.dart';
import 'package:eschool_api/src/model/academic_transition_grade.dart';
import 'package:eschool_api/src/model/academic_transition_grade_policy.dart';
import 'package:eschool_api/src/model/academic_transition_grade_policy_response.dart';
import 'package:eschool_api/src/model/academic_transition_group.dart';
import 'package:eschool_api/src/model/academic_transition_homeroom.dart';
import 'package:eschool_api/src/model/academic_transition_input.dart';
import 'package:eschool_api/src/model/academic_transition_learner.dart';
import 'package:eschool_api/src/model/academic_transition_learner_choice.dart';
import 'package:eschool_api/src/model/academic_transition_learner_response.dart';
import 'package:eschool_api/src/model/academic_transition_membership.dart';
import 'package:eschool_api/src/model/academic_transition_policy_write.dart';
import 'package:eschool_api/src/model/academic_transition_preview.dart';
import 'package:eschool_api/src/model/academic_transition_preview_response.dart';
import 'package:eschool_api/src/model/academic_transition_record.dart';
import 'package:eschool_api/src/model/academic_transition_record_response.dart';
import 'package:eschool_api/src/model/academic_transition_result.dart';
import 'package:eschool_api/src/model/academic_transition_retention.dart';
import 'package:eschool_api/src/model/academic_transition_retention_response.dart';
import 'package:eschool_api/src/model/academic_transition_retention_write.dart';
import 'package:eschool_api/src/model/academic_transition_search.dart';
import 'package:eschool_api/src/model/academic_transition_search_response.dart';
import 'package:eschool_api/src/model/academic_transition_state.dart';
import 'package:eschool_api/src/model/academic_transition_year.dart';
import 'package:eschool_api/src/model/accept_guardian_invitation_request.dart';
import 'package:eschool_api/src/model/activity_attendance.dart';
import 'package:eschool_api/src/model/activity_attendance_entry_write.dart';
import 'package:eschool_api/src/model/activity_attendance_receipt.dart';
import 'package:eschool_api/src/model/activity_attendance_receipt_response.dart';
import 'package:eschool_api/src/model/activity_attendance_write.dart';
import 'package:eschool_api/src/model/activity_group.dart';
import 'package:eschool_api/src/model/activity_group_detail.dart';
import 'package:eschool_api/src/model/activity_group_detail_response.dart';
import 'package:eschool_api/src/model/activity_group_list.dart';
import 'package:eschool_api/src/model/activity_group_list_response.dart';
import 'package:eschool_api/src/model/activity_group_response.dart';
import 'package:eschool_api/src/model/activity_group_roster.dart';
import 'package:eschool_api/src/model/activity_group_roster_response.dart';
import 'package:eschool_api/src/model/activity_group_staff.dart';
import 'package:eschool_api/src/model/activity_group_staff_response.dart';
import 'package:eschool_api/src/model/activity_group_write.dart';
import 'package:eschool_api/src/model/activity_material.dart';
import 'package:eschool_api/src/model/activity_material_response.dart';
import 'package:eschool_api/src/model/activity_material_view.dart';
import 'package:eschool_api/src/model/activity_material_write.dart';
import 'package:eschool_api/src/model/activity_membership.dart';
import 'package:eschool_api/src/model/activity_membership_response.dart';
import 'package:eschool_api/src/model/activity_membership_write.dart';
import 'package:eschool_api/src/model/activity_roster_member.dart';
import 'package:eschool_api/src/model/activity_session.dart';
import 'package:eschool_api/src/model/activity_session_response.dart';
import 'package:eschool_api/src/model/activity_session_roster.dart';
import 'package:eschool_api/src/model/activity_session_roster_learner.dart';
import 'package:eschool_api/src/model/activity_session_write.dart';
import 'package:eschool_api/src/model/activity_staff_write.dart';
import 'package:eschool_api/src/model/add_wellbeing_case_entry_response.dart';
import 'package:eschool_api/src/model/approval_learner.dart';
import 'package:eschool_api/src/model/approval_state.dart';
import 'package:eschool_api/src/model/approval_state_response.dart';
import 'package:eschool_api/src/model/approve_practice_question_response.dart';
import 'package:eschool_api/src/model/branding.dart';
import 'package:eschool_api/src/model/calendar_removed_response.dart';
import 'package:eschool_api/src/model/cancel_consultation_response.dart';
import 'package:eschool_api/src/model/cast_consultation_vote_response.dart';
import 'package:eschool_api/src/model/child.dart';
import 'package:eschool_api/src/model/class_grade_mapping.dart';
import 'package:eschool_api/src/model/class_grade_mapping_grade_level.dart';
import 'package:eschool_api/src/model/class_grade_response.dart';
import 'package:eschool_api/src/model/class_grade_write.dart';
import 'package:eschool_api/src/model/close_consultation_response.dart';
import 'package:eschool_api/src/model/communication_audience.dart';
import 'package:eschool_api/src/model/communication_audience_list.dart';
import 'package:eschool_api/src/model/communication_audience_list_response.dart';
import 'package:eschool_api/src/model/communication_audience_receipt.dart';
import 'package:eschool_api/src/model/communication_audience_receipt_response.dart';
import 'package:eschool_api/src/model/communication_audience_selection.dart';
import 'package:eschool_api/src/model/communication_audience_snapshot.dart';
import 'package:eschool_api/src/model/communication_audience_write.dart';
import 'package:eschool_api/src/model/communication_configuration.dart';
import 'package:eschool_api/src/model/communication_configuration_response.dart';
import 'package:eschool_api/src/model/communication_configuration_years_inner.dart';
import 'package:eschool_api/src/model/communication_contact_list.dart';
import 'package:eschool_api/src/model/communication_contact_list_response.dart';
import 'package:eschool_api/src/model/communication_decision_write.dart';
import 'package:eschool_api/src/model/communication_effective_rules.dart';
import 'package:eschool_api/src/model/communication_message_editor.dart';
import 'package:eschool_api/src/model/communication_message_editor_response.dart';
import 'package:eschool_api/src/model/communication_message_list.dart';
import 'package:eschool_api/src/model/communication_message_list_response.dart';
import 'package:eschool_api/src/model/communication_message_preview.dart';
import 'package:eschool_api/src/model/communication_message_preview_response.dart';
import 'package:eschool_api/src/model/communication_message_reader.dart';
import 'package:eschool_api/src/model/communication_message_reader_response.dart';
import 'package:eschool_api/src/model/communication_message_receipt.dart';
import 'package:eschool_api/src/model/communication_message_receipt_response.dart';
import 'package:eschool_api/src/model/communication_message_write.dart';
import 'package:eschool_api/src/model/communication_read_receipt.dart';
import 'package:eschool_api/src/model/communication_read_receipt_response.dart';
import 'package:eschool_api/src/model/communication_role_rule.dart';
import 'package:eschool_api/src/model/communication_role_rule_response.dart';
import 'package:eschool_api/src/model/communication_rule_list.dart';
import 'package:eschool_api/src/model/communication_rule_list_response.dart';
import 'package:eschool_api/src/model/communication_rule_write.dart';
import 'package:eschool_api/src/model/communication_scope.dart';
import 'package:eschool_api/src/model/communication_submit_write.dart';
import 'package:eschool_api/src/model/communication_summary.dart';
import 'package:eschool_api/src/model/communication_withdraw_write.dart';
import 'package:eschool_api/src/model/consultation_access.dart';
import 'package:eschool_api/src/model/consultation_audience.dart';
import 'package:eschool_api/src/model/consultation_audience_choice_page.dart';
import 'package:eschool_api/src/model/consultation_audience_input.dart';
import 'package:eschool_api/src/model/consultation_ballot.dart';
import 'package:eschool_api/src/model/consultation_choice.dart';
import 'package:eschool_api/src/model/consultation_configuration.dart';
import 'package:eschool_api/src/model/consultation_configuration_input.dart';
import 'package:eschool_api/src/model/consultation_configuration_snapshot.dart';
import 'package:eschool_api/src/model/consultation_end_input.dart';
import 'package:eschool_api/src/model/consultation_history_entry.dart';
import 'package:eschool_api/src/model/consultation_history_page.dart';
import 'package:eschool_api/src/model/consultation_history_record.dart';
import 'package:eschool_api/src/model/consultation_option.dart';
import 'package:eschool_api/src/model/consultation_option_count.dart';
import 'package:eschool_api/src/model/consultation_option_input.dart';
import 'package:eschool_api/src/model/consultation_page.dart';
import 'package:eschool_api/src/model/consultation_question.dart';
import 'package:eschool_api/src/model/consultation_question_input.dart';
import 'package:eschool_api/src/model/consultation_receipt.dart';
import 'package:eschool_api/src/model/consultation_response_answer.dart';
import 'package:eschool_api/src/model/consultation_response_entry.dart';
import 'package:eschool_api/src/model/consultation_response_page.dart';
import 'package:eschool_api/src/model/consultation_result_question.dart';
import 'package:eschool_api/src/model/consultation_results.dart';
import 'package:eschool_api/src/model/consultation_revision_input.dart';
import 'package:eschool_api/src/model/consultation_summary.dart';
import 'package:eschool_api/src/model/consultation_vote_answer.dart';
import 'package:eschool_api/src/model/consultation_vote_input.dart';
import 'package:eschool_api/src/model/consultation_vote_receipt.dart';
import 'package:eschool_api/src/model/content_archive_write.dart';
import 'package:eschool_api/src/model/content_configuration.dart';
import 'package:eschool_api/src/model/content_configuration_response.dart';
import 'package:eschool_api/src/model/content_draft_write.dart';
import 'package:eschool_api/src/model/content_group.dart';
import 'package:eschool_api/src/model/content_history.dart';
import 'package:eschool_api/src/model/content_item.dart';
import 'package:eschool_api/src/model/content_item_response.dart';
import 'package:eschool_api/src/model/content_library.dart';
import 'package:eschool_api/src/model/content_library_item.dart';
import 'package:eschool_api/src/model/content_library_response.dart';
import 'package:eschool_api/src/model/content_list.dart';
import 'package:eschool_api/src/model/content_list_item.dart';
import 'package:eschool_api/src/model/content_list_response.dart';
import 'package:eschool_api/src/model/content_preview.dart';
import 'package:eschool_api/src/model/content_preview_response.dart';
import 'package:eschool_api/src/model/content_publish_write.dart';
import 'package:eschool_api/src/model/content_read_receipt.dart';
import 'package:eschool_api/src/model/content_read_receipt_response.dart';
import 'package:eschool_api/src/model/content_reader.dart';
import 'package:eschool_api/src/model/content_reader_response.dart';
import 'package:eschool_api/src/model/content_receipt.dart';
import 'package:eschool_api/src/model/content_receipt_response.dart';
import 'package:eschool_api/src/model/content_revision_write.dart';
import 'package:eschool_api/src/model/content_saved_access.dart';
import 'package:eschool_api/src/model/content_saved_access_response.dart';
import 'package:eschool_api/src/model/content_saved_access_write.dart';
import 'package:eschool_api/src/model/content_selection.dart';
import 'package:eschool_api/src/model/content_settings.dart';
import 'package:eschool_api/src/model/content_settings_response.dart';
import 'package:eschool_api/src/model/content_source.dart';
import 'package:eschool_api/src/model/content_topic.dart';
import 'package:eschool_api/src/model/content_topic_choice.dart';
import 'package:eschool_api/src/model/content_version.dart';
import 'package:eschool_api/src/model/content_version_response.dart';
import 'package:eschool_api/src/model/content_year.dart';
import 'package:eschool_api/src/model/context.dart';
import 'package:eschool_api/src/model/context_response.dart';
import 'package:eschool_api/src/model/context_user.dart';
import 'package:eschool_api/src/model/cover_absence.dart';
import 'package:eschool_api/src/model/cover_absence_response.dart';
import 'package:eschool_api/src/model/cover_absence_summary.dart';
import 'package:eschool_api/src/model/cover_assignment_input.dart';
import 'package:eschool_api/src/model/cover_candidates.dart';
import 'package:eschool_api/src/model/cover_candidates_response.dart';
import 'package:eschool_api/src/model/cover_change_input.dart';
import 'package:eschool_api/src/model/cover_configuration.dart';
import 'package:eschool_api/src/model/cover_configuration_response.dart';
import 'package:eschool_api/src/model/cover_create_input.dart';
import 'package:eschool_api/src/model/cover_mine.dart';
import 'package:eschool_api/src/model/cover_mine_response.dart';
import 'package:eschool_api/src/model/cover_occurrence.dart';
import 'package:eschool_api/src/model/cover_occurrence_response.dart';
import 'package:eschool_api/src/model/cover_source.dart';
import 'package:eschool_api/src/model/cover_teacher.dart';
import 'package:eschool_api/src/model/create_consultation_response.dart';
import 'package:eschool_api/src/model/create_learner_enrolment_request.dart';
import 'package:eschool_api/src/model/create_profile_category_request.dart';
import 'package:eschool_api/src/model/create_profile_field_request.dart';
import 'package:eschool_api/src/model/create_wellbeing_appointment_response.dart';
import 'package:eschool_api/src/model/create_wellbeing_case_assignment_response.dart';
import 'package:eschool_api/src/model/create_wellbeing_category_response.dart';
import 'package:eschool_api/src/model/create_wellbeing_team_assignment_response.dart';
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
import 'package:eschool_api/src/model/discipline_acknowledge_write.dart';
import 'package:eschool_api/src/model/discipline_acknowledgement.dart';
import 'package:eschool_api/src/model/discipline_acknowledgement_response.dart';
import 'package:eschool_api/src/model/discipline_choice.dart';
import 'package:eschool_api/src/model/discipline_configuration.dart';
import 'package:eschool_api/src/model/discipline_configuration_response.dart';
import 'package:eschool_api/src/model/discipline_definition.dart';
import 'package:eschool_api/src/model/discipline_detention.dart';
import 'package:eschool_api/src/model/discipline_detention_attendance.dart';
import 'package:eschool_api/src/model/discipline_detention_attendance_response.dart';
import 'package:eschool_api/src/model/discipline_detention_attendance_write.dart';
import 'package:eschool_api/src/model/discipline_detention_cancel_write.dart';
import 'package:eschool_api/src/model/discipline_detention_detail.dart';
import 'package:eschool_api/src/model/discipline_detention_detail_response.dart';
import 'package:eschool_api/src/model/discipline_detention_enroll_write.dart';
import 'package:eschool_api/src/model/discipline_detention_enrollment.dart';
import 'package:eschool_api/src/model/discipline_detention_enrollment_response.dart';
import 'package:eschool_api/src/model/discipline_detention_entry.dart';
import 'package:eschool_api/src/model/discipline_detention_response.dart';
import 'package:eschool_api/src/model/discipline_detention_write.dart';
import 'package:eschool_api/src/model/discipline_detentions.dart';
import 'package:eschool_api/src/model/discipline_detentions_response.dart';
import 'package:eschool_api/src/model/discipline_family_detention.dart';
import 'package:eschool_api/src/model/discipline_family_history.dart';
import 'package:eschool_api/src/model/discipline_family_history_response.dart';
import 'package:eschool_api/src/model/discipline_family_record.dart';
import 'package:eschool_api/src/model/discipline_framework.dart';
import 'package:eschool_api/src/model/discipline_framework_response.dart';
import 'package:eschool_api/src/model/discipline_framework_view.dart';
import 'package:eschool_api/src/model/discipline_framework_view_response.dart';
import 'package:eschool_api/src/model/discipline_framework_write.dart';
import 'package:eschool_api/src/model/discipline_hearing_write.dart';
import 'package:eschool_api/src/model/discipline_infraction.dart';
import 'package:eschool_api/src/model/discipline_learner_choice.dart';
import 'package:eschool_api/src/model/discipline_learner_choices.dart';
import 'package:eschool_api/src/model/discipline_learner_choices_response.dart';
import 'package:eschool_api/src/model/discipline_level.dart';
import 'package:eschool_api/src/model/discipline_optional_framework.dart';
import 'package:eschool_api/src/model/discipline_record_basis.dart';
import 'package:eschool_api/src/model/discipline_record_detail.dart';
import 'package:eschool_api/src/model/discipline_record_detail_response.dart';
import 'package:eschool_api/src/model/discipline_record_history.dart';
import 'package:eschool_api/src/model/discipline_record_history_response.dart';
import 'package:eschool_api/src/model/discipline_record_metadata.dart';
import 'package:eschool_api/src/model/discipline_record_metadata_response.dart';
import 'package:eschool_api/src/model/discipline_record_queue.dart';
import 'package:eschool_api/src/model/discipline_record_queue_response.dart';
import 'package:eschool_api/src/model/discipline_record_version.dart';
import 'package:eschool_api/src/model/discipline_record_write.dart';
import 'package:eschool_api/src/model/discipline_review_write.dart';
import 'package:eschool_api/src/model/discipline_sanction.dart';
import 'package:eschool_api/src/model/discipline_supervisors.dart';
import 'package:eschool_api/src/model/discipline_supervisors_response.dart';
import 'package:eschool_api/src/model/discipline_version_record.dart';
import 'package:eschool_api/src/model/enrolment.dart';
import 'package:eschool_api/src/model/enrolment_list_response.dart';
import 'package:eschool_api/src/model/enrolment_list_response_data.dart';
import 'package:eschool_api/src/model/enrolment_response.dart';
import 'package:eschool_api/src/model/error_response.dart';
import 'package:eschool_api/src/model/facilitator_features.dart';
import 'package:eschool_api/src/model/facilitator_policy.dart';
import 'package:eschool_api/src/model/facilitator_profile.dart';
import 'package:eschool_api/src/model/facilitator_school.dart';
import 'package:eschool_api/src/model/facilitator_session_response.dart';
import 'package:eschool_api/src/model/facilitator_sign_in_data.dart';
import 'package:eschool_api/src/model/facilitator_sign_in_input.dart';
import 'package:eschool_api/src/model/facilitator_sign_in_response.dart';
import 'package:eschool_api/src/model/facilitator_sign_out_response.dart';
import 'package:eschool_api/src/model/gallery_child.dart';
import 'package:eschool_api/src/model/gallery_configuration.dart';
import 'package:eschool_api/src/model/gallery_configuration_response.dart';
import 'package:eschool_api/src/model/gallery_configure.dart';
import 'package:eschool_api/src/model/gallery_decision.dart';
import 'package:eschool_api/src/model/gallery_family_page.dart';
import 'package:eschool_api/src/model/gallery_family_page_response.dart';
import 'package:eschool_api/src/model/gallery_group.dart';
import 'package:eschool_api/src/model/gallery_group_page.dart';
import 'package:eschool_api/src/model/gallery_group_page_response.dart';
import 'package:eschool_api/src/model/gallery_photo.dart';
import 'package:eschool_api/src/model/gallery_propose.dart';
import 'package:eschool_api/src/model/gallery_reference.dart';
import 'package:eschool_api/src/model/gallery_reference_response.dart';
import 'package:eschool_api/src/model/gallery_revision.dart';
import 'package:eschool_api/src/model/gallery_settings.dart';
import 'package:eschool_api/src/model/gallery_settings_response.dart';
import 'package:eschool_api/src/model/gallery_tagged_photo.dart';
import 'package:eschool_api/src/model/gallery_year.dart';
import 'package:eschool_api/src/model/get_anonymous_incident_follow_up_response.dart';
import 'package:eschool_api/src/model/get_consultation_access_response.dart';
import 'package:eschool_api/src/model/get_consultation_ballot_response.dart';
import 'package:eschool_api/src/model/get_consultation_configuration_response.dart';
import 'package:eschool_api/src/model/get_consultation_history_record_response.dart';
import 'package:eschool_api/src/model/get_consultation_results_response.dart';
import 'package:eschool_api/src/model/get_identified_incident_follow_up_response.dart';
import 'package:eschool_api/src/model/get_managed_consultation_results_response.dart';
import 'package:eschool_api/src/model/get_practice_access_response.dart';
import 'package:eschool_api/src/model/get_practice_bank_choices_response.dart';
import 'package:eschool_api/src/model/get_practice_bank_response.dart';
import 'package:eschool_api/src/model/get_practice_daily_set_response.dart';
import 'package:eschool_api/src/model/get_public_incident_configuration_response.dart';
import 'package:eschool_api/src/model/get_wellbeing_access_response.dart';
import 'package:eschool_api/src/model/get_wellbeing_case_response.dart';
import 'package:eschool_api/src/model/get_wellbeing_configuration_response.dart';
import 'package:eschool_api/src/model/get_wellbeing_report_response.dart';
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
import 'package:eschool_api/src/model/identify_incident_reporter_response.dart';
import 'package:eschool_api/src/model/incident_follow_up_input.dart';
import 'package:eschool_api/src/model/incident_identify_input.dart';
import 'package:eschool_api/src/model/incident_photo.dart';
import 'package:eschool_api/src/model/incident_photo_constraints.dart';
import 'package:eschool_api/src/model/incident_photo_read_input.dart';
import 'package:eschool_api/src/model/incident_public_configuration.dart';
import 'package:eschool_api/src/model/incident_public_configuration_categories_inner.dart';
import 'package:eschool_api/src/model/incident_public_thread.dart';
import 'package:eschool_api/src/model/incident_public_view.dart';
import 'package:eschool_api/src/model/incident_reply_input.dart';
import 'package:eschool_api/src/model/incident_reply_receipt.dart';
import 'package:eschool_api/src/model/incident_submission_input.dart';
import 'package:eschool_api/src/model/incident_submission_receipt.dart';
import 'package:eschool_api/src/model/intervention_create.dart';
import 'package:eschool_api/src/model/intervention_detail.dart';
import 'package:eschool_api/src/model/intervention_detail_response.dart';
import 'package:eschool_api/src/model/intervention_history.dart';
import 'package:eschool_api/src/model/intervention_history_response.dart';
import 'package:eschool_api/src/model/intervention_metadata.dart';
import 'package:eschool_api/src/model/intervention_metadata_response.dart';
import 'package:eschool_api/src/model/intervention_owner_write.dart';
import 'package:eschool_api/src/model/intervention_queue.dart';
import 'package:eschool_api/src/model/intervention_queue_item.dart';
import 'package:eschool_api/src/model/intervention_queue_response.dart';
import 'package:eschool_api/src/model/intervention_update.dart';
import 'package:eschool_api/src/model/intervention_version.dart';
import 'package:eschool_api/src/model/intervention_version_record.dart';
import 'package:eschool_api/src/model/issue_practice_daily_set_response.dart';
import 'package:eschool_api/src/model/learner_note_assignment.dart';
import 'package:eschool_api/src/model/learner_note_assignment_response.dart';
import 'package:eschool_api/src/model/learner_note_assignment_revoke.dart';
import 'package:eschool_api/src/model/learner_note_assignment_write.dart';
import 'package:eschool_api/src/model/learner_note_assignments.dart';
import 'package:eschool_api/src/model/learner_note_assignments_response.dart';
import 'package:eschool_api/src/model/learner_note_create.dart';
import 'package:eschool_api/src/model/learner_note_detail.dart';
import 'package:eschool_api/src/model/learner_note_detail_response.dart';
import 'package:eschool_api/src/model/learner_note_history.dart';
import 'package:eschool_api/src/model/learner_note_history_response.dart';
import 'package:eschool_api/src/model/learner_note_list.dart';
import 'package:eschool_api/src/model/learner_note_list_response.dart';
import 'package:eschool_api/src/model/learner_note_metadata.dart';
import 'package:eschool_api/src/model/learner_note_revision.dart';
import 'package:eschool_api/src/model/learner_note_update.dart';
import 'package:eschool_api/src/model/link_wellbeing_case_incident_response.dart';
import 'package:eschool_api/src/model/list_consultation_audience_choices_response.dart';
import 'package:eschool_api/src/model/list_consultation_history_response.dart';
import 'package:eschool_api/src/model/list_consultation_responses_response.dart';
import 'package:eschool_api/src/model/list_consultations_response.dart';
import 'package:eschool_api/src/model/list_managed_consultations_response.dart';
import 'package:eschool_api/src/model/list_staff_private_notices_response.dart';
import 'package:eschool_api/src/model/list_wellbeing_appointments_response.dart';
import 'package:eschool_api/src/model/list_wellbeing_case_assignments_response.dart';
import 'package:eschool_api/src/model/list_wellbeing_case_incident_choices_response.dart';
import 'package:eschool_api/src/model/list_wellbeing_case_professionals_response.dart';
import 'package:eschool_api/src/model/list_wellbeing_cases_response.dart';
import 'package:eschool_api/src/model/list_wellbeing_managed_cases_response.dart';
import 'package:eschool_api/src/model/list_wellbeing_notices_response.dart';
import 'package:eschool_api/src/model/list_wellbeing_reports_response.dart';
import 'package:eschool_api/src/model/list_wellbeing_team_assignments_response.dart';
import 'package:eschool_api/src/model/live_authorization.dart';
import 'package:eschool_api/src/model/live_authorization_response.dart';
import 'package:eschool_api/src/model/live_authorize_request.dart';
import 'package:eschool_api/src/model/live_presence.dart';
import 'package:eschool_api/src/model/live_presence_request.dart';
import 'package:eschool_api/src/model/live_presence_response.dart';
import 'package:eschool_api/src/model/live_session_configuration.dart';
import 'package:eschool_api/src/model/live_session_response.dart';
import 'package:eschool_api/src/model/mastery_configuration.dart';
import 'package:eschool_api/src/model/mastery_configuration_response.dart';
import 'package:eschool_api/src/model/mastery_graph.dart';
import 'package:eschool_api/src/model/mastery_graph_response.dart';
import 'package:eschool_api/src/model/mastery_graph_write.dart';
import 'package:eschool_api/src/model/mastery_group.dart';
import 'package:eschool_api/src/model/mastery_link.dart';
import 'package:eschool_api/src/model/mastery_node.dart';
import 'package:eschool_api/src/model/mastery_priority.dart';
import 'package:eschool_api/src/model/mastery_rebuild.dart';
import 'package:eschool_api/src/model/mastery_rebuild_response.dart';
import 'package:eschool_api/src/model/mastery_roster.dart';
import 'package:eschool_api/src/model/mastery_roster_learner.dart';
import 'package:eschool_api/src/model/mastery_roster_response.dart';
import 'package:eschool_api/src/model/mastery_settings.dart';
import 'package:eschool_api/src/model/mastery_subject_choice.dart';
import 'package:eschool_api/src/model/mastery_subject_list.dart';
import 'package:eschool_api/src/model/mastery_subject_list_response.dart';
import 'package:eschool_api/src/model/mastery_topic_view.dart';
import 'package:eschool_api/src/model/mastery_view.dart';
import 'package:eschool_api/src/model/mastery_view_response.dart';
import 'package:eschool_api/src/model/module.dart';
import 'package:eschool_api/src/model/my_school_info.dart';
import 'package:eschool_api/src/model/my_school_response.dart';
import 'package:eschool_api/src/model/notification_channel_preference.dart';
import 'package:eschool_api/src/model/notification_preferences_response.dart';
import 'package:eschool_api/src/model/notification_rule.dart';
import 'package:eschool_api/src/model/notification_type_preference.dart';
import 'package:eschool_api/src/model/notification_type_preference_channels.dart';
import 'package:eschool_api/src/model/open_wellbeing_case_response.dart';
import 'package:eschool_api/src/model/pagination.dart';
import 'package:eschool_api/src/model/practice_access.dart';
import 'package:eschool_api/src/model/practice_approval.dart';
import 'package:eschool_api/src/model/practice_approve_input.dart';
import 'package:eschool_api/src/model/practice_bank_choices.dart';
import 'package:eschool_api/src/model/practice_bank_item.dart';
import 'package:eschool_api/src/model/practice_bank_page.dart';
import 'package:eschool_api/src/model/practice_choice.dart';
import 'package:eschool_api/src/model/practice_daily_set.dart';
import 'package:eschool_api/src/model/practice_group_choice.dart';
import 'package:eschool_api/src/model/practice_issue_input.dart';
import 'package:eschool_api/src/model/practice_question.dart';
import 'package:eschool_api/src/model/practice_question_option.dart';
import 'package:eschool_api/src/model/practice_review.dart';
import 'package:eschool_api/src/model/practice_review_input.dart';
import 'package:eschool_api/src/model/practice_schedule.dart';
import 'package:eschool_api/src/model/practice_set_entry.dart';
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
import 'package:eschool_api/src/model/progress_band_settings.dart';
import 'package:eschool_api/src/model/progress_band_settings_response.dart';
import 'package:eschool_api/src/model/progress_band_settings_write.dart';
import 'package:eschool_api/src/model/progress_configuration.dart';
import 'package:eschool_api/src/model/progress_configuration_response.dart';
import 'package:eschool_api/src/model/progress_family_bands.dart';
import 'package:eschool_api/src/model/progress_family_bands_response.dart';
import 'package:eschool_api/src/model/progress_group.dart';
import 'package:eschool_api/src/model/progress_learner_list.dart';
import 'package:eschool_api/src/model/progress_learner_list_response.dart';
import 'package:eschool_api/src/model/progress_learner_option.dart';
import 'package:eschool_api/src/model/progress_option.dart';
import 'package:eschool_api/src/model/progress_released_band.dart';
import 'package:eschool_api/src/model/progress_roster.dart';
import 'package:eschool_api/src/model/progress_roster_response.dart';
import 'package:eschool_api/src/model/progress_staff_band.dart';
import 'package:eschool_api/src/model/progress_staff_band_response.dart';
import 'package:eschool_api/src/model/publish_consultation_response.dart';
import 'package:eschool_api/src/model/read_staff_private_notice_response.dart';
import 'package:eschool_api/src/model/read_wellbeing_notice_response.dart';
import 'package:eschool_api/src/model/recognition_access.dart';
import 'package:eschool_api/src/model/recognition_access_response.dart';
import 'package:eschool_api/src/model/recognition_award_category.dart';
import 'package:eschool_api/src/model/recognition_award_definition.dart';
import 'package:eschool_api/src/model/recognition_award_profile.dart';
import 'package:eschool_api/src/model/recognition_award_profile_response.dart';
import 'package:eschool_api/src/model/recognition_award_window.dart';
import 'package:eschool_api/src/model/recognition_catalogue_change.dart';
import 'package:eschool_api/src/model/recognition_configuration.dart';
import 'package:eschool_api/src/model/recognition_configuration_response.dart';
import 'package:eschool_api/src/model/recognition_configure.dart';
import 'package:eschool_api/src/model/recognition_correction.dart';
import 'package:eschool_api/src/model/recognition_house.dart';
import 'package:eschool_api/src/model/recognition_house_counter.dart';
import 'package:eschool_api/src/model/recognition_house_counters.dart';
import 'package:eschool_api/src/model/recognition_house_counters_response.dart';
import 'package:eschool_api/src/model/recognition_issue.dart';
import 'package:eschool_api/src/model/recognition_learner.dart';
import 'package:eschool_api/src/model/recognition_learner_page.dart';
import 'package:eschool_api/src/model/recognition_learner_page_response.dart';
import 'package:eschool_api/src/model/recognition_named_reference.dart';
import 'package:eschool_api/src/model/recognition_nominate.dart';
import 'package:eschool_api/src/model/recognition_nomination.dart';
import 'package:eschool_api/src/model/recognition_nomination_catalogue.dart';
import 'package:eschool_api/src/model/recognition_nomination_catalogue_response.dart';
import 'package:eschool_api/src/model/recognition_nomination_choice.dart';
import 'package:eschool_api/src/model/recognition_nomination_page.dart';
import 'package:eschool_api/src/model/recognition_nomination_page_response.dart';
import 'package:eschool_api/src/model/recognition_outside_award.dart';
import 'package:eschool_api/src/model/recognition_permanent_award.dart';
import 'package:eschool_api/src/model/recognition_point_category.dart';
import 'package:eschool_api/src/model/recognition_point_entry.dart';
import 'package:eschool_api/src/model/recognition_point_history.dart';
import 'package:eschool_api/src/model/recognition_point_history_response.dart';
import 'package:eschool_api/src/model/recognition_present.dart';
import 'package:eschool_api/src/model/recognition_reference.dart';
import 'package:eschool_api/src/model/recognition_reference_response.dart';
import 'package:eschool_api/src/model/recognition_review.dart';
import 'package:eschool_api/src/model/recognition_settings.dart';
import 'package:eschool_api/src/model/recognition_versioned_correction.dart';
import 'package:eschool_api/src/model/recognition_year.dart';
import 'package:eschool_api/src/model/reply_anonymous_incident_response.dart';
import 'package:eschool_api/src/model/reply_identified_incident_response.dart';
import 'package:eschool_api/src/model/reply_wellbeing_report_response.dart';
import 'package:eschool_api/src/model/review_practice_entry_response.dart';
import 'package:eschool_api/src/model/revise_practice_question_response.dart';
import 'package:eschool_api/src/model/risk_assignment.dart';
import 'package:eschool_api/src/model/risk_assignment_result.dart';
import 'package:eschool_api/src/model/risk_assignment_result_response.dart';
import 'package:eschool_api/src/model/risk_assignment_revoke.dart';
import 'package:eschool_api/src/model/risk_assignment_write.dart';
import 'package:eschool_api/src/model/risk_assignments.dart';
import 'package:eschool_api/src/model/risk_assignments_response.dart';
import 'package:eschool_api/src/model/risk_classroom.dart';
import 'package:eschool_api/src/model/risk_classroom_learner.dart';
import 'package:eschool_api/src/model/risk_classroom_response.dart';
import 'package:eschool_api/src/model/risk_classroom_signal.dart';
import 'package:eschool_api/src/model/risk_configuration.dart';
import 'package:eschool_api/src/model/risk_configuration_response.dart';
import 'package:eschool_api/src/model/risk_learner_choice.dart';
import 'package:eschool_api/src/model/risk_learner_choices.dart';
import 'package:eschool_api/src/model/risk_learner_choices_response.dart';
import 'package:eschool_api/src/model/risk_owner_choice.dart';
import 'package:eschool_api/src/model/risk_owner_choices.dart';
import 'package:eschool_api/src/model/risk_owner_choices_response.dart';
import 'package:eschool_api/src/model/risk_professional_choices.dart';
import 'package:eschool_api/src/model/risk_professional_choices_response.dart';
import 'package:eschool_api/src/model/risk_settings.dart';
import 'package:eschool_api/src/model/risk_settings_response.dart';
import 'package:eschool_api/src/model/risk_settings_write.dart';
import 'package:eschool_api/src/model/risk_signal.dart';
import 'package:eschool_api/src/model/risk_signal_learner.dart';
import 'package:eschool_api/src/model/risk_signals.dart';
import 'package:eschool_api/src/model/risk_signals_response.dart';
import 'package:eschool_api/src/model/risk_staff_choice.dart';
import 'package:eschool_api/src/model/save_wellbeing_appointment_response.dart';
import 'package:eschool_api/src/model/save_wellbeing_case_assignment_response.dart';
import 'package:eschool_api/src/model/save_wellbeing_category_response.dart';
import 'package:eschool_api/src/model/save_wellbeing_team_assignment_response.dart';
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
import 'package:eschool_api/src/model/set_wellbeing_case_status_response.dart';
import 'package:eschool_api/src/model/staff_private_notice.dart';
import 'package:eschool_api/src/model/staff_private_notice_page.dart';
import 'package:eschool_api/src/model/staff_private_notice_read.dart';
import 'package:eschool_api/src/model/subject_standard.dart';
import 'package:eschool_api/src/model/subject_standards_response.dart';
import 'package:eschool_api/src/model/subject_standards_response_data.dart';
import 'package:eschool_api/src/model/submit_anonymous_incident_response.dart';
import 'package:eschool_api/src/model/submit_identified_incident_response.dart';
import 'package:eschool_api/src/model/support_access.dart';
import 'package:eschool_api/src/model/support_access_response.dart';
import 'package:eschool_api/src/model/support_assign_input.dart';
import 'package:eschool_api/src/model/support_assignment.dart';
import 'package:eschool_api/src/model/support_assignments.dart';
import 'package:eschool_api/src/model/support_assignments_response.dart';
import 'package:eschool_api/src/model/support_case_summary.dart';
import 'package:eschool_api/src/model/support_cases.dart';
import 'package:eschool_api/src/model/support_cases_response.dart';
import 'package:eschool_api/src/model/support_configuration.dart';
import 'package:eschool_api/src/model/support_configuration_response.dart';
import 'package:eschool_api/src/model/support_disclose_input.dart';
import 'package:eschool_api/src/model/support_disclosure_history.dart';
import 'package:eschool_api/src/model/support_disclosure_summary.dart';
import 'package:eschool_api/src/model/support_disclosures.dart';
import 'package:eschool_api/src/model/support_disclosures_response.dart';
import 'package:eschool_api/src/model/support_named_choice.dart';
import 'package:eschool_api/src/model/support_plan.dart';
import 'package:eschool_api/src/model/support_plan_contents.dart';
import 'package:eschool_api/src/model/support_plan_response.dart';
import 'package:eschool_api/src/model/support_plan_snapshot.dart';
import 'package:eschool_api/src/model/support_reason_input.dart';
import 'package:eschool_api/src/model/support_review.dart';
import 'package:eschool_api/src/model/support_review_input.dart';
import 'package:eschool_api/src/model/support_reviews.dart';
import 'package:eschool_api/src/model/support_reviews_response.dart';
import 'package:eschool_api/src/model/support_save_input.dart';
import 'package:eschool_api/src/model/support_teacher_disclosure.dart';
import 'package:eschool_api/src/model/support_teacher_disclosure_response.dart';
import 'package:eschool_api/src/model/support_teacher_list.dart';
import 'package:eschool_api/src/model/support_teacher_list_response.dart';
import 'package:eschool_api/src/model/support_version.dart';
import 'package:eschool_api/src/model/support_versions.dart';
import 'package:eschool_api/src/model/support_versions_response.dart';
import 'package:eschool_api/src/model/support_write_receipt.dart';
import 'package:eschool_api/src/model/support_write_receipt_response.dart';
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
import 'package:eschool_api/src/model/transfer_accept_input.dart';
import 'package:eschool_api/src/model/transfer_access.dart';
import 'package:eschool_api/src/model/transfer_access_response.dart';
import 'package:eschool_api/src/model/transfer_attendance.dart';
import 'package:eschool_api/src/model/transfer_choice.dart';
import 'package:eschool_api/src/model/transfer_choice_page.dart';
import 'package:eschool_api/src/model/transfer_choice_page_response.dart';
import 'package:eschool_api/src/model/transfer_consent_input.dart';
import 'package:eschool_api/src/model/transfer_detail.dart';
import 'package:eschool_api/src/model/transfer_detail_response.dart';
import 'package:eschool_api/src/model/transfer_document.dart';
import 'package:eschool_api/src/model/transfer_gallery_choice.dart';
import 'package:eschool_api/src/model/transfer_mark.dart';
import 'package:eschool_api/src/model/transfer_page.dart';
import 'package:eschool_api/src/model/transfer_page_response.dart';
import 'package:eschool_api/src/model/transfer_prepare_input.dart';
import 'package:eschool_api/src/model/transfer_report.dart';
import 'package:eschool_api/src/model/transfer_revision_input.dart';
import 'package:eschool_api/src/model/transfer_summary.dart';
import 'package:eschool_api/src/model/transfer_write_receipt.dart';
import 'package:eschool_api/src/model/transfer_write_receipt_response.dart';
import 'package:eschool_api/src/model/update_consultation_response.dart';
import 'package:eschool_api/src/model/update_my_notification_preferences_request.dart';
import 'package:eschool_api/src/model/update_my_profile_request.dart';
import 'package:eschool_api/src/model/update_person_profile_request.dart';
import 'package:eschool_api/src/model/update_profile_category_request.dart';
import 'package:eschool_api/src/model/update_profile_field_request.dart';
import 'package:eschool_api/src/model/upload_anonymous_incident_photo_response.dart';
import 'package:eschool_api/src/model/upload_identified_incident_photo_response.dart';
import 'package:eschool_api/src/model/validation_errors.dart';
import 'package:eschool_api/src/model/weekly_digest_access.dart';
import 'package:eschool_api/src/model/weekly_digest_access_response.dart';
import 'package:eschool_api/src/model/weekly_digest_action_templates.dart';
import 'package:eschool_api/src/model/weekly_digest_attendance.dart';
import 'package:eschool_api/src/model/weekly_digest_child.dart';
import 'package:eschool_api/src/model/weekly_digest_detail.dart';
import 'package:eschool_api/src/model/weekly_digest_detail_response.dart';
import 'package:eschool_api/src/model/weekly_digest_event.dart';
import 'package:eschool_api/src/model/weekly_digest_evidence.dart';
import 'package:eschool_api/src/model/weekly_digest_home_action.dart';
import 'package:eschool_api/src/model/weekly_digest_list_item.dart';
import 'package:eschool_api/src/model/weekly_digest_mark.dart';
import 'package:eschool_api/src/model/weekly_digest_page.dart';
import 'package:eschool_api/src/model/weekly_digest_page_response.dart';
import 'package:eschool_api/src/model/weekly_digest_practice.dart';
import 'package:eschool_api/src/model/weekly_digest_preference_input.dart';
import 'package:eschool_api/src/model/weekly_digest_reference.dart';
import 'package:eschool_api/src/model/weekly_digest_reference_response.dart';
import 'package:eschool_api/src/model/weekly_digest_starting_template.dart';
import 'package:eschool_api/src/model/weekly_digest_template.dart';
import 'package:eschool_api/src/model/weekly_digest_template_input.dart';
import 'package:eschool_api/src/model/weekly_digest_template_page.dart';
import 'package:eschool_api/src/model/weekly_digest_template_page_response.dart';
import 'package:eschool_api/src/model/weekly_digest_template_review.dart';
import 'package:eschool_api/src/model/wellbeing_access.dart';
import 'package:eschool_api/src/model/wellbeing_appointment.dart';
import 'package:eschool_api/src/model/wellbeing_appointment_input.dart';
import 'package:eschool_api/src/model/wellbeing_assignment.dart';
import 'package:eschool_api/src/model/wellbeing_assignment_input.dart';
import 'package:eschool_api/src/model/wellbeing_assignment_page.dart';
import 'package:eschool_api/src/model/wellbeing_case.dart';
import 'package:eschool_api/src/model/wellbeing_case_assignment_receipt.dart';
import 'package:eschool_api/src/model/wellbeing_case_choice_page.dart';
import 'package:eschool_api/src/model/wellbeing_case_entry.dart';
import 'package:eschool_api/src/model/wellbeing_case_entry_input.dart';
import 'package:eschool_api/src/model/wellbeing_case_entry_receipt.dart';
import 'package:eschool_api/src/model/wellbeing_case_entry_receipt_entry.dart';
import 'package:eschool_api/src/model/wellbeing_case_link_input.dart';
import 'package:eschool_api/src/model/wellbeing_case_link_receipt.dart';
import 'package:eschool_api/src/model/wellbeing_case_open_input.dart';
import 'package:eschool_api/src/model/wellbeing_case_open_receipt.dart';
import 'package:eschool_api/src/model/wellbeing_case_page.dart';
import 'package:eschool_api/src/model/wellbeing_case_status_input.dart';
import 'package:eschool_api/src/model/wellbeing_case_status_receipt.dart';
import 'package:eschool_api/src/model/wellbeing_case_summary.dart';
import 'package:eschool_api/src/model/wellbeing_category.dart';
import 'package:eschool_api/src/model/wellbeing_category_input.dart';
import 'package:eschool_api/src/model/wellbeing_configuration.dart';
import 'package:eschool_api/src/model/wellbeing_learner_choice.dart';
import 'package:eschool_api/src/model/wellbeing_named_choice.dart';
import 'package:eschool_api/src/model/wellbeing_notice.dart';
import 'package:eschool_api/src/model/wellbeing_notice_page.dart';
import 'package:eschool_api/src/model/wellbeing_notice_read_receipt.dart';
import 'package:eschool_api/src/model/wellbeing_report.dart';
import 'package:eschool_api/src/model/wellbeing_report_page.dart';
import 'package:eschool_api/src/model/wellbeing_report_reply_input.dart';
import 'package:eschool_api/src/model/wellbeing_report_reply_receipt.dart';
import 'package:eschool_api/src/model/wellbeing_report_summary.dart';
import 'package:eschool_api/src/model/wellbeing_report_thread.dart';
import 'package:eschool_api/src/model/wellbeing_team_receipt.dart';

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
    case 'AcademicExitDocumentChoice':
      return AcademicExitDocumentChoice.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'AcademicExitDocumentEvidence':
      return AcademicExitDocumentEvidence.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'AcademicExitIssue':
      return AcademicExitIssue.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'AcademicExitPackage':
      return AcademicExitPackage.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'AcademicExitPackageResponse':
      return AcademicExitPackageResponse.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'AcademicExitPrepare':
      return AcademicExitPrepare.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'AcademicExitPreview':
      return AcademicExitPreview.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'AcademicExitPreviewResponse':
      return AcademicExitPreviewResponse.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'AcademicExitReportChoice':
      return AcademicExitReportChoice.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'AcademicExitReportEvidence':
      return AcademicExitReportEvidence.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'AcademicExitSelection':
      return AcademicExitSelection.fromJson(value as Map<String, dynamic>)
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
    case 'AcademicReportAccessData':
      return AcademicReportAccessData.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'AcademicReportAccessResponse':
      return AcademicReportAccessResponse.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'AcademicReportActionWrite':
      return AcademicReportActionWrite.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'AcademicReportAttendance':
      return AcademicReportAttendance.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'AcademicReportAttendanceDay':
      return AcademicReportAttendanceDay.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'AcademicReportBankSavedResponse':
      return AcademicReportBankSavedResponse.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'AcademicReportBatchData':
      return AcademicReportBatchData.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'AcademicReportBatchResponse':
      return AcademicReportBatchResponse.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'AcademicReportBatchSummary':
      return AcademicReportBatchSummary.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'AcademicReportBatchesData':
      return AcademicReportBatchesData.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'AcademicReportBatchesResponse':
      return AcademicReportBatchesResponse.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'AcademicReportCard':
      return AcademicReportCard.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'AcademicReportCommentBank':
      return AcademicReportCommentBank.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'AcademicReportCommentBankWrite':
      return AcademicReportCommentBankWrite.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'AcademicReportCommentData':
      return AcademicReportCommentData.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'AcademicReportCommentResponse':
      return AcademicReportCommentResponse.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'AcademicReportCommentRow':
      return AcademicReportCommentRow.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'AcademicReportCommentWrite':
      return AcademicReportCommentWrite.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'AcademicReportCommentsData':
      return AcademicReportCommentsData.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'AcademicReportCommentsResponse':
      return AcademicReportCommentsResponse.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'AcademicReportConfigurationData':
      return AcademicReportConfigurationData.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'AcademicReportConfigurationResponse':
      return AcademicReportConfigurationResponse.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'AcademicReportDesign':
      return AcademicReportDesign.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'AcademicReportDesignSnapshot':
      return AcademicReportDesignSnapshot.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'AcademicReportDetailCard':
      return AcademicReportDetailCard.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'AcademicReportDetailData':
      return AcademicReportDetailData.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'AcademicReportDetailResponse':
      return AcademicReportDetailResponse.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'AcademicReportEnrolment':
      return AcademicReportEnrolment.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'AcademicReportFamilyCard':
      return AcademicReportFamilyCard.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'AcademicReportFamilyData':
      return AcademicReportFamilyData.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'AcademicReportFamilyResponse':
      return AcademicReportFamilyResponse.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'AcademicReportHomeroom':
      return AcademicReportHomeroom.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'AcademicReportPayload':
      return AcademicReportPayload.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'AcademicReportPrepareWrite':
      return AcademicReportPrepareWrite.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'AcademicReportPreviewData':
      return AcademicReportPreviewData.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'AcademicReportPreviewResponse':
      return AcademicReportPreviewResponse.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'AcademicReportReadData':
      return AcademicReportReadData.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'AcademicReportReadResponse':
      return AcademicReportReadResponse.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'AcademicReportReleasePreviewData':
      return AcademicReportReleasePreviewData.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'AcademicReportReleasePreviewResponse':
      return AcademicReportReleasePreviewResponse.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'AcademicReportRosterData':
      return AcademicReportRosterData.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'AcademicReportRosterResponse':
      return AcademicReportRosterResponse.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'AcademicReportSavedRevision':
      return AcademicReportSavedRevision.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'AcademicReportSelection':
      return AcademicReportSelection.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'AcademicReportSignature':
      return AcademicReportSignature.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'AcademicReportSignatures':
      return AcademicReportSignatures.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'AcademicReportSourceData':
      return AcademicReportSourceData.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'AcademicReportSourceResponse':
      return AcademicReportSourceResponse.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'AcademicReportStoredComment':
      return AcademicReportStoredComment.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'AcademicReportSubjectResult':
      return AcademicReportSubjectResult.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'AcademicReportTemplate':
      return AcademicReportTemplate.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'AcademicReportTemplateResponse':
      return AcademicReportTemplateResponse.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'AcademicReportTemplateWrite':
      return AcademicReportTemplateWrite.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'AcademicReportTerm':
      return AcademicReportTerm.fromJson(value as Map<String, dynamic>)
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
    case 'AcademicTransitionAssignment':
      return AcademicTransitionAssignment.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'AcademicTransitionCommit':
      return AcademicTransitionCommit.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'AcademicTransitionConfiguration':
      return AcademicTransitionConfiguration.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'AcademicTransitionConfigurationResponse':
      return AcademicTransitionConfigurationResponse.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'AcademicTransitionEnrolment':
      return AcademicTransitionEnrolment.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'AcademicTransitionFact':
      return AcademicTransitionFact.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'AcademicTransitionGrade':
      return AcademicTransitionGrade.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'AcademicTransitionGradePolicy':
      return AcademicTransitionGradePolicy.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'AcademicTransitionGradePolicyResponse':
      return AcademicTransitionGradePolicyResponse.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'AcademicTransitionGroup':
      return AcademicTransitionGroup.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'AcademicTransitionHomeroom':
      return AcademicTransitionHomeroom.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'AcademicTransitionInput':
      return AcademicTransitionInput.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'AcademicTransitionLearner':
      return AcademicTransitionLearner.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'AcademicTransitionLearnerChoice':
      return AcademicTransitionLearnerChoice.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'AcademicTransitionLearnerResponse':
      return AcademicTransitionLearnerResponse.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'AcademicTransitionMembership':
      return AcademicTransitionMembership.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'AcademicTransitionPolicyWrite':
      return AcademicTransitionPolicyWrite.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'AcademicTransitionPreview':
      return AcademicTransitionPreview.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'AcademicTransitionPreviewResponse':
      return AcademicTransitionPreviewResponse.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'AcademicTransitionRecord':
      return AcademicTransitionRecord.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'AcademicTransitionRecordResponse':
      return AcademicTransitionRecordResponse.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'AcademicTransitionResult':
      return AcademicTransitionResult.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'AcademicTransitionRetention':
      return AcademicTransitionRetention.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'AcademicTransitionRetentionResponse':
      return AcademicTransitionRetentionResponse.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'AcademicTransitionRetentionWrite':
      return AcademicTransitionRetentionWrite.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'AcademicTransitionSearch':
      return AcademicTransitionSearch.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'AcademicTransitionSearchResponse':
      return AcademicTransitionSearchResponse.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'AcademicTransitionState':
      return AcademicTransitionState.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'AcademicTransitionYear':
      return AcademicTransitionYear.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'AcceptGuardianInvitationRequest':
      return AcceptGuardianInvitationRequest.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'ActivityAttendance':
      return ActivityAttendance.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'ActivityAttendanceEntryWrite':
      return ActivityAttendanceEntryWrite.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'ActivityAttendanceReceipt':
      return ActivityAttendanceReceipt.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'ActivityAttendanceReceiptResponse':
      return ActivityAttendanceReceiptResponse.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'ActivityAttendanceWrite':
      return ActivityAttendanceWrite.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'ActivityGroup':
      return ActivityGroup.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'ActivityGroupDetail':
      return ActivityGroupDetail.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'ActivityGroupDetailResponse':
      return ActivityGroupDetailResponse.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'ActivityGroupList':
      return ActivityGroupList.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'ActivityGroupListResponse':
      return ActivityGroupListResponse.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'ActivityGroupResponse':
      return ActivityGroupResponse.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'ActivityGroupRoster':
      return ActivityGroupRoster.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'ActivityGroupRosterResponse':
      return ActivityGroupRosterResponse.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'ActivityGroupStaff':
      return ActivityGroupStaff.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'ActivityGroupStaffResponse':
      return ActivityGroupStaffResponse.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'ActivityGroupWrite':
      return ActivityGroupWrite.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'ActivityMaterial':
      return ActivityMaterial.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'ActivityMaterialResponse':
      return ActivityMaterialResponse.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'ActivityMaterialView':
      return ActivityMaterialView.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'ActivityMaterialWrite':
      return ActivityMaterialWrite.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'ActivityMembership':
      return ActivityMembership.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'ActivityMembershipResponse':
      return ActivityMembershipResponse.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'ActivityMembershipWrite':
      return ActivityMembershipWrite.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'ActivityRosterMember':
      return ActivityRosterMember.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'ActivitySession':
      return ActivitySession.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'ActivitySessionResponse':
      return ActivitySessionResponse.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'ActivitySessionRoster':
      return ActivitySessionRoster.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'ActivitySessionRosterLearner':
      return ActivitySessionRosterLearner.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'ActivitySessionWrite':
      return ActivitySessionWrite.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'ActivityStaffWrite':
      return ActivityStaffWrite.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'AddWellbeingCaseEntryResponse':
      return AddWellbeingCaseEntryResponse.fromJson(
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
    case 'ApprovePracticeQuestionResponse':
      return ApprovePracticeQuestionResponse.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'Branding':
      return Branding.fromJson(value as Map<String, dynamic>) as ReturnType;
    case 'CalendarRemovedResponse':
      return CalendarRemovedResponse.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'CancelConsultationResponse':
      return CancelConsultationResponse.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'CastConsultationVoteResponse':
      return CastConsultationVoteResponse.fromJson(
            value as Map<String, dynamic>,
          )
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
    case 'CloseConsultationResponse':
      return CloseConsultationResponse.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'CommunicationAudience':
      return CommunicationAudience.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'CommunicationAudienceList':
      return CommunicationAudienceList.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'CommunicationAudienceListResponse':
      return CommunicationAudienceListResponse.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'CommunicationAudienceReceipt':
      return CommunicationAudienceReceipt.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'CommunicationAudienceReceiptResponse':
      return CommunicationAudienceReceiptResponse.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'CommunicationAudienceSelection':
      return CommunicationAudienceSelection.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'CommunicationAudienceSnapshot':
      return CommunicationAudienceSnapshot.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'CommunicationAudienceWrite':
      return CommunicationAudienceWrite.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'CommunicationConfiguration':
      return CommunicationConfiguration.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'CommunicationConfigurationResponse':
      return CommunicationConfigurationResponse.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'CommunicationConfigurationYearsInner':
      return CommunicationConfigurationYearsInner.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'CommunicationContactList':
      return CommunicationContactList.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'CommunicationContactListResponse':
      return CommunicationContactListResponse.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'CommunicationDecisionWrite':
      return CommunicationDecisionWrite.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'CommunicationEffectiveRules':
      return CommunicationEffectiveRules.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'CommunicationMessageEditor':
      return CommunicationMessageEditor.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'CommunicationMessageEditorResponse':
      return CommunicationMessageEditorResponse.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'CommunicationMessageList':
      return CommunicationMessageList.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'CommunicationMessageListResponse':
      return CommunicationMessageListResponse.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'CommunicationMessagePreview':
      return CommunicationMessagePreview.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'CommunicationMessagePreviewResponse':
      return CommunicationMessagePreviewResponse.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'CommunicationMessageReader':
      return CommunicationMessageReader.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'CommunicationMessageReaderResponse':
      return CommunicationMessageReaderResponse.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'CommunicationMessageReceipt':
      return CommunicationMessageReceipt.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'CommunicationMessageReceiptResponse':
      return CommunicationMessageReceiptResponse.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'CommunicationMessageWrite':
      return CommunicationMessageWrite.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'CommunicationReadReceipt':
      return CommunicationReadReceipt.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'CommunicationReadReceiptResponse':
      return CommunicationReadReceiptResponse.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'CommunicationRoleRule':
      return CommunicationRoleRule.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'CommunicationRoleRuleResponse':
      return CommunicationRoleRuleResponse.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'CommunicationRuleList':
      return CommunicationRuleList.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'CommunicationRuleListResponse':
      return CommunicationRuleListResponse.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'CommunicationRuleWrite':
      return CommunicationRuleWrite.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'CommunicationScope':
      return CommunicationScope.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'CommunicationSubmitWrite':
      return CommunicationSubmitWrite.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'CommunicationSummary':
      return CommunicationSummary.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'CommunicationWithdrawWrite':
      return CommunicationWithdrawWrite.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'ConsultationAccess':
      return ConsultationAccess.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'ConsultationAudience':
      return ConsultationAudience.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'ConsultationAudienceChoicePage':
      return ConsultationAudienceChoicePage.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'ConsultationAudienceInput':
      return ConsultationAudienceInput.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'ConsultationBallot':
      return ConsultationBallot.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'ConsultationChoice':
      return ConsultationChoice.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'ConsultationConfiguration':
      return ConsultationConfiguration.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'ConsultationConfigurationInput':
      return ConsultationConfigurationInput.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'ConsultationConfigurationSnapshot':
      return ConsultationConfigurationSnapshot.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'ConsultationEndInput':
      return ConsultationEndInput.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'ConsultationHistoryEntry':
      return ConsultationHistoryEntry.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'ConsultationHistoryPage':
      return ConsultationHistoryPage.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'ConsultationHistoryRecord':
      return ConsultationHistoryRecord.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'ConsultationOption':
      return ConsultationOption.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'ConsultationOptionCount':
      return ConsultationOptionCount.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'ConsultationOptionInput':
      return ConsultationOptionInput.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'ConsultationPage':
      return ConsultationPage.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'ConsultationQuestion':
      return ConsultationQuestion.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'ConsultationQuestionInput':
      return ConsultationQuestionInput.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'ConsultationReceipt':
      return ConsultationReceipt.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'ConsultationResponseAnswer':
      return ConsultationResponseAnswer.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'ConsultationResponseEntry':
      return ConsultationResponseEntry.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'ConsultationResponsePage':
      return ConsultationResponsePage.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'ConsultationResultQuestion':
      return ConsultationResultQuestion.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'ConsultationResults':
      return ConsultationResults.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'ConsultationRevisionInput':
      return ConsultationRevisionInput.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'ConsultationSummary':
      return ConsultationSummary.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'ConsultationVoteAnswer':
      return ConsultationVoteAnswer.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'ConsultationVoteInput':
      return ConsultationVoteInput.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'ConsultationVoteReceipt':
      return ConsultationVoteReceipt.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'ContentArchiveWrite':
      return ContentArchiveWrite.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'ContentConfiguration':
      return ContentConfiguration.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'ContentConfigurationResponse':
      return ContentConfigurationResponse.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'ContentDraftWrite':
      return ContentDraftWrite.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'ContentGroup':
      return ContentGroup.fromJson(value as Map<String, dynamic>) as ReturnType;
    case 'ContentHistory':
      return ContentHistory.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'ContentItem':
      return ContentItem.fromJson(value as Map<String, dynamic>) as ReturnType;
    case 'ContentItemResponse':
      return ContentItemResponse.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'ContentLibrary':
      return ContentLibrary.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'ContentLibraryItem':
      return ContentLibraryItem.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'ContentLibraryResponse':
      return ContentLibraryResponse.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'ContentList':
      return ContentList.fromJson(value as Map<String, dynamic>) as ReturnType;
    case 'ContentListItem':
      return ContentListItem.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'ContentListResponse':
      return ContentListResponse.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'ContentPreview':
      return ContentPreview.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'ContentPreviewResponse':
      return ContentPreviewResponse.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'ContentPublishWrite':
      return ContentPublishWrite.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'ContentReadReceipt':
      return ContentReadReceipt.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'ContentReadReceiptResponse':
      return ContentReadReceiptResponse.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'ContentReader':
      return ContentReader.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'ContentReaderResponse':
      return ContentReaderResponse.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'ContentReceipt':
      return ContentReceipt.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'ContentReceiptResponse':
      return ContentReceiptResponse.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'ContentRevisionWrite':
      return ContentRevisionWrite.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'ContentSavedAccess':
      return ContentSavedAccess.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'ContentSavedAccessResponse':
      return ContentSavedAccessResponse.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'ContentSavedAccessWrite':
      return ContentSavedAccessWrite.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'ContentSelection':
      return ContentSelection.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'ContentSettings':
      return ContentSettings.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'ContentSettingsResponse':
      return ContentSettingsResponse.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'ContentSource':
      return ContentSource.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'ContentTopic':
      return ContentTopic.fromJson(value as Map<String, dynamic>) as ReturnType;
    case 'ContentTopicChoice':
      return ContentTopicChoice.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'ContentVersion':
      return ContentVersion.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'ContentVersionResponse':
      return ContentVersionResponse.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'ContentYear':
      return ContentYear.fromJson(value as Map<String, dynamic>) as ReturnType;
    case 'Context':
      return Context.fromJson(value as Map<String, dynamic>) as ReturnType;
    case 'ContextResponse':
      return ContextResponse.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'ContextUser':
      return ContextUser.fromJson(value as Map<String, dynamic>) as ReturnType;
    case 'CoverAbsence':
      return CoverAbsence.fromJson(value as Map<String, dynamic>) as ReturnType;
    case 'CoverAbsenceResponse':
      return CoverAbsenceResponse.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'CoverAbsenceSummary':
      return CoverAbsenceSummary.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'CoverAssignmentInput':
      return CoverAssignmentInput.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'CoverCandidates':
      return CoverCandidates.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'CoverCandidatesResponse':
      return CoverCandidatesResponse.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'CoverChangeInput':
      return CoverChangeInput.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'CoverConfiguration':
      return CoverConfiguration.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'CoverConfigurationResponse':
      return CoverConfigurationResponse.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'CoverCreateInput':
      return CoverCreateInput.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'CoverMine':
      return CoverMine.fromJson(value as Map<String, dynamic>) as ReturnType;
    case 'CoverMineResponse':
      return CoverMineResponse.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'CoverOccurrence':
      return CoverOccurrence.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'CoverOccurrenceResponse':
      return CoverOccurrenceResponse.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'CoverSource':
      return CoverSource.fromJson(value as Map<String, dynamic>) as ReturnType;
    case 'CoverTeacher':
      return CoverTeacher.fromJson(value as Map<String, dynamic>) as ReturnType;
    case 'CreateConsultationResponse':
      return CreateConsultationResponse.fromJson(value as Map<String, dynamic>)
          as ReturnType;
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
    case 'CreateWellbeingAppointmentResponse':
      return CreateWellbeingAppointmentResponse.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'CreateWellbeingCaseAssignmentResponse':
      return CreateWellbeingCaseAssignmentResponse.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'CreateWellbeingCategoryResponse':
      return CreateWellbeingCategoryResponse.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'CreateWellbeingTeamAssignmentResponse':
      return CreateWellbeingTeamAssignmentResponse.fromJson(
            value as Map<String, dynamic>,
          )
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
    case 'DisciplineAcknowledgeWrite':
      return DisciplineAcknowledgeWrite.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'DisciplineAcknowledgement':
      return DisciplineAcknowledgement.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'DisciplineAcknowledgementResponse':
      return DisciplineAcknowledgementResponse.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'DisciplineChoice':
      return DisciplineChoice.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'DisciplineConfiguration':
      return DisciplineConfiguration.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'DisciplineConfigurationResponse':
      return DisciplineConfigurationResponse.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'DisciplineDefinition':
      return DisciplineDefinition.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'DisciplineDetention':
      return DisciplineDetention.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'DisciplineDetentionAttendance':
      return DisciplineDetentionAttendance.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'DisciplineDetentionAttendanceResponse':
      return DisciplineDetentionAttendanceResponse.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'DisciplineDetentionAttendanceWrite':
      return DisciplineDetentionAttendanceWrite.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'DisciplineDetentionCancelWrite':
      return DisciplineDetentionCancelWrite.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'DisciplineDetentionDetail':
      return DisciplineDetentionDetail.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'DisciplineDetentionDetailResponse':
      return DisciplineDetentionDetailResponse.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'DisciplineDetentionEnrollWrite':
      return DisciplineDetentionEnrollWrite.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'DisciplineDetentionEnrollment':
      return DisciplineDetentionEnrollment.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'DisciplineDetentionEnrollmentResponse':
      return DisciplineDetentionEnrollmentResponse.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'DisciplineDetentionEntry':
      return DisciplineDetentionEntry.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'DisciplineDetentionResponse':
      return DisciplineDetentionResponse.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'DisciplineDetentionWrite':
      return DisciplineDetentionWrite.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'DisciplineDetentions':
      return DisciplineDetentions.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'DisciplineDetentionsResponse':
      return DisciplineDetentionsResponse.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'DisciplineFamilyDetention':
      return DisciplineFamilyDetention.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'DisciplineFamilyHistory':
      return DisciplineFamilyHistory.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'DisciplineFamilyHistoryResponse':
      return DisciplineFamilyHistoryResponse.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'DisciplineFamilyRecord':
      return DisciplineFamilyRecord.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'DisciplineFramework':
      return DisciplineFramework.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'DisciplineFrameworkResponse':
      return DisciplineFrameworkResponse.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'DisciplineFrameworkView':
      return DisciplineFrameworkView.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'DisciplineFrameworkViewResponse':
      return DisciplineFrameworkViewResponse.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'DisciplineFrameworkWrite':
      return DisciplineFrameworkWrite.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'DisciplineHearingWrite':
      return DisciplineHearingWrite.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'DisciplineInfraction':
      return DisciplineInfraction.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'DisciplineLearnerChoice':
      return DisciplineLearnerChoice.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'DisciplineLearnerChoices':
      return DisciplineLearnerChoices.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'DisciplineLearnerChoicesResponse':
      return DisciplineLearnerChoicesResponse.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'DisciplineLevel':
      return DisciplineLevel.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'DisciplineOptionalFramework':
      return DisciplineOptionalFramework.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'DisciplineRecordBasis':
      return DisciplineRecordBasis.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'DisciplineRecordDetail':
      return DisciplineRecordDetail.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'DisciplineRecordDetailResponse':
      return DisciplineRecordDetailResponse.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'DisciplineRecordHistory':
      return DisciplineRecordHistory.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'DisciplineRecordHistoryResponse':
      return DisciplineRecordHistoryResponse.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'DisciplineRecordMetadata':
      return DisciplineRecordMetadata.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'DisciplineRecordMetadataResponse':
      return DisciplineRecordMetadataResponse.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'DisciplineRecordQueue':
      return DisciplineRecordQueue.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'DisciplineRecordQueueResponse':
      return DisciplineRecordQueueResponse.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'DisciplineRecordVersion':
      return DisciplineRecordVersion.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'DisciplineRecordWrite':
      return DisciplineRecordWrite.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'DisciplineReviewWrite':
      return DisciplineReviewWrite.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'DisciplineSanction':
      return DisciplineSanction.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'DisciplineSupervisors':
      return DisciplineSupervisors.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'DisciplineSupervisorsResponse':
      return DisciplineSupervisorsResponse.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'DisciplineVersionRecord':
      return DisciplineVersionRecord.fromJson(value as Map<String, dynamic>)
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
    case 'FacilitatorFeatures':
      return FacilitatorFeatures.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'FacilitatorPolicy':
      return FacilitatorPolicy.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'FacilitatorProfile':
      return FacilitatorProfile.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'FacilitatorSchool':
      return FacilitatorSchool.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'FacilitatorSessionResponse':
      return FacilitatorSessionResponse.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'FacilitatorSignInData':
      return FacilitatorSignInData.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'FacilitatorSignInInput':
      return FacilitatorSignInInput.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'FacilitatorSignInResponse':
      return FacilitatorSignInResponse.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'FacilitatorSignOutResponse':
      return FacilitatorSignOutResponse.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'GalleryChild':
      return GalleryChild.fromJson(value as Map<String, dynamic>) as ReturnType;
    case 'GalleryConfiguration':
      return GalleryConfiguration.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'GalleryConfigurationResponse':
      return GalleryConfigurationResponse.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'GalleryConfigure':
      return GalleryConfigure.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'GalleryDecision':
      return GalleryDecision.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'GalleryFamilyPage':
      return GalleryFamilyPage.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'GalleryFamilyPageResponse':
      return GalleryFamilyPageResponse.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'GalleryGroup':
      return GalleryGroup.fromJson(value as Map<String, dynamic>) as ReturnType;
    case 'GalleryGroupPage':
      return GalleryGroupPage.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'GalleryGroupPageResponse':
      return GalleryGroupPageResponse.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'GalleryPhoto':
      return GalleryPhoto.fromJson(value as Map<String, dynamic>) as ReturnType;
    case 'GalleryPropose':
      return GalleryPropose.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'GalleryReference':
      return GalleryReference.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'GalleryReferenceResponse':
      return GalleryReferenceResponse.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'GalleryRevision':
      return GalleryRevision.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'GallerySettings':
      return GallerySettings.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'GallerySettingsResponse':
      return GallerySettingsResponse.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'GalleryTaggedPhoto':
      return GalleryTaggedPhoto.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'GalleryYear':
      return GalleryYear.fromJson(value as Map<String, dynamic>) as ReturnType;
    case 'GetAnonymousIncidentFollowUpResponse':
      return GetAnonymousIncidentFollowUpResponse.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'GetConsultationAccessResponse':
      return GetConsultationAccessResponse.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'GetConsultationBallotResponse':
      return GetConsultationBallotResponse.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'GetConsultationConfigurationResponse':
      return GetConsultationConfigurationResponse.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'GetConsultationHistoryRecordResponse':
      return GetConsultationHistoryRecordResponse.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'GetConsultationResultsResponse':
      return GetConsultationResultsResponse.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'GetIdentifiedIncidentFollowUpResponse':
      return GetIdentifiedIncidentFollowUpResponse.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'GetManagedConsultationResultsResponse':
      return GetManagedConsultationResultsResponse.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'GetPracticeAccessResponse':
      return GetPracticeAccessResponse.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'GetPracticeBankChoicesResponse':
      return GetPracticeBankChoicesResponse.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'GetPracticeBankResponse':
      return GetPracticeBankResponse.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'GetPracticeDailySetResponse':
      return GetPracticeDailySetResponse.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'GetPublicIncidentConfigurationResponse':
      return GetPublicIncidentConfigurationResponse.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'GetWellbeingAccessResponse':
      return GetWellbeingAccessResponse.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'GetWellbeingCaseResponse':
      return GetWellbeingCaseResponse.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'GetWellbeingConfigurationResponse':
      return GetWellbeingConfigurationResponse.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'GetWellbeingReportResponse':
      return GetWellbeingReportResponse.fromJson(value as Map<String, dynamic>)
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
    case 'IdentifyIncidentReporterResponse':
      return IdentifyIncidentReporterResponse.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'IncidentFollowUpInput':
      return IncidentFollowUpInput.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'IncidentIdentifyInput':
      return IncidentIdentifyInput.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'IncidentPhoto':
      return IncidentPhoto.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'IncidentPhotoConstraints':
      return IncidentPhotoConstraints.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'IncidentPhotoReadInput':
      return IncidentPhotoReadInput.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'IncidentPublicConfiguration':
      return IncidentPublicConfiguration.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'IncidentPublicConfigurationCategoriesInner':
      return IncidentPublicConfigurationCategoriesInner.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'IncidentPublicThread':
      return IncidentPublicThread.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'IncidentPublicView':
      return IncidentPublicView.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'IncidentReplyInput':
      return IncidentReplyInput.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'IncidentReplyReceipt':
      return IncidentReplyReceipt.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'IncidentSubmissionInput':
      return IncidentSubmissionInput.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'IncidentSubmissionReceipt':
      return IncidentSubmissionReceipt.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'InterventionCreate':
      return InterventionCreate.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'InterventionDetail':
      return InterventionDetail.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'InterventionDetailResponse':
      return InterventionDetailResponse.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'InterventionHistory':
      return InterventionHistory.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'InterventionHistoryResponse':
      return InterventionHistoryResponse.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'InterventionMetadata':
      return InterventionMetadata.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'InterventionMetadataResponse':
      return InterventionMetadataResponse.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'InterventionOwnerWrite':
      return InterventionOwnerWrite.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'InterventionQueue':
      return InterventionQueue.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'InterventionQueueItem':
      return InterventionQueueItem.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'InterventionQueueResponse':
      return InterventionQueueResponse.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'InterventionUpdate':
      return InterventionUpdate.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'InterventionVersion':
      return InterventionVersion.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'InterventionVersionRecord':
      return InterventionVersionRecord.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'IssuePracticeDailySetResponse':
      return IssuePracticeDailySetResponse.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'LearnerNoteAssignment':
      return LearnerNoteAssignment.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'LearnerNoteAssignmentResponse':
      return LearnerNoteAssignmentResponse.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'LearnerNoteAssignmentRevoke':
      return LearnerNoteAssignmentRevoke.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'LearnerNoteAssignmentWrite':
      return LearnerNoteAssignmentWrite.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'LearnerNoteAssignments':
      return LearnerNoteAssignments.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'LearnerNoteAssignmentsResponse':
      return LearnerNoteAssignmentsResponse.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'LearnerNoteCreate':
      return LearnerNoteCreate.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'LearnerNoteDetail':
      return LearnerNoteDetail.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'LearnerNoteDetailResponse':
      return LearnerNoteDetailResponse.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'LearnerNoteHistory':
      return LearnerNoteHistory.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'LearnerNoteHistoryResponse':
      return LearnerNoteHistoryResponse.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'LearnerNoteList':
      return LearnerNoteList.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'LearnerNoteListResponse':
      return LearnerNoteListResponse.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'LearnerNoteMetadata':
      return LearnerNoteMetadata.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'LearnerNoteRevision':
      return LearnerNoteRevision.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'LearnerNoteUpdate':
      return LearnerNoteUpdate.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'LinkWellbeingCaseIncidentResponse':
      return LinkWellbeingCaseIncidentResponse.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'ListConsultationAudienceChoicesResponse':
      return ListConsultationAudienceChoicesResponse.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'ListConsultationHistoryResponse':
      return ListConsultationHistoryResponse.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'ListConsultationResponsesResponse':
      return ListConsultationResponsesResponse.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'ListConsultationsResponse':
      return ListConsultationsResponse.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'ListManagedConsultationsResponse':
      return ListManagedConsultationsResponse.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'ListStaffPrivateNoticesResponse':
      return ListStaffPrivateNoticesResponse.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'ListWellbeingAppointmentsResponse':
      return ListWellbeingAppointmentsResponse.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'ListWellbeingCaseAssignmentsResponse':
      return ListWellbeingCaseAssignmentsResponse.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'ListWellbeingCaseIncidentChoicesResponse':
      return ListWellbeingCaseIncidentChoicesResponse.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'ListWellbeingCaseProfessionalsResponse':
      return ListWellbeingCaseProfessionalsResponse.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'ListWellbeingCasesResponse':
      return ListWellbeingCasesResponse.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'ListWellbeingManagedCasesResponse':
      return ListWellbeingManagedCasesResponse.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'ListWellbeingNoticesResponse':
      return ListWellbeingNoticesResponse.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'ListWellbeingReportsResponse':
      return ListWellbeingReportsResponse.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'ListWellbeingTeamAssignmentsResponse':
      return ListWellbeingTeamAssignmentsResponse.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'LiveAuthorization':
      return LiveAuthorization.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'LiveAuthorizationResponse':
      return LiveAuthorizationResponse.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'LiveAuthorizeRequest':
      return LiveAuthorizeRequest.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'LivePresence':
      return LivePresence.fromJson(value as Map<String, dynamic>) as ReturnType;
    case 'LivePresenceRequest':
      return LivePresenceRequest.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'LivePresenceResponse':
      return LivePresenceResponse.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'LiveSessionConfiguration':
      return LiveSessionConfiguration.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'LiveSessionResponse':
      return LiveSessionResponse.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'MasteryConfiguration':
      return MasteryConfiguration.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'MasteryConfigurationResponse':
      return MasteryConfigurationResponse.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'MasteryGraph':
      return MasteryGraph.fromJson(value as Map<String, dynamic>) as ReturnType;
    case 'MasteryGraphResponse':
      return MasteryGraphResponse.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'MasteryGraphWrite':
      return MasteryGraphWrite.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'MasteryGroup':
      return MasteryGroup.fromJson(value as Map<String, dynamic>) as ReturnType;
    case 'MasteryLink':
      return MasteryLink.fromJson(value as Map<String, dynamic>) as ReturnType;
    case 'MasteryNode':
      return MasteryNode.fromJson(value as Map<String, dynamic>) as ReturnType;
    case 'MasteryPriority':
      return MasteryPriority.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'MasteryRebuild':
      return MasteryRebuild.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'MasteryRebuildResponse':
      return MasteryRebuildResponse.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'MasteryRoster':
      return MasteryRoster.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'MasteryRosterLearner':
      return MasteryRosterLearner.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'MasteryRosterResponse':
      return MasteryRosterResponse.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'MasterySettings':
      return MasterySettings.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'MasterySubjectChoice':
      return MasterySubjectChoice.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'MasterySubjectList':
      return MasterySubjectList.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'MasterySubjectListResponse':
      return MasterySubjectListResponse.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'MasteryTopicView':
      return MasteryTopicView.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'MasteryView':
      return MasteryView.fromJson(value as Map<String, dynamic>) as ReturnType;
    case 'MasteryViewResponse':
      return MasteryViewResponse.fromJson(value as Map<String, dynamic>)
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
    case 'OpenWellbeingCaseResponse':
      return OpenWellbeingCaseResponse.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'Pagination':
      return Pagination.fromJson(value as Map<String, dynamic>) as ReturnType;
    case 'PracticeAccess':
      return PracticeAccess.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'PracticeApproval':
      return PracticeApproval.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'PracticeApproveInput':
      return PracticeApproveInput.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'PracticeBankChoices':
      return PracticeBankChoices.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'PracticeBankItem':
      return PracticeBankItem.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'PracticeBankPage':
      return PracticeBankPage.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'PracticeChoice':
      return PracticeChoice.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'PracticeDailySet':
      return PracticeDailySet.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'PracticeGroupChoice':
      return PracticeGroupChoice.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'PracticeIssueInput':
      return PracticeIssueInput.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'PracticeQuestion':
      return PracticeQuestion.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'PracticeQuestionOption':
      return PracticeQuestionOption.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'PracticeReview':
      return PracticeReview.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'PracticeReviewInput':
      return PracticeReviewInput.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'PracticeSchedule':
      return PracticeSchedule.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'PracticeSetEntry':
      return PracticeSetEntry.fromJson(value as Map<String, dynamic>)
          as ReturnType;
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
    case 'ProgressBandSettings':
      return ProgressBandSettings.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'ProgressBandSettingsResponse':
      return ProgressBandSettingsResponse.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'ProgressBandSettingsWrite':
      return ProgressBandSettingsWrite.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'ProgressConfiguration':
      return ProgressConfiguration.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'ProgressConfigurationResponse':
      return ProgressConfigurationResponse.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'ProgressFamilyBands':
      return ProgressFamilyBands.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'ProgressFamilyBandsResponse':
      return ProgressFamilyBandsResponse.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'ProgressGroup':
      return ProgressGroup.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'ProgressLearnerList':
      return ProgressLearnerList.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'ProgressLearnerListResponse':
      return ProgressLearnerListResponse.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'ProgressLearnerOption':
      return ProgressLearnerOption.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'ProgressOption':
      return ProgressOption.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'ProgressReleasedBand':
      return ProgressReleasedBand.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'ProgressRoster':
      return ProgressRoster.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'ProgressRosterResponse':
      return ProgressRosterResponse.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'ProgressStaffBand':
      return ProgressStaffBand.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'ProgressStaffBandResponse':
      return ProgressStaffBandResponse.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'PublishConsultationResponse':
      return PublishConsultationResponse.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'ReadStaffPrivateNoticeResponse':
      return ReadStaffPrivateNoticeResponse.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'ReadWellbeingNoticeResponse':
      return ReadWellbeingNoticeResponse.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'RecognitionAccess':
      return RecognitionAccess.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'RecognitionAccessResponse':
      return RecognitionAccessResponse.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'RecognitionAwardCategory':
      return RecognitionAwardCategory.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'RecognitionAwardDefinition':
      return RecognitionAwardDefinition.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'RecognitionAwardProfile':
      return RecognitionAwardProfile.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'RecognitionAwardProfileResponse':
      return RecognitionAwardProfileResponse.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'RecognitionAwardWindow':
      return RecognitionAwardWindow.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'RecognitionCatalogueChange':
      return RecognitionCatalogueChange.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'RecognitionConfiguration':
      return RecognitionConfiguration.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'RecognitionConfigurationResponse':
      return RecognitionConfigurationResponse.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'RecognitionConfigure':
      return RecognitionConfigure.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'RecognitionCorrection':
      return RecognitionCorrection.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'RecognitionHouse':
      return RecognitionHouse.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'RecognitionHouseCounter':
      return RecognitionHouseCounter.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'RecognitionHouseCounters':
      return RecognitionHouseCounters.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'RecognitionHouseCountersResponse':
      return RecognitionHouseCountersResponse.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'RecognitionIssue':
      return RecognitionIssue.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'RecognitionLearner':
      return RecognitionLearner.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'RecognitionLearnerPage':
      return RecognitionLearnerPage.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'RecognitionLearnerPageResponse':
      return RecognitionLearnerPageResponse.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'RecognitionNamedReference':
      return RecognitionNamedReference.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'RecognitionNominate':
      return RecognitionNominate.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'RecognitionNomination':
      return RecognitionNomination.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'RecognitionNominationCatalogue':
      return RecognitionNominationCatalogue.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'RecognitionNominationCatalogueResponse':
      return RecognitionNominationCatalogueResponse.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'RecognitionNominationChoice':
      return RecognitionNominationChoice.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'RecognitionNominationPage':
      return RecognitionNominationPage.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'RecognitionNominationPageResponse':
      return RecognitionNominationPageResponse.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'RecognitionOutsideAward':
      return RecognitionOutsideAward.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'RecognitionPermanentAward':
      return RecognitionPermanentAward.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'RecognitionPointCategory':
      return RecognitionPointCategory.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'RecognitionPointEntry':
      return RecognitionPointEntry.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'RecognitionPointHistory':
      return RecognitionPointHistory.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'RecognitionPointHistoryResponse':
      return RecognitionPointHistoryResponse.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'RecognitionPresent':
      return RecognitionPresent.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'RecognitionReference':
      return RecognitionReference.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'RecognitionReferenceResponse':
      return RecognitionReferenceResponse.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'RecognitionReview':
      return RecognitionReview.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'RecognitionSettings':
      return RecognitionSettings.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'RecognitionVersionedCorrection':
      return RecognitionVersionedCorrection.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'RecognitionYear':
      return RecognitionYear.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'ReplyAnonymousIncidentResponse':
      return ReplyAnonymousIncidentResponse.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'ReplyIdentifiedIncidentResponse':
      return ReplyIdentifiedIncidentResponse.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'ReplyWellbeingReportResponse':
      return ReplyWellbeingReportResponse.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'ReviewPracticeEntryResponse':
      return ReviewPracticeEntryResponse.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'RevisePracticeQuestionResponse':
      return RevisePracticeQuestionResponse.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'RiskAssignment':
      return RiskAssignment.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'RiskAssignmentResult':
      return RiskAssignmentResult.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'RiskAssignmentResultResponse':
      return RiskAssignmentResultResponse.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'RiskAssignmentRevoke':
      return RiskAssignmentRevoke.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'RiskAssignmentWrite':
      return RiskAssignmentWrite.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'RiskAssignments':
      return RiskAssignments.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'RiskAssignmentsResponse':
      return RiskAssignmentsResponse.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'RiskClassroom':
      return RiskClassroom.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'RiskClassroomLearner':
      return RiskClassroomLearner.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'RiskClassroomResponse':
      return RiskClassroomResponse.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'RiskClassroomSignal':
      return RiskClassroomSignal.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'RiskConfiguration':
      return RiskConfiguration.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'RiskConfigurationResponse':
      return RiskConfigurationResponse.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'RiskLearnerChoice':
      return RiskLearnerChoice.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'RiskLearnerChoices':
      return RiskLearnerChoices.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'RiskLearnerChoicesResponse':
      return RiskLearnerChoicesResponse.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'RiskOwnerChoice':
      return RiskOwnerChoice.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'RiskOwnerChoices':
      return RiskOwnerChoices.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'RiskOwnerChoicesResponse':
      return RiskOwnerChoicesResponse.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'RiskProfessionalChoices':
      return RiskProfessionalChoices.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'RiskProfessionalChoicesResponse':
      return RiskProfessionalChoicesResponse.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'RiskSettings':
      return RiskSettings.fromJson(value as Map<String, dynamic>) as ReturnType;
    case 'RiskSettingsResponse':
      return RiskSettingsResponse.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'RiskSettingsWrite':
      return RiskSettingsWrite.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'RiskSignal':
      return RiskSignal.fromJson(value as Map<String, dynamic>) as ReturnType;
    case 'RiskSignalLearner':
      return RiskSignalLearner.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'RiskSignals':
      return RiskSignals.fromJson(value as Map<String, dynamic>) as ReturnType;
    case 'RiskSignalsResponse':
      return RiskSignalsResponse.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'RiskStaffChoice':
      return RiskStaffChoice.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'SaveWellbeingAppointmentResponse':
      return SaveWellbeingAppointmentResponse.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'SaveWellbeingCaseAssignmentResponse':
      return SaveWellbeingCaseAssignmentResponse.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'SaveWellbeingCategoryResponse':
      return SaveWellbeingCategoryResponse.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'SaveWellbeingTeamAssignmentResponse':
      return SaveWellbeingTeamAssignmentResponse.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
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
    case 'SetWellbeingCaseStatusResponse':
      return SetWellbeingCaseStatusResponse.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'StaffPrivateNotice':
      return StaffPrivateNotice.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'StaffPrivateNoticePage':
      return StaffPrivateNoticePage.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'StaffPrivateNoticeRead':
      return StaffPrivateNoticeRead.fromJson(value as Map<String, dynamic>)
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
    case 'SubmitAnonymousIncidentResponse':
      return SubmitAnonymousIncidentResponse.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'SubmitIdentifiedIncidentResponse':
      return SubmitIdentifiedIncidentResponse.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'SupportAccess':
      return SupportAccess.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'SupportAccessResponse':
      return SupportAccessResponse.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'SupportAssignInput':
      return SupportAssignInput.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'SupportAssignment':
      return SupportAssignment.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'SupportAssignments':
      return SupportAssignments.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'SupportAssignmentsResponse':
      return SupportAssignmentsResponse.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'SupportCaseSummary':
      return SupportCaseSummary.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'SupportCases':
      return SupportCases.fromJson(value as Map<String, dynamic>) as ReturnType;
    case 'SupportCasesResponse':
      return SupportCasesResponse.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'SupportConfiguration':
      return SupportConfiguration.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'SupportConfigurationResponse':
      return SupportConfigurationResponse.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'SupportDiscloseInput':
      return SupportDiscloseInput.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'SupportDisclosureHistory':
      return SupportDisclosureHistory.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'SupportDisclosureSummary':
      return SupportDisclosureSummary.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'SupportDisclosures':
      return SupportDisclosures.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'SupportDisclosuresResponse':
      return SupportDisclosuresResponse.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'SupportNamedChoice':
      return SupportNamedChoice.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'SupportPlan':
      return SupportPlan.fromJson(value as Map<String, dynamic>) as ReturnType;
    case 'SupportPlanContents':
      return SupportPlanContents.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'SupportPlanResponse':
      return SupportPlanResponse.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'SupportPlanSnapshot':
      return SupportPlanSnapshot.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'SupportReasonInput':
      return SupportReasonInput.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'SupportReview':
      return SupportReview.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'SupportReviewInput':
      return SupportReviewInput.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'SupportReviews':
      return SupportReviews.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'SupportReviewsResponse':
      return SupportReviewsResponse.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'SupportSaveInput':
      return SupportSaveInput.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'SupportTeacherDisclosure':
      return SupportTeacherDisclosure.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'SupportTeacherDisclosureResponse':
      return SupportTeacherDisclosureResponse.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'SupportTeacherList':
      return SupportTeacherList.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'SupportTeacherListResponse':
      return SupportTeacherListResponse.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'SupportVersion':
      return SupportVersion.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'SupportVersions':
      return SupportVersions.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'SupportVersionsResponse':
      return SupportVersionsResponse.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'SupportWriteReceipt':
      return SupportWriteReceipt.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'SupportWriteReceiptResponse':
      return SupportWriteReceiptResponse.fromJson(value as Map<String, dynamic>)
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
    case 'TransferAcceptInput':
      return TransferAcceptInput.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'TransferAccess':
      return TransferAccess.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'TransferAccessResponse':
      return TransferAccessResponse.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'TransferAttendance':
      return TransferAttendance.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'TransferChoice':
      return TransferChoice.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'TransferChoicePage':
      return TransferChoicePage.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'TransferChoicePageResponse':
      return TransferChoicePageResponse.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'TransferConsentInput':
      return TransferConsentInput.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'TransferDetail':
      return TransferDetail.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'TransferDetailResponse':
      return TransferDetailResponse.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'TransferDocument':
      return TransferDocument.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'TransferGalleryChoice':
      return TransferGalleryChoice.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'TransferMark':
      return TransferMark.fromJson(value as Map<String, dynamic>) as ReturnType;
    case 'TransferPage':
      return TransferPage.fromJson(value as Map<String, dynamic>) as ReturnType;
    case 'TransferPageResponse':
      return TransferPageResponse.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'TransferPrepareInput':
      return TransferPrepareInput.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'TransferReport':
      return TransferReport.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'TransferRevisionInput':
      return TransferRevisionInput.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'TransferSummary':
      return TransferSummary.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'TransferWriteReceipt':
      return TransferWriteReceipt.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'TransferWriteReceiptResponse':
      return TransferWriteReceiptResponse.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'UpdateConsultationResponse':
      return UpdateConsultationResponse.fromJson(value as Map<String, dynamic>)
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
    case 'UploadAnonymousIncidentPhotoResponse':
      return UploadAnonymousIncidentPhotoResponse.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'UploadIdentifiedIncidentPhotoResponse':
      return UploadIdentifiedIncidentPhotoResponse.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'ValidationErrors':
      return ValidationErrors.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'WeeklyDigestAccess':
      return WeeklyDigestAccess.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'WeeklyDigestAccessResponse':
      return WeeklyDigestAccessResponse.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'WeeklyDigestActionTemplates':
      return WeeklyDigestActionTemplates.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'WeeklyDigestAttendance':
      return WeeklyDigestAttendance.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'WeeklyDigestChild':
      return WeeklyDigestChild.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'WeeklyDigestDetail':
      return WeeklyDigestDetail.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'WeeklyDigestDetailResponse':
      return WeeklyDigestDetailResponse.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'WeeklyDigestEvent':
      return WeeklyDigestEvent.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'WeeklyDigestEvidence':
      return WeeklyDigestEvidence.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'WeeklyDigestHomeAction':
      return WeeklyDigestHomeAction.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'WeeklyDigestListItem':
      return WeeklyDigestListItem.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'WeeklyDigestMark':
      return WeeklyDigestMark.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'WeeklyDigestPage':
      return WeeklyDigestPage.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'WeeklyDigestPageResponse':
      return WeeklyDigestPageResponse.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'WeeklyDigestPractice':
      return WeeklyDigestPractice.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'WeeklyDigestPreferenceInput':
      return WeeklyDigestPreferenceInput.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'WeeklyDigestReference':
      return WeeklyDigestReference.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'WeeklyDigestReferenceResponse':
      return WeeklyDigestReferenceResponse.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'WeeklyDigestStartingTemplate':
      return WeeklyDigestStartingTemplate.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'WeeklyDigestTemplate':
      return WeeklyDigestTemplate.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'WeeklyDigestTemplateInput':
      return WeeklyDigestTemplateInput.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'WeeklyDigestTemplatePage':
      return WeeklyDigestTemplatePage.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'WeeklyDigestTemplatePageResponse':
      return WeeklyDigestTemplatePageResponse.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'WeeklyDigestTemplateReview':
      return WeeklyDigestTemplateReview.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'WellbeingAccess':
      return WellbeingAccess.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'WellbeingAppointment':
      return WellbeingAppointment.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'WellbeingAppointmentInput':
      return WellbeingAppointmentInput.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'WellbeingAssignment':
      return WellbeingAssignment.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'WellbeingAssignmentInput':
      return WellbeingAssignmentInput.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'WellbeingAssignmentPage':
      return WellbeingAssignmentPage.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'WellbeingCase':
      return WellbeingCase.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'WellbeingCaseAssignmentReceipt':
      return WellbeingCaseAssignmentReceipt.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'WellbeingCaseChoicePage':
      return WellbeingCaseChoicePage.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'WellbeingCaseEntry':
      return WellbeingCaseEntry.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'WellbeingCaseEntryInput':
      return WellbeingCaseEntryInput.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'WellbeingCaseEntryReceipt':
      return WellbeingCaseEntryReceipt.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'WellbeingCaseEntryReceiptEntry':
      return WellbeingCaseEntryReceiptEntry.fromJson(
            value as Map<String, dynamic>,
          )
          as ReturnType;
    case 'WellbeingCaseLinkInput':
      return WellbeingCaseLinkInput.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'WellbeingCaseLinkReceipt':
      return WellbeingCaseLinkReceipt.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'WellbeingCaseOpenInput':
      return WellbeingCaseOpenInput.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'WellbeingCaseOpenReceipt':
      return WellbeingCaseOpenReceipt.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'WellbeingCasePage':
      return WellbeingCasePage.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'WellbeingCaseStatusInput':
      return WellbeingCaseStatusInput.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'WellbeingCaseStatusReceipt':
      return WellbeingCaseStatusReceipt.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'WellbeingCaseSummary':
      return WellbeingCaseSummary.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'WellbeingCategory':
      return WellbeingCategory.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'WellbeingCategoryInput':
      return WellbeingCategoryInput.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'WellbeingConfiguration':
      return WellbeingConfiguration.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'WellbeingLearnerChoice':
      return WellbeingLearnerChoice.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'WellbeingNamedChoice':
      return WellbeingNamedChoice.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'WellbeingNotice':
      return WellbeingNotice.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'WellbeingNoticePage':
      return WellbeingNoticePage.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'WellbeingNoticeReadReceipt':
      return WellbeingNoticeReadReceipt.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'WellbeingReport':
      return WellbeingReport.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'WellbeingReportPage':
      return WellbeingReportPage.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'WellbeingReportReplyInput':
      return WellbeingReportReplyInput.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'WellbeingReportReplyReceipt':
      return WellbeingReportReplyReceipt.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'WellbeingReportSummary':
      return WellbeingReportSummary.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'WellbeingReportThread':
      return WellbeingReportThread.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'WellbeingTeamReceipt':
      return WellbeingTeamReceipt.fromJson(value as Map<String, dynamic>)
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
