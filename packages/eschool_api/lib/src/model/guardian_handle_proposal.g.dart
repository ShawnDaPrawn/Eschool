// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'guardian_handle_proposal.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

GuardianHandleProposal _$GuardianHandleProposalFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('GuardianHandleProposal', json, ($checkedConvert) {
  $checkKeys(
    json,
    requiredKeys: const ['id', 'handle', 'expires_at', 'learner'],
  );
  final val = GuardianHandleProposal(
    id: $checkedConvert('id', (v) => (v as num).toInt()),
    handle: $checkedConvert('handle', (v) => v as String),
    expiresAt: $checkedConvert(
      'expires_at',
      (v) => DateTime.parse(v as String),
    ),
    learner: $checkedConvert(
      'learner',
      (v) => GuardianInvitationPreviewLearnersInner.fromJson(
        v as Map<String, dynamic>,
      ),
    ),
  );
  return val;
}, fieldKeyMap: const {'expiresAt': 'expires_at'});

Map<String, dynamic> _$GuardianHandleProposalToJson(
  GuardianHandleProposal instance,
) => <String, dynamic>{
  'id': instance.id,
  'handle': instance.handle,
  'expires_at': instance.expiresAt.toIso8601String(),
  'learner': instance.learner.toJson(),
};
