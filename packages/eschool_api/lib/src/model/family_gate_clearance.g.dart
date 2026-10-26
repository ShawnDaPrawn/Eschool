// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'family_gate_clearance.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

FamilyGateClearance _$FamilyGateClearanceFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('FamilyGateClearance', json, ($checkedConvert) {
  $checkKeys(
    json,
    requiredKeys: const ['id', 'revision', 'collector', 'starts_at', 'ends_at'],
  );
  final val = FamilyGateClearance(
    id: $checkedConvert('id', (v) => v as String),
    revision: $checkedConvert('revision', (v) => (v as num).toInt()),
    collector: $checkedConvert('collector', (v) => v as String),
    startsAt: $checkedConvert('starts_at', (v) => DateTime.parse(v as String)),
    endsAt: $checkedConvert('ends_at', (v) => DateTime.parse(v as String)),
  );
  return val;
}, fieldKeyMap: const {'startsAt': 'starts_at', 'endsAt': 'ends_at'});

Map<String, dynamic> _$FamilyGateClearanceToJson(
  FamilyGateClearance instance,
) => <String, dynamic>{
  'id': instance.id,
  'revision': instance.revision,
  'collector': instance.collector,
  'starts_at': instance.startsAt.toIso8601String(),
  'ends_at': instance.endsAt.toIso8601String(),
};
