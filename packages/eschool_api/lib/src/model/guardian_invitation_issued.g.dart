// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'guardian_invitation_issued.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

GuardianInvitationIssued _$GuardianInvitationIssuedFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('GuardianInvitationIssued', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['id', 'expires_at']);
  final val = GuardianInvitationIssued(
    id: $checkedConvert('id', (v) => (v as num).toInt()),
    expiresAt: $checkedConvert(
      'expires_at',
      (v) => DateTime.parse(v as String),
    ),
  );
  return val;
}, fieldKeyMap: const {'expiresAt': 'expires_at'});

Map<String, dynamic> _$GuardianInvitationIssuedToJson(
  GuardianInvitationIssued instance,
) => <String, dynamic>{
  'id': instance.id,
  'expires_at': instance.expiresAt.toIso8601String(),
};
