// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_timetable_next_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicTimetableNextResponse _$AcademicTimetableNextResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('AcademicTimetableNextResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = AcademicTimetableNextResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) => AcademicTimetableNextData.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$AcademicTimetableNextResponseToJson(
  AcademicTimetableNextResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
