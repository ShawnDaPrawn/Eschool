// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'decide_guardian_handle_proposal_request.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

DecideGuardianHandleProposalRequest
_$DecideGuardianHandleProposalRequestFromJson(Map<String, dynamic> json) =>
    $checkedCreate('DecideGuardianHandleProposalRequest', json, (
      $checkedConvert,
    ) {
      $checkKeys(json, requiredKeys: const ['decision']);
      final val = DecideGuardianHandleProposalRequest(
        decision: $checkedConvert(
          'decision',
          (v) => $enumDecode(
            _$DecideGuardianHandleProposalRequestDecisionEnumEnumMap,
            v,
            unknownValue: DecideGuardianHandleProposalRequestDecisionEnum
                .unknownDefaultOpenApi,
          ),
        ),
      );
      return val;
    });

Map<String, dynamic> _$DecideGuardianHandleProposalRequestToJson(
  DecideGuardianHandleProposalRequest instance,
) => <String, dynamic>{
  'decision':
      _$DecideGuardianHandleProposalRequestDecisionEnumEnumMap[instance
          .decision]!,
};

const _$DecideGuardianHandleProposalRequestDecisionEnumEnumMap = {
  DecideGuardianHandleProposalRequestDecisionEnum.approve: 'approve',
  DecideGuardianHandleProposalRequestDecisionEnum.reject: 'reject',
  DecideGuardianHandleProposalRequestDecisionEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
