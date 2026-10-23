// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'guardian_approval.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

GuardianApproval _$GuardianApprovalFromJson(Map<String, dynamic> json) =>
    $checkedCreate('GuardianApproval', json, ($checkedConvert) {
      $checkKeys(
        json,
        requiredKeys: const ['learner', 'status', 'requested_at', 'consent'],
      );
      final val = GuardianApproval(
        learner: $checkedConvert(
          'learner',
          (v) => ApprovalLearner.fromJson(v as Map<String, dynamic>),
        ),
        status: $checkedConvert(
          'status',
          (v) => $enumDecode(
            _$GuardianApprovalStatusEnumEnumMap,
            v,
            unknownValue: GuardianApprovalStatusEnum.unknownDefaultOpenApi,
          ),
        ),
        requestedAt: $checkedConvert(
          'requested_at',
          (v) => v == null ? null : DateTime.parse(v as String),
        ),
        consent: $checkedConvert(
          'consent',
          (v) => GuardianConsent.fromJson(v as Map<String, dynamic>),
        ),
      );
      return val;
    }, fieldKeyMap: const {'requestedAt': 'requested_at'});

Map<String, dynamic> _$GuardianApprovalToJson(GuardianApproval instance) =>
    <String, dynamic>{
      'learner': instance.learner.toJson(),
      'status': _$GuardianApprovalStatusEnumEnumMap[instance.status]!,
      'requested_at': instance.requestedAt?.toIso8601String(),
      'consent': instance.consent.toJson(),
    };

const _$GuardianApprovalStatusEnumEnumMap = {
  GuardianApprovalStatusEnum.pending: 'pending',
  GuardianApprovalStatusEnum.approved: 'approved',
  GuardianApprovalStatusEnum.withdrawn: 'withdrawn',
  GuardianApprovalStatusEnum.unknownDefaultOpenApi: 'unknown_default_open_api',
};
