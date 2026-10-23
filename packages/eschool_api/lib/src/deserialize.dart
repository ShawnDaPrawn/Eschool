import 'package:eschool_api/src/model/accept_guardian_invitation_request.dart';
import 'package:eschool_api/src/model/approval_learner.dart';
import 'package:eschool_api/src/model/approval_state.dart';
import 'package:eschool_api/src/model/approval_state_response.dart';
import 'package:eschool_api/src/model/branding.dart';
import 'package:eschool_api/src/model/child.dart';
import 'package:eschool_api/src/model/context.dart';
import 'package:eschool_api/src/model/context_response.dart';
import 'package:eschool_api/src/model/context_user.dart';
import 'package:eschool_api/src/model/create_learner_enrolment_request.dart';
import 'package:eschool_api/src/model/create_profile_category_request.dart';
import 'package:eschool_api/src/model/create_profile_field_request.dart';
import 'package:eschool_api/src/model/current_school.dart';
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
import 'package:eschool_api/src/model/school_summary.dart';
import 'package:eschool_api/src/model/session_year.dart';
import 'package:eschool_api/src/model/set_my_handle_request.dart';
import 'package:eschool_api/src/model/term.dart';
import 'package:eschool_api/src/model/term_dates.dart';
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
    case 'Child':
      return Child.fromJson(value as Map<String, dynamic>) as ReturnType;
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
    case 'SchoolSummary':
      return SchoolSummary.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'SessionYear':
      return SessionYear.fromJson(value as Map<String, dynamic>) as ReturnType;
    case 'SetMyHandleRequest':
      return SetMyHandleRequest.fromJson(value as Map<String, dynamic>)
          as ReturnType;
    case 'Term':
      return Term.fromJson(value as Map<String, dynamic>) as ReturnType;
    case 'TermDates':
      return TermDates.fromJson(value as Map<String, dynamic>) as ReturnType;
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
