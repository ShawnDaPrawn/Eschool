// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'term_templates_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

TermTemplatesResponse _$TermTemplatesResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('TermTemplatesResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = TermTemplatesResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) => TermTemplatesResponseData.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$TermTemplatesResponseToJson(
  TermTemplatesResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
