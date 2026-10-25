// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'weekly_digest_reference.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

WeeklyDigestReference _$WeeklyDigestReferenceFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('WeeklyDigestReference', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['id', 'revision']);
  final val = WeeklyDigestReference(
    id: $checkedConvert('id', (v) => (v as num).toInt()),
    revision: $checkedConvert('revision', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$WeeklyDigestReferenceToJson(
  WeeklyDigestReference instance,
) => <String, dynamic>{'id': instance.id, 'revision': instance.revision};
