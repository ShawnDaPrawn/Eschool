// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'exam_coach_paper_list_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ExamCoachPaperListResponse _$ExamCoachPaperListResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('ExamCoachPaperListResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = ExamCoachPaperListResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => ExamCoachPaperList.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$ExamCoachPaperListResponseToJson(
  ExamCoachPaperListResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
