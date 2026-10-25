// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'learner_note_detail.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

LearnerNoteDetail _$LearnerNoteDetailFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'LearnerNoteDetail',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'id',
            'learner_id',
            'subject_id',
            'session_year_id',
            'author_id',
            'author_name',
            'visibility',
            'revision',
            'archived',
            'updated_at',
            'body',
          ],
        );
        final val = LearnerNoteDetail(
          id: $checkedConvert('id', (v) => (v as num).toInt()),
          learnerId: $checkedConvert('learner_id', (v) => (v as num).toInt()),
          subjectId: $checkedConvert('subject_id', (v) => (v as num?)?.toInt()),
          sessionYearId: $checkedConvert(
            'session_year_id',
            (v) => (v as num).toInt(),
          ),
          authorId: $checkedConvert('author_id', (v) => (v as num).toInt()),
          authorName: $checkedConvert('author_name', (v) => v as String),
          visibility: $checkedConvert(
            'visibility',
            (v) => $enumDecode(
              _$LearnerNoteDetailVisibilityEnumEnumMap,
              v,
              unknownValue:
                  LearnerNoteDetailVisibilityEnum.unknownDefaultOpenApi,
            ),
          ),
          revision: $checkedConvert('revision', (v) => (v as num).toInt()),
          archived: $checkedConvert('archived', (v) => v as bool),
          updatedAt: $checkedConvert(
            'updated_at',
            (v) => DateTime.parse(v as String),
          ),
          body: $checkedConvert('body', (v) => v as String),
        );
        return val;
      },
      fieldKeyMap: const {
        'learnerId': 'learner_id',
        'subjectId': 'subject_id',
        'sessionYearId': 'session_year_id',
        'authorId': 'author_id',
        'authorName': 'author_name',
        'updatedAt': 'updated_at',
      },
    );

Map<String, dynamic> _$LearnerNoteDetailToJson(
  LearnerNoteDetail instance,
) => <String, dynamic>{
  'id': instance.id,
  'learner_id': instance.learnerId,
  'subject_id': instance.subjectId,
  'session_year_id': instance.sessionYearId,
  'author_id': instance.authorId,
  'author_name': instance.authorName,
  'visibility': _$LearnerNoteDetailVisibilityEnumEnumMap[instance.visibility]!,
  'revision': instance.revision,
  'archived': instance.archived,
  'updated_at': instance.updatedAt.toIso8601String(),
  'body': instance.body,
};

const _$LearnerNoteDetailVisibilityEnumEnumMap = {
  LearnerNoteDetailVisibilityEnum.tutor: 'tutor',
  LearnerNoteDetailVisibilityEnum.staff: 'staff',
  LearnerNoteDetailVisibilityEnum.counselling: 'counselling',
  LearnerNoteDetailVisibilityEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
