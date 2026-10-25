// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'discipline_detention_attendance_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

DisciplineDetentionAttendanceResponse
_$DisciplineDetentionAttendanceResponseFromJson(Map<String, dynamic> json) =>
    $checkedCreate('DisciplineDetentionAttendanceResponse', json, (
      $checkedConvert,
    ) {
      $checkKeys(
        json,
        requiredKeys: const ['error', 'message', 'code', 'data'],
      );
      final val = DisciplineDetentionAttendanceResponse(
        error: $checkedConvert('error', (v) => v as bool),
        message: $checkedConvert('message', (v) => v as String),
        code: $checkedConvert('code', (v) => (v as num).toInt()),
        data: $checkedConvert(
          'data',
          (v) =>
              DisciplineDetentionAttendance.fromJson(v as Map<String, dynamic>),
        ),
      );
      return val;
    });

Map<String, dynamic> _$DisciplineDetentionAttendanceResponseToJson(
  DisciplineDetentionAttendanceResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
