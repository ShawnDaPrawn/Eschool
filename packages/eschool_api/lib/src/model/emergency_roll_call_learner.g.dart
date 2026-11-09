// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'emergency_roll_call_learner.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

EmergencyRollCallLearner _$EmergencyRollCallLearnerFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'EmergencyRollCallLearner',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'learner_id',
        'name',
        'status',
        'marked_at',
        'released_at',
        'released_to',
      ],
    );
    final val = EmergencyRollCallLearner(
      learnerId: $checkedConvert('learner_id', (v) => (v as num).toInt()),
      name: $checkedConvert('name', (v) => v as String),
      status: $checkedConvert(
        'status',
        (v) => $enumDecode(
          _$EmergencyRollCallLearnerStatusEnumEnumMap,
          v,
          unknownValue:
              EmergencyRollCallLearnerStatusEnum.unknownDefaultOpenApi,
        ),
      ),
      markedAt: $checkedConvert(
        'marked_at',
        (v) => v == null ? null : DateTime.parse(v as String),
      ),
      releasedAt: $checkedConvert(
        'released_at',
        (v) => v == null ? null : DateTime.parse(v as String),
      ),
      releasedTo: $checkedConvert(
        'released_to',
        (v) => $enumDecodeNullable(
          _$EmergencyRollCallLearnerReleasedToEnumEnumMap,
          v,
          unknownValue:
              EmergencyRollCallLearnerReleasedToEnum.unknownDefaultOpenApi,
        ),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'learnerId': 'learner_id',
    'markedAt': 'marked_at',
    'releasedAt': 'released_at',
    'releasedTo': 'released_to',
  },
);

Map<String, dynamic> _$EmergencyRollCallLearnerToJson(
  EmergencyRollCallLearner instance,
) => <String, dynamic>{
  'learner_id': instance.learnerId,
  'name': instance.name,
  'status': _$EmergencyRollCallLearnerStatusEnumEnumMap[instance.status]!,
  'marked_at': instance.markedAt?.toIso8601String(),
  'released_at': instance.releasedAt?.toIso8601String(),
  'released_to':
      _$EmergencyRollCallLearnerReleasedToEnumEnumMap[instance.releasedTo],
};

const _$EmergencyRollCallLearnerStatusEnumEnumMap = {
  EmergencyRollCallLearnerStatusEnum.accounted: 'accounted',
  EmergencyRollCallLearnerStatusEnum.unaccounted: 'unaccounted',
  EmergencyRollCallLearnerStatusEnum.released: 'released',
  EmergencyRollCallLearnerStatusEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};

const _$EmergencyRollCallLearnerReleasedToEnumEnumMap = {
  EmergencyRollCallLearnerReleasedToEnum.guardian: 'guardian',
  EmergencyRollCallLearnerReleasedToEnum.collector: 'collector',
  EmergencyRollCallLearnerReleasedToEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
