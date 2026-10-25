// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'intervention_version.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

InterventionVersion _$InterventionVersionFromJson(Map<String, dynamic> json) =>
    $checkedCreate('InterventionVersion', json, ($checkedConvert) {
      $checkKeys(
        json,
        requiredKeys: const ['revision', 'created_at', 'actor_id', 'record'],
      );
      final val = InterventionVersion(
        revision: $checkedConvert('revision', (v) => (v as num).toInt()),
        createdAt: $checkedConvert(
          'created_at',
          (v) => DateTime.parse(v as String),
        ),
        actorId: $checkedConvert('actor_id', (v) => (v as num).toInt()),
        record: $checkedConvert(
          'record',
          (v) => InterventionVersionRecord.fromJson(v as Map<String, dynamic>),
        ),
      );
      return val;
    }, fieldKeyMap: const {'createdAt': 'created_at', 'actorId': 'actor_id'});

Map<String, dynamic> _$InterventionVersionToJson(
  InterventionVersion instance,
) => <String, dynamic>{
  'revision': instance.revision,
  'created_at': instance.createdAt.toIso8601String(),
  'actor_id': instance.actorId,
  'record': instance.record.toJson(),
};
