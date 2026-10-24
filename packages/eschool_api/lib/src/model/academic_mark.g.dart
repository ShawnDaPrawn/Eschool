// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_mark.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicMark _$AcademicMarkFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'AcademicMark',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'id',
        'canonical_task_id',
        'learner_id',
        'teaching_group_id',
        'mark',
        'status',
        'disposition',
        'revision',
        'note',
        'captured_by',
        'moderated_by',
        'moderated_at',
        'question_marks',
      ],
    );
    final val = AcademicMark(
      id: $checkedConvert('id', (v) => (v as num).toInt()),
      canonicalTaskId: $checkedConvert(
        'canonical_task_id',
        (v) => (v as num).toInt(),
      ),
      learnerId: $checkedConvert('learner_id', (v) => (v as num).toInt()),
      teachingGroupId: $checkedConvert(
        'teaching_group_id',
        (v) => (v as num).toInt(),
      ),
      mark: $checkedConvert('mark', (v) => v as String?),
      status: $checkedConvert(
        'status',
        (v) => $enumDecode(
          _$AcademicMarkStatusEnumEnumMap,
          v,
          unknownValue: AcademicMarkStatusEnum.unknownDefaultOpenApi,
        ),
      ),
      disposition: $checkedConvert('disposition', (v) => v as String),
      revision: $checkedConvert('revision', (v) => (v as num).toInt()),
      note: $checkedConvert('note', (v) => v as String?),
      capturedBy: $checkedConvert('captured_by', (v) => (v as num).toInt()),
      moderatedBy: $checkedConvert('moderated_by', (v) => (v as num?)?.toInt()),
      moderatedAt: $checkedConvert('moderated_at', (v) => v as String?),
      questionMarks: $checkedConvert(
        'question_marks',
        (v) => (v as List<dynamic>)
            .map(
              (e) => AcademicStoredQuestion.fromJson(e as Map<String, dynamic>),
            )
            .toList(),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'canonicalTaskId': 'canonical_task_id',
    'learnerId': 'learner_id',
    'teachingGroupId': 'teaching_group_id',
    'capturedBy': 'captured_by',
    'moderatedBy': 'moderated_by',
    'moderatedAt': 'moderated_at',
    'questionMarks': 'question_marks',
  },
);

Map<String, dynamic> _$AcademicMarkToJson(AcademicMark instance) =>
    <String, dynamic>{
      'id': instance.id,
      'canonical_task_id': instance.canonicalTaskId,
      'learner_id': instance.learnerId,
      'teaching_group_id': instance.teachingGroupId,
      'mark': instance.mark,
      'status': _$AcademicMarkStatusEnumEnumMap[instance.status]!,
      'disposition': instance.disposition,
      'revision': instance.revision,
      'note': instance.note,
      'captured_by': instance.capturedBy,
      'moderated_by': instance.moderatedBy,
      'moderated_at': instance.moderatedAt,
      'question_marks': instance.questionMarks.map((e) => e.toJson()).toList(),
    };

const _$AcademicMarkStatusEnumEnumMap = {
  AcademicMarkStatusEnum.captured: 'captured',
  AcademicMarkStatusEnum.moderated: 'moderated',
  AcademicMarkStatusEnum.locked: 'locked',
  AcademicMarkStatusEnum.unknownDefaultOpenApi: 'unknown_default_open_api',
};
