// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'support_version.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SupportVersion _$SupportVersionFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('SupportVersion', json, ($checkedConvert) {
  $checkKeys(
    json,
    requiredKeys: const ['revision', 'author_id', 'recorded_at', 'snapshot'],
  );
  final val = SupportVersion(
    revision: $checkedConvert('revision', (v) => (v as num).toInt()),
    authorId: $checkedConvert('author_id', (v) => (v as num).toInt()),
    recordedAt: $checkedConvert(
      'recorded_at',
      (v) => DateTime.parse(v as String),
    ),
    snapshot: $checkedConvert(
      'snapshot',
      (v) => SupportPlanSnapshot.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
}, fieldKeyMap: const {'authorId': 'author_id', 'recordedAt': 'recorded_at'});

Map<String, dynamic> _$SupportVersionToJson(SupportVersion instance) =>
    <String, dynamic>{
      'revision': instance.revision,
      'author_id': instance.authorId,
      'recorded_at': instance.recordedAt.toIso8601String(),
      'snapshot': instance.snapshot.toJson(),
    };
