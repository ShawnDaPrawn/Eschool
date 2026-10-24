// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_attendance_today_entry.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicAttendanceTodayEntry _$AcademicAttendanceTodayEntryFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'AcademicAttendanceTodayEntry',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'register_id',
        'label',
        'kind',
        'state',
        'source',
        'amended_at',
      ],
    );
    final val = AcademicAttendanceTodayEntry(
      registerId: $checkedConvert('register_id', (v) => (v as num).toInt()),
      label: $checkedConvert('label', (v) => v as String),
      kind: $checkedConvert('kind', (v) => v as String),
      state: $checkedConvert('state', (v) => v as String),
      source_: $checkedConvert('source', (v) => v as String),
      amendedAt: $checkedConvert('amended_at', (v) => v as String?),
    );
    return val;
  },
  fieldKeyMap: const {
    'registerId': 'register_id',
    'source_': 'source',
    'amendedAt': 'amended_at',
  },
);

Map<String, dynamic> _$AcademicAttendanceTodayEntryToJson(
  AcademicAttendanceTodayEntry instance,
) => <String, dynamic>{
  'register_id': instance.registerId,
  'label': instance.label,
  'kind': instance.kind,
  'state': instance.state,
  'source': instance.source_,
  'amended_at': instance.amendedAt,
};
