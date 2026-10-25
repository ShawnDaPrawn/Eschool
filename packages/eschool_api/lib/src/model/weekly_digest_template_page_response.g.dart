// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'weekly_digest_template_page_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

WeeklyDigestTemplatePageResponse _$WeeklyDigestTemplatePageResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('WeeklyDigestTemplatePageResponse', json, (
  $checkedConvert,
) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = WeeklyDigestTemplatePageResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) => WeeklyDigestTemplatePage.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$WeeklyDigestTemplatePageResponseToJson(
  WeeklyDigestTemplatePageResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
