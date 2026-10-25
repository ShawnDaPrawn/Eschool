// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'cover_create_input.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

CoverCreateInput _$CoverCreateInputFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'CoverCreateInput',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'operation_id',
            'session_year_id',
            'teacher_id',
            'starts_on',
            'ends_on',
          ],
        );
        final val = CoverCreateInput(
          operationId: $checkedConvert('operation_id', (v) => v as String),
          sessionYearId: $checkedConvert(
            'session_year_id',
            (v) => (v as num).toInt(),
          ),
          teacherId: $checkedConvert('teacher_id', (v) => (v as num).toInt()),
          startsOn: $checkedConvert('starts_on', (v) => v as String),
          endsOn: $checkedConvert('ends_on', (v) => v as String),
          reason: $checkedConvert('reason', (v) => v as String?),
        );
        return val;
      },
      fieldKeyMap: const {
        'operationId': 'operation_id',
        'sessionYearId': 'session_year_id',
        'teacherId': 'teacher_id',
        'startsOn': 'starts_on',
        'endsOn': 'ends_on',
      },
    );

Map<String, dynamic> _$CoverCreateInputToJson(CoverCreateInput instance) =>
    <String, dynamic>{
      'operation_id': instance.operationId,
      'session_year_id': instance.sessionYearId,
      'teacher_id': instance.teacherId,
      'starts_on': instance.startsOn,
      'ends_on': instance.endsOn,
      'reason': ?instance.reason,
    };
