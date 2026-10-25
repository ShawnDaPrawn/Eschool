// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'learner_note_update.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

LearnerNoteUpdate _$LearnerNoteUpdateFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'LearnerNoteUpdate',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const ['operation_id', 'expected_revision', 'body'],
        );
        final val = LearnerNoteUpdate(
          operationId: $checkedConvert('operation_id', (v) => v as String),
          expectedRevision: $checkedConvert(
            'expected_revision',
            (v) => (v as num).toInt(),
          ),
          body: $checkedConvert('body', (v) => v as String),
          archive: $checkedConvert('archive', (v) => v as bool?),
        );
        return val;
      },
      fieldKeyMap: const {
        'operationId': 'operation_id',
        'expectedRevision': 'expected_revision',
      },
    );

Map<String, dynamic> _$LearnerNoteUpdateToJson(LearnerNoteUpdate instance) =>
    <String, dynamic>{
      'operation_id': instance.operationId,
      'expected_revision': instance.expectedRevision,
      'body': instance.body,
      'archive': ?instance.archive,
    };
