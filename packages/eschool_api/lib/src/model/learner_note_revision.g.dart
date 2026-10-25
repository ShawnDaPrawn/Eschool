// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'learner_note_revision.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

LearnerNoteRevision _$LearnerNoteRevisionFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'LearnerNoteRevision',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'revision',
            'author_id',
            'author_name',
            'body',
            'archived',
            'created_at',
          ],
        );
        final val = LearnerNoteRevision(
          revision: $checkedConvert('revision', (v) => (v as num).toInt()),
          authorId: $checkedConvert('author_id', (v) => (v as num).toInt()),
          authorName: $checkedConvert('author_name', (v) => v as String),
          body: $checkedConvert('body', (v) => v as String),
          archived: $checkedConvert('archived', (v) => v as bool),
          createdAt: $checkedConvert(
            'created_at',
            (v) => DateTime.parse(v as String),
          ),
        );
        return val;
      },
      fieldKeyMap: const {
        'authorId': 'author_id',
        'authorName': 'author_name',
        'createdAt': 'created_at',
      },
    );

Map<String, dynamic> _$LearnerNoteRevisionToJson(
  LearnerNoteRevision instance,
) => <String, dynamic>{
  'revision': instance.revision,
  'author_id': instance.authorId,
  'author_name': instance.authorName,
  'body': instance.body,
  'archived': instance.archived,
  'created_at': instance.createdAt.toIso8601String(),
};
