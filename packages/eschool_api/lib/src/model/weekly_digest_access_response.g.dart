// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'weekly_digest_access_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

WeeklyDigestAccessResponse _$WeeklyDigestAccessResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('WeeklyDigestAccessResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = WeeklyDigestAccessResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) => WeeklyDigestAccess.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$WeeklyDigestAccessResponseToJson(
  WeeklyDigestAccessResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
