// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'cover_absence_summary.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

CoverAbsenceSummary _$CoverAbsenceSummaryFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'CoverAbsenceSummary',
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
          ],
        );
        final val = CoverAbsenceSummary(
          id: $checkedConvert('id', (v) => (v as num).toInt()),
          revision: $checkedConvert('revision', (v) => (v as num).toInt()),
          state: $checkedConvert(
            'state',
            (v) => $enumDecode(
              _$CoverAbsenceSummaryStateEnumEnumMap,
              v,
              unknownValue: CoverAbsenceSummaryStateEnum.unknownDefaultOpenApi,
            ),
          ),
          teacherId: $checkedConvert('teacher_id', (v) => (v as num).toInt()),
          teacherName: $checkedConvert('teacher_name', (v) => v as String),
          startsOn: $checkedConvert('starts_on', (v) => v as String),
          endsOn: $checkedConvert('ends_on', (v) => v as String),
        );
        return val;
      },
      fieldKeyMap: const {
        'teacherId': 'teacher_id',
        'teacherName': 'teacher_name',
        'startsOn': 'starts_on',
        'endsOn': 'ends_on',
      },
    );

Map<String, dynamic> _$CoverAbsenceSummaryToJson(
  CoverAbsenceSummary instance,
) => <String, dynamic>{
  'id': instance.id,
  'revision': instance.revision,
  'state': _$CoverAbsenceSummaryStateEnumEnumMap[instance.state]!,
  'teacher_id': instance.teacherId,
  'teacher_name': instance.teacherName,
  'starts_on': instance.startsOn,
  'ends_on': instance.endsOn,
};

const _$CoverAbsenceSummaryStateEnumEnumMap = {
  CoverAbsenceSummaryStateEnum.requested: 'requested',
  CoverAbsenceSummaryStateEnum.cancelled: 'cancelled',
  CoverAbsenceSummaryStateEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
