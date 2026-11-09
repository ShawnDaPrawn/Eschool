// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'badge_credential_list_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

BadgeCredentialListResponse _$BadgeCredentialListResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('BadgeCredentialListResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = BadgeCredentialListResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) =>
          BadgeCredentialListResponseData.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$BadgeCredentialListResponseToJson(
  BadgeCredentialListResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
