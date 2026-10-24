// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_timetable_commit_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicTimetableCommitResponse _$AcademicTimetableCommitResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('AcademicTimetableCommitResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = AcademicTimetableCommitResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) => AcademicTimetableCommitData.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$AcademicTimetableCommitResponseToJson(
  AcademicTimetableCommitResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
