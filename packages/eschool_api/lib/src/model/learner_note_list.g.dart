// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'learner_note_list.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

LearnerNoteList _$LearnerNoteListFromJson(Map<String, dynamic> json) =>
    $checkedCreate('LearnerNoteList', json, ($checkedConvert) {
      $checkKeys(json, requiredKeys: const ['notes', 'next_after']);
      final val = LearnerNoteList(
        notes: $checkedConvert(
          'notes',
          (v) => (v as List<dynamic>)
              .map(
                (e) => LearnerNoteMetadata.fromJson(e as Map<String, dynamic>),
              )
              .toList(),
        ),
        nextAfter: $checkedConvert('next_after', (v) => (v as num?)?.toInt()),
      );
      return val;
    }, fieldKeyMap: const {'nextAfter': 'next_after'});

Map<String, dynamic> _$LearnerNoteListToJson(LearnerNoteList instance) =>
    <String, dynamic>{
      'notes': instance.notes.map((e) => e.toJson()).toList(),
      'next_after': instance.nextAfter,
    };
