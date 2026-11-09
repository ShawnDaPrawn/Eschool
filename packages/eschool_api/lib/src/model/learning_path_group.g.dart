// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'learning_path_group.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

LearningPathGroup _$LearningPathGroupFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'LearningPathGroup',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'school_id',
        'reader_id',
        'group_id',
        'subject_id',
        'current_page',
        'last_page',
        'total',
        'learners',
      ],
    );
    final val = LearningPathGroup(
      schoolId: $checkedConvert('school_id', (v) => (v as num).toInt()),
      readerId: $checkedConvert('reader_id', (v) => (v as num).toInt()),
      groupId: $checkedConvert('group_id', (v) => (v as num).toInt()),
      subjectId: $checkedConvert('subject_id', (v) => (v as num).toInt()),
      currentPage: $checkedConvert('current_page', (v) => (v as num).toInt()),
      lastPage: $checkedConvert('last_page', (v) => (v as num).toInt()),
      total: $checkedConvert('total', (v) => (v as num).toInt()),
      learners: $checkedConvert(
        'learners',
        (v) => (v as List<dynamic>)
            .map(
              (e) => LearningPathGroupLearnersInner.fromJson(
                e as Map<String, dynamic>,
              ),
            )
            .toList(),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'schoolId': 'school_id',
    'readerId': 'reader_id',
    'groupId': 'group_id',
    'subjectId': 'subject_id',
    'currentPage': 'current_page',
    'lastPage': 'last_page',
  },
);

Map<String, dynamic> _$LearningPathGroupToJson(LearningPathGroup instance) =>
    <String, dynamic>{
      'school_id': instance.schoolId,
      'reader_id': instance.readerId,
      'group_id': instance.groupId,
      'subject_id': instance.subjectId,
      'current_page': instance.currentPage,
      'last_page': instance.lastPage,
      'total': instance.total,
      'learners': instance.learners.map((e) => e.toJson()).toList(),
    };
