// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'family_gate_collected.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

FamilyGateCollected _$FamilyGateCollectedFromJson(Map<String, dynamic> json) =>
    $checkedCreate('FamilyGateCollected', json, ($checkedConvert) {
      $checkKeys(
        json,
        requiredKeys: const ['id', 'status', 'revision', 'collected_at'],
      );
      final val = FamilyGateCollected(
        id: $checkedConvert('id', (v) => v as String),
        status: $checkedConvert('status', (v) => v as String),
        revision: $checkedConvert('revision', (v) => (v as num).toInt()),
        collectedAt: $checkedConvert(
          'collected_at',
          (v) => DateTime.parse(v as String),
        ),
      );
      return val;
    }, fieldKeyMap: const {'collectedAt': 'collected_at'});

Map<String, dynamic> _$FamilyGateCollectedToJson(
  FamilyGateCollected instance,
) => <String, dynamic>{
  'id': instance.id,
  'status': instance.status,
  'revision': instance.revision,
  'collected_at': instance.collectedAt.toIso8601String(),
};
