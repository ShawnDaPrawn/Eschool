// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'guardian_invitation_issued_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

GuardianInvitationIssuedResponse _$GuardianInvitationIssuedResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('GuardianInvitationIssuedResponse', json, (
  $checkedConvert,
) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = GuardianInvitationIssuedResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => GuardianInvitationIssued.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$GuardianInvitationIssuedResponseToJson(
  GuardianInvitationIssuedResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
