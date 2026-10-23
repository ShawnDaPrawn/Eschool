// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'guardian_invitation_preview_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

GuardianInvitationPreviewResponse _$GuardianInvitationPreviewResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('GuardianInvitationPreviewResponse', json, (
  $checkedConvert,
) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = GuardianInvitationPreviewResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => GuardianInvitationPreview.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$GuardianInvitationPreviewResponseToJson(
  GuardianInvitationPreviewResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
