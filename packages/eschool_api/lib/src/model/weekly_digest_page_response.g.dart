// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'weekly_digest_page_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

WeeklyDigestPageResponse _$WeeklyDigestPageResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('WeeklyDigestPageResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = WeeklyDigestPageResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) => WeeklyDigestPage.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$WeeklyDigestPageResponseToJson(
  WeeklyDigestPageResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
