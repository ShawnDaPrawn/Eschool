// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'term_template_apply_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

TermTemplateApplyResponse _$TermTemplateApplyResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('TermTemplateApplyResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = TermTemplateApplyResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) => TermTemplateApplyResponseData.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$TermTemplateApplyResponseToJson(
  TermTemplateApplyResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
