// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'learner_note_history.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

LearnerNoteHistory _$LearnerNoteHistoryFromJson(Map<String, dynamic> json) =>
    $checkedCreate('LearnerNoteHistory', json, ($checkedConvert) {
      $checkKeys(json, requiredKeys: const ['note_id', 'revisions']);
      final val = LearnerNoteHistory(
        noteId: $checkedConvert('note_id', (v) => (v as num).toInt()),
        revisions: $checkedConvert(
          'revisions',
          (v) => (v as List<dynamic>)
              .map(
                (e) => LearnerNoteRevision.fromJson(e as Map<String, dynamic>),
              )
              .toList(),
        ),
      );
      return val;
    }, fieldKeyMap: const {'noteId': 'note_id'});

Map<String, dynamic> _$LearnerNoteHistoryToJson(LearnerNoteHistory instance) =>
    <String, dynamic>{
      'note_id': instance.noteId,
      'revisions': instance.revisions.map((e) => e.toJson()).toList(),
    };
