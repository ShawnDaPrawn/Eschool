// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'curriculum_plans_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

CurriculumPlansResponse _$CurriculumPlansResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('CurriculumPlansResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = CurriculumPlansResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) => CurriculumPlansResponseData.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$CurriculumPlansResponseToJson(
  CurriculumPlansResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
