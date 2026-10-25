// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'recognition_present.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

RecognitionPresent _$RecognitionPresentFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'RecognitionPresent',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'operation_id',
            'learner_id',
            'session_year_id',
            'award_definition_id',
            'award_nomination_id',
            'presented_on',
            'event',
          ],
        );
        final val = RecognitionPresent(
          operationId: $checkedConvert('operation_id', (v) => v as String),
          learnerId: $checkedConvert('learner_id', (v) => (v as num).toInt()),
          sessionYearId: $checkedConvert(
            'session_year_id',
            (v) => (v as num).toInt(),
          ),
          awardDefinitionId: $checkedConvert(
            'award_definition_id',
            (v) => (v as num).toInt(),
          ),
          awardNominationId: $checkedConvert(
            'award_nomination_id',
            (v) => (v as num?)?.toInt(),
          ),
          presentedOn: $checkedConvert('presented_on', (v) => v as String),
          event: $checkedConvert('event', (v) => v as String?),
        );
        return val;
      },
      fieldKeyMap: const {
        'operationId': 'operation_id',
        'learnerId': 'learner_id',
        'sessionYearId': 'session_year_id',
        'awardDefinitionId': 'award_definition_id',
        'awardNominationId': 'award_nomination_id',
        'presentedOn': 'presented_on',
      },
    );

Map<String, dynamic> _$RecognitionPresentToJson(RecognitionPresent instance) =>
    <String, dynamic>{
      'operation_id': instance.operationId,
      'learner_id': instance.learnerId,
      'session_year_id': instance.sessionYearId,
      'award_definition_id': instance.awardDefinitionId,
      'award_nomination_id': instance.awardNominationId,
      'presented_on': instance.presentedOn,
      'event': instance.event,
    };
