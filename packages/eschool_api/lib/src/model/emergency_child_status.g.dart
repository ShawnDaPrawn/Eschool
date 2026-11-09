// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'emergency_child_status.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

EmergencyChildStatus _$EmergencyChildStatusFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'EmergencyChildStatus',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'learner_id',
        'name',
        'status',
        'released_at',
        'released_to',
        'released_to_name',
      ],
    );
    final val = EmergencyChildStatus(
      learnerId: $checkedConvert('learner_id', (v) => (v as num).toInt()),
      name: $checkedConvert('name', (v) => v as String),
      status: $checkedConvert(
        'status',
        (v) => $enumDecodeNullable(
          _$EmergencyChildStatusStatusEnumEnumMap,
          v,
          unknownValue: EmergencyChildStatusStatusEnum.unknownDefaultOpenApi,
        ),
      ),
      releasedAt: $checkedConvert(
        'released_at',
        (v) => v == null ? null : DateTime.parse(v as String),
      ),
      releasedTo: $checkedConvert(
        'released_to',
        (v) => $enumDecodeNullable(
          _$EmergencyChildStatusReleasedToEnumEnumMap,
          v,
          unknownValue:
              EmergencyChildStatusReleasedToEnum.unknownDefaultOpenApi,
        ),
      ),
      releasedToName: $checkedConvert('released_to_name', (v) => v as String?),
    );
    return val;
  },
  fieldKeyMap: const {
    'learnerId': 'learner_id',
    'releasedAt': 'released_at',
    'releasedTo': 'released_to',
    'releasedToName': 'released_to_name',
  },
);

Map<String, dynamic> _$EmergencyChildStatusToJson(
  EmergencyChildStatus instance,
) => <String, dynamic>{
  'learner_id': instance.learnerId,
  'name': instance.name,
  'status': _$EmergencyChildStatusStatusEnumEnumMap[instance.status],
  'released_at': instance.releasedAt?.toIso8601String(),
  'released_to':
      _$EmergencyChildStatusReleasedToEnumEnumMap[instance.releasedTo],
  'released_to_name': instance.releasedToName,
};

const _$EmergencyChildStatusStatusEnumEnumMap = {
  EmergencyChildStatusStatusEnum.accounted: 'accounted',
  EmergencyChildStatusStatusEnum.unaccounted: 'unaccounted',
  EmergencyChildStatusStatusEnum.released: 'released',
  EmergencyChildStatusStatusEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};

const _$EmergencyChildStatusReleasedToEnumEnumMap = {
  EmergencyChildStatusReleasedToEnum.guardian: 'guardian',
  EmergencyChildStatusReleasedToEnum.collector: 'collector',
  EmergencyChildStatusReleasedToEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
