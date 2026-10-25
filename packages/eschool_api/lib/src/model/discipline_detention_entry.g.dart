// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'discipline_detention_entry.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

DisciplineDetentionEntry _$DisciplineDetentionEntryFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'DisciplineDetentionEntry',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'id',
        'record_id',
        'learner_id',
        'name',
        'attendance',
        'status',
        'source_valid',
        'reason',
        'revision',
      ],
    );
    final val = DisciplineDetentionEntry(
      id: $checkedConvert('id', (v) => (v as num).toInt()),
      recordId: $checkedConvert('record_id', (v) => (v as num).toInt()),
      learnerId: $checkedConvert('learner_id', (v) => (v as num).toInt()),
      name: $checkedConvert('name', (v) => v as String),
      attendance: $checkedConvert(
        'attendance',
        (v) => $enumDecodeNullable(
          _$DisciplineDetentionEntryAttendanceEnumEnumMap,
          v,
          unknownValue:
              DisciplineDetentionEntryAttendanceEnum.unknownDefaultOpenApi,
        ),
      ),
      status: $checkedConvert(
        'status',
        (v) => $enumDecode(
          _$DisciplineDetentionEntryStatusEnumEnumMap,
          v,
          unknownValue:
              DisciplineDetentionEntryStatusEnum.unknownDefaultOpenApi,
        ),
      ),
      sourceValid: $checkedConvert('source_valid', (v) => v as bool),
      reason: $checkedConvert('reason', (v) => v as String?),
      revision: $checkedConvert('revision', (v) => (v as num).toInt()),
    );
    return val;
  },
  fieldKeyMap: const {
    'recordId': 'record_id',
    'learnerId': 'learner_id',
    'sourceValid': 'source_valid',
  },
);

Map<String, dynamic> _$DisciplineDetentionEntryToJson(
  DisciplineDetentionEntry instance,
) => <String, dynamic>{
  'id': instance.id,
  'record_id': instance.recordId,
  'learner_id': instance.learnerId,
  'name': instance.name,
  'attendance':
      _$DisciplineDetentionEntryAttendanceEnumEnumMap[instance.attendance],
  'status': _$DisciplineDetentionEntryStatusEnumEnumMap[instance.status]!,
  'source_valid': instance.sourceValid,
  'reason': instance.reason,
  'revision': instance.revision,
};

const _$DisciplineDetentionEntryAttendanceEnumEnumMap = {
  DisciplineDetentionEntryAttendanceEnum.present: 'present',
  DisciplineDetentionEntryAttendanceEnum.absent: 'absent',
  DisciplineDetentionEntryAttendanceEnum.excused: 'excused',
  DisciplineDetentionEntryAttendanceEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};

const _$DisciplineDetentionEntryStatusEnumEnumMap = {
  DisciplineDetentionEntryStatusEnum.active: 'active',
  DisciplineDetentionEntryStatusEnum.cancelled: 'cancelled',
  DisciplineDetentionEntryStatusEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
