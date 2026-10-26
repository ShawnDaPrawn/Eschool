// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'exam_coach_staff_choices_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ExamCoachStaffChoicesResponse _$ExamCoachStaffChoicesResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('ExamCoachStaffChoicesResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = ExamCoachStaffChoicesResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => ExamCoachStaffChoices.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$ExamCoachStaffChoicesResponseToJson(
  ExamCoachStaffChoicesResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
