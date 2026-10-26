// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'foundation_learner_context.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

FoundationLearnerContext _$FoundationLearnerContextFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'FoundationLearnerContext',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'school_id',
        'learner_id',
        'name',
        'teaching_group_id',
        'expires_at',
        'restricted',
        'capabilities',
      ],
    );
    final val = FoundationLearnerContext(
      schoolId: $checkedConvert('school_id', (v) => (v as num).toInt()),
      learnerId: $checkedConvert('learner_id', (v) => (v as num).toInt()),
      name: $checkedConvert('name', (v) => v as String),
      teachingGroupId: $checkedConvert(
        'teaching_group_id',
        (v) => (v as num).toInt(),
      ),
      expiresAt: $checkedConvert(
        'expires_at',
        (v) => DateTime.parse(v as String),
      ),
      restricted: $checkedConvert('restricted', (v) => v as bool),
      capabilities: $checkedConvert(
        'capabilities',
        (v) => (v as List<dynamic>).map((e) => e as String).toList(),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'schoolId': 'school_id',
    'learnerId': 'learner_id',
    'teachingGroupId': 'teaching_group_id',
    'expiresAt': 'expires_at',
  },
);

Map<String, dynamic> _$FoundationLearnerContextToJson(
  FoundationLearnerContext instance,
) => <String, dynamic>{
  'school_id': instance.schoolId,
  'learner_id': instance.learnerId,
  'name': instance.name,
  'teaching_group_id': instance.teachingGroupId,
  'expires_at': instance.expiresAt.toIso8601String(),
  'restricted': instance.restricted,
  'capabilities': instance.capabilities,
};
