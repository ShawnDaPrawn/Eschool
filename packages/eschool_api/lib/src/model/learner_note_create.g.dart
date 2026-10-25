// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'learner_note_create.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

LearnerNoteCreate _$LearnerNoteCreateFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'LearnerNoteCreate',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'operation_id',
            'session_year_id',
            'visibility',
            'body',
          ],
        );
        final val = LearnerNoteCreate(
          operationId: $checkedConvert('operation_id', (v) => v as String),
          sessionYearId: $checkedConvert(
            'session_year_id',
            (v) => (v as num).toInt(),
          ),
          subjectId: $checkedConvert('subject_id', (v) => (v as num?)?.toInt()),
          visibility: $checkedConvert(
            'visibility',
            (v) => $enumDecode(
              _$LearnerNoteCreateVisibilityEnumEnumMap,
              v,
              unknownValue:
                  LearnerNoteCreateVisibilityEnum.unknownDefaultOpenApi,
            ),
          ),
          body: $checkedConvert('body', (v) => v as String),
        );
        return val;
      },
      fieldKeyMap: const {
        'operationId': 'operation_id',
        'sessionYearId': 'session_year_id',
        'subjectId': 'subject_id',
      },
    );

Map<String, dynamic> _$LearnerNoteCreateToJson(
  LearnerNoteCreate instance,
) => <String, dynamic>{
  'operation_id': instance.operationId,
  'session_year_id': instance.sessionYearId,
  'subject_id': ?instance.subjectId,
  'visibility': _$LearnerNoteCreateVisibilityEnumEnumMap[instance.visibility]!,
  'body': instance.body,
};

const _$LearnerNoteCreateVisibilityEnumEnumMap = {
  LearnerNoteCreateVisibilityEnum.tutor: 'tutor',
  LearnerNoteCreateVisibilityEnum.staff: 'staff',
  LearnerNoteCreateVisibilityEnum.counselling: 'counselling',
  LearnerNoteCreateVisibilityEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
