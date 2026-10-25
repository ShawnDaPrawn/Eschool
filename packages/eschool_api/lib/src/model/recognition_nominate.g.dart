// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'recognition_nominate.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

RecognitionNominate _$RecognitionNominateFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'RecognitionNominate',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'operation_id',
            'session_year_id',
            'grade_level_id',
            'learner_id',
            'award_definition_id',
            'reason',
          ],
        );
        final val = RecognitionNominate(
          operationId: $checkedConvert('operation_id', (v) => v as String),
          sessionYearId: $checkedConvert(
            'session_year_id',
            (v) => (v as num).toInt(),
          ),
          gradeLevelId: $checkedConvert(
            'grade_level_id',
            (v) => (v as num).toInt(),
          ),
          learnerId: $checkedConvert('learner_id', (v) => (v as num).toInt()),
          awardDefinitionId: $checkedConvert(
            'award_definition_id',
            (v) => (v as num).toInt(),
          ),
          reason: $checkedConvert('reason', (v) => v as String),
        );
        return val;
      },
      fieldKeyMap: const {
        'operationId': 'operation_id',
        'sessionYearId': 'session_year_id',
        'gradeLevelId': 'grade_level_id',
        'learnerId': 'learner_id',
        'awardDefinitionId': 'award_definition_id',
      },
    );

Map<String, dynamic> _$RecognitionNominateToJson(
  RecognitionNominate instance,
) => <String, dynamic>{
  'operation_id': instance.operationId,
  'session_year_id': instance.sessionYearId,
  'grade_level_id': instance.gradeLevelId,
  'learner_id': instance.learnerId,
  'award_definition_id': instance.awardDefinitionId,
  'reason': instance.reason,
};
