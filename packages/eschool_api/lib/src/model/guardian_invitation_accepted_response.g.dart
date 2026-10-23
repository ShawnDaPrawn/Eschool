// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'guardian_invitation_accepted_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

GuardianInvitationAcceptedResponse _$GuardianInvitationAcceptedResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('GuardianInvitationAcceptedResponse', json, (
  $checkedConvert,
) {
  $checkKeys(
    json,
    requiredKeys: const ['error', 'message', 'data', 'code', 'token'],
  );
  final val = GuardianInvitationAcceptedResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) =>
          (v as Map<String, dynamic>).map((k, e) => MapEntry(k, e as Object)),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    token: $checkedConvert('token', (v) => v as String),
  );
  return val;
});

Map<String, dynamic> _$GuardianInvitationAcceptedResponseToJson(
  GuardianInvitationAcceptedResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data,
  'code': instance.code,
  'token': instance.token,
};
