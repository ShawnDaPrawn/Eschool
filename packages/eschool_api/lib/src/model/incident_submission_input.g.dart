// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'incident_submission_input.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

IncidentSubmissionInput _$IncidentSubmissionInputFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'IncidentSubmissionInput',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'secret',
        'category_id',
        'body',
        'urgency',
        'anonymous',
      ],
    );
    final val = IncidentSubmissionInput(
      secret: $checkedConvert('secret', (v) => v as String),
      categoryId: $checkedConvert('category_id', (v) => (v as num).toInt()),
      body: $checkedConvert('body', (v) => v as String),
      urgency: $checkedConvert(
        'urgency',
        (v) => $enumDecode(
          _$IncidentSubmissionInputUrgencyEnumEnumMap,
          v,
          unknownValue:
              IncidentSubmissionInputUrgencyEnum.unknownDefaultOpenApi,
        ),
      ),
      anonymous: $checkedConvert('anonymous', (v) => v as bool),
      acknowledgedGuidance: $checkedConvert(
        'acknowledged_guidance',
        (v) => v as bool?,
      ),
      identifyConsent: $checkedConvert('identify_consent', (v) => v as bool?),
      learnerId: $checkedConvert('learner_id', (v) => (v as num?)?.toInt()),
    );
    return val;
  },
  fieldKeyMap: const {
    'categoryId': 'category_id',
    'acknowledgedGuidance': 'acknowledged_guidance',
    'identifyConsent': 'identify_consent',
    'learnerId': 'learner_id',
  },
);

Map<String, dynamic> _$IncidentSubmissionInputToJson(
  IncidentSubmissionInput instance,
) => <String, dynamic>{
  'secret': instance.secret,
  'category_id': instance.categoryId,
  'body': instance.body,
  'urgency': _$IncidentSubmissionInputUrgencyEnumEnumMap[instance.urgency]!,
  'anonymous': instance.anonymous,
  'acknowledged_guidance': ?instance.acknowledgedGuidance,
  'identify_consent': ?instance.identifyConsent,
  'learner_id': ?instance.learnerId,
};

const _$IncidentSubmissionInputUrgencyEnumEnumMap = {
  IncidentSubmissionInputUrgencyEnum.routine: 'routine',
  IncidentSubmissionInputUrgencyEnum.urgent: 'urgent',
  IncidentSubmissionInputUrgencyEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
