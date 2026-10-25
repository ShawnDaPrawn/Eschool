// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'incident_identify_input.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

IncidentIdentifyInput _$IncidentIdentifyInputFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'IncidentIdentifyInput',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const ['secret', 'revision', 'identify_consent'],
    );
    final val = IncidentIdentifyInput(
      secret: $checkedConvert('secret', (v) => v as String),
      revision: $checkedConvert('revision', (v) => (v as num).toInt()),
      identifyConsent: $checkedConvert('identify_consent', (v) => v as bool),
      learnerId: $checkedConvert('learner_id', (v) => (v as num?)?.toInt()),
    );
    return val;
  },
  fieldKeyMap: const {
    'identifyConsent': 'identify_consent',
    'learnerId': 'learner_id',
  },
);

Map<String, dynamic> _$IncidentIdentifyInputToJson(
  IncidentIdentifyInput instance,
) => <String, dynamic>{
  'secret': instance.secret,
  'revision': instance.revision,
  'identify_consent': instance.identifyConsent,
  'learner_id': ?instance.learnerId,
};
