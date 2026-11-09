// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'learning_path_family_summary.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

LearningPathFamilySummary _$LearningPathFamilySummaryFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'LearningPathFamilySummary',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'school_id',
        'reader_id',
        'learner_id',
        'subjects',
        'suggestion',
      ],
    );
    final val = LearningPathFamilySummary(
      schoolId: $checkedConvert('school_id', (v) => (v as num).toInt()),
      readerId: $checkedConvert('reader_id', (v) => (v as num).toInt()),
      learnerId: $checkedConvert('learner_id', (v) => (v as num).toInt()),
      subjects: $checkedConvert(
        'subjects',
        (v) => (v as List<dynamic>)
            .map(
              (e) => LearningPathFamilySummarySubjectsInner.fromJson(
                e as Map<String, dynamic>,
              ),
            )
            .toList(),
      ),
      suggestion: $checkedConvert('suggestion', (v) => v as String),
    );
    return val;
  },
  fieldKeyMap: const {
    'schoolId': 'school_id',
    'readerId': 'reader_id',
    'learnerId': 'learner_id',
  },
);

Map<String, dynamic> _$LearningPathFamilySummaryToJson(
  LearningPathFamilySummary instance,
) => <String, dynamic>{
  'school_id': instance.schoolId,
  'reader_id': instance.readerId,
  'learner_id': instance.learnerId,
  'subjects': instance.subjects.map((e) => e.toJson()).toList(),
  'suggestion': instance.suggestion,
};
