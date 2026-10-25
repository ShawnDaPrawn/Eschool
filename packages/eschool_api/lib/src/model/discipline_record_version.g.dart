// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'discipline_record_version.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

DisciplineRecordVersion _$DisciplineRecordVersionFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('DisciplineRecordVersion', json, ($checkedConvert) {
  $checkKeys(
    json,
    requiredKeys: const ['revision', 'actor_id', 'created_at', 'record'],
  );
  final val = DisciplineRecordVersion(
    revision: $checkedConvert('revision', (v) => (v as num).toInt()),
    actorId: $checkedConvert('actor_id', (v) => (v as num).toInt()),
    createdAt: $checkedConvert(
      'created_at',
      (v) => DateTime.parse(v as String),
    ),
    record: $checkedConvert(
      'record',
      (v) => DisciplineVersionRecord.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
}, fieldKeyMap: const {'actorId': 'actor_id', 'createdAt': 'created_at'});

Map<String, dynamic> _$DisciplineRecordVersionToJson(
  DisciplineRecordVersion instance,
) => <String, dynamic>{
  'revision': instance.revision,
  'actor_id': instance.actorId,
  'created_at': instance.createdAt.toIso8601String(),
  'record': instance.record.toJson(),
};
