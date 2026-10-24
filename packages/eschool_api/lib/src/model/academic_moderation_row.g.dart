// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_moderation_row.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicModerationRow _$AcademicModerationRowFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'AcademicModerationRow',
  json,
  ($checkedConvert) {
    $checkKeys(json, requiredKeys: const ['learner_id', 'expected_revision']);
    final val = AcademicModerationRow(
      learnerId: $checkedConvert('learner_id', (v) => (v as num).toInt()),
      expectedRevision: $checkedConvert(
        'expected_revision',
        (v) => (v as num).toInt(),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'learnerId': 'learner_id',
    'expectedRevision': 'expected_revision',
  },
);

Map<String, dynamic> _$AcademicModerationRowToJson(
  AcademicModerationRow instance,
) => <String, dynamic>{
  'learner_id': instance.learnerId,
  'expected_revision': instance.expectedRevision,
};
