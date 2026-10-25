// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'recognition_outside_award.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

RecognitionOutsideAward _$RecognitionOutsideAwardFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'RecognitionOutsideAward',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'operation_id',
        'learner_id',
        'session_year_id',
        'title',
        'criteria',
        'outside_organisation',
        'presented_on',
        'event',
      ],
    );
    final val = RecognitionOutsideAward(
      operationId: $checkedConvert('operation_id', (v) => v as String),
      learnerId: $checkedConvert('learner_id', (v) => (v as num).toInt()),
      sessionYearId: $checkedConvert(
        'session_year_id',
        (v) => (v as num).toInt(),
      ),
      title: $checkedConvert('title', (v) => v as String),
      criteria: $checkedConvert('criteria', (v) => v as String),
      outsideOrganisation: $checkedConvert(
        'outside_organisation',
        (v) => v as String,
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
    'outsideOrganisation': 'outside_organisation',
    'presentedOn': 'presented_on',
  },
);

Map<String, dynamic> _$RecognitionOutsideAwardToJson(
  RecognitionOutsideAward instance,
) => <String, dynamic>{
  'operation_id': instance.operationId,
  'learner_id': instance.learnerId,
  'session_year_id': instance.sessionYearId,
  'title': instance.title,
  'criteria': instance.criteria,
  'outside_organisation': instance.outsideOrganisation,
  'presented_on': instance.presentedOn,
  'event': instance.event,
};
