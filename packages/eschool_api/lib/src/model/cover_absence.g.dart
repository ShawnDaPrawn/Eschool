// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'cover_absence.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

CoverAbsence _$CoverAbsenceFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'CoverAbsence',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'id',
            'revision',
            'state',
            'teacher_id',
            'teacher_name',
            'starts_on',
            'ends_on',
            'session_year_id',
            'reason',
            'cancel_reason',
            'occurrences',
          ],
        );
        final val = CoverAbsence(
          id: $checkedConvert('id', (v) => (v as num).toInt()),
          revision: $checkedConvert('revision', (v) => (v as num).toInt()),
          state: $checkedConvert(
            'state',
            (v) => $enumDecode(
              _$CoverAbsenceStateEnumEnumMap,
              v,
              unknownValue: CoverAbsenceStateEnum.unknownDefaultOpenApi,
            ),
          ),
          teacherId: $checkedConvert('teacher_id', (v) => (v as num).toInt()),
          teacherName: $checkedConvert('teacher_name', (v) => v as String),
          startsOn: $checkedConvert('starts_on', (v) => v as String),
          endsOn: $checkedConvert('ends_on', (v) => v as String),
          sessionYearId: $checkedConvert(
            'session_year_id',
            (v) => (v as num).toInt(),
          ),
          reason: $checkedConvert('reason', (v) => v as String?),
          cancelReason: $checkedConvert('cancel_reason', (v) => v as String?),
          occurrences: $checkedConvert(
            'occurrences',
            (v) => (v as List<dynamic>)
                .map((e) => CoverOccurrence.fromJson(e as Map<String, dynamic>))
                .toList(),
          ),
        );
        return val;
      },
      fieldKeyMap: const {
        'teacherId': 'teacher_id',
        'teacherName': 'teacher_name',
        'startsOn': 'starts_on',
        'endsOn': 'ends_on',
        'sessionYearId': 'session_year_id',
        'cancelReason': 'cancel_reason',
      },
    );

Map<String, dynamic> _$CoverAbsenceToJson(CoverAbsence instance) =>
    <String, dynamic>{
      'id': instance.id,
      'revision': instance.revision,
      'state': _$CoverAbsenceStateEnumEnumMap[instance.state]!,
      'teacher_id': instance.teacherId,
      'teacher_name': instance.teacherName,
      'starts_on': instance.startsOn,
      'ends_on': instance.endsOn,
      'session_year_id': instance.sessionYearId,
      'reason': instance.reason,
      'cancel_reason': instance.cancelReason,
      'occurrences': instance.occurrences.map((e) => e.toJson()).toList(),
    };

const _$CoverAbsenceStateEnumEnumMap = {
  CoverAbsenceStateEnum.requested: 'requested',
  CoverAbsenceStateEnum.cancelled: 'cancelled',
  CoverAbsenceStateEnum.unknownDefaultOpenApi: 'unknown_default_open_api',
};
