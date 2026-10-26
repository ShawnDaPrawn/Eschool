// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'exam_coach_paper_version_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ExamCoachPaperVersionResponse _$ExamCoachPaperVersionResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('ExamCoachPaperVersionResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = ExamCoachPaperVersionResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => ExamCoachPaperVersion.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$ExamCoachPaperVersionResponseToJson(
  ExamCoachPaperVersionResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
