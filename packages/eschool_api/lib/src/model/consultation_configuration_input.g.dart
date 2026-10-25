// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'consultation_configuration_input.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ConsultationConfigurationInput _$ConsultationConfigurationInputFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'ConsultationConfigurationInput',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'operation_id',
        'revision',
        'session_year_id',
        'type',
        'title',
        'opens_at',
        'closes_at',
        'anonymous',
        'results_visibility',
        'audience',
        'questions',
      ],
    );
    final val = ConsultationConfigurationInput(
      operationId: $checkedConvert('operation_id', (v) => v as String),
      revision: $checkedConvert('revision', (v) => (v as num).toInt()),
      sessionYearId: $checkedConvert(
        'session_year_id',
        (v) => (v as num).toInt(),
      ),
      type: $checkedConvert(
        'type',
        (v) => $enumDecode(
          _$ConsultationConfigurationInputTypeEnumEnumMap,
          v,
          unknownValue:
              ConsultationConfigurationInputTypeEnum.unknownDefaultOpenApi,
        ),
      ),
      title: $checkedConvert('title', (v) => v as String),
      instructions: $checkedConvert('instructions', (v) => v as String?),
      opensAt: $checkedConvert('opens_at', (v) => DateTime.parse(v as String)),
      closesAt: $checkedConvert(
        'closes_at',
        (v) => DateTime.parse(v as String),
      ),
      anonymous: $checkedConvert('anonymous', (v) => v as bool),
      resultsVisibility: $checkedConvert(
        'results_visibility',
        (v) => $enumDecode(
          _$ConsultationConfigurationInputResultsVisibilityEnumEnumMap,
          v,
          unknownValue: ConsultationConfigurationInputResultsVisibilityEnum
              .unknownDefaultOpenApi,
        ),
      ),
      audience: $checkedConvert(
        'audience',
        (v) => ConsultationAudienceInput.fromJson(v as Map<String, dynamic>),
      ),
      questions: $checkedConvert(
        'questions',
        (v) => (v as List<dynamic>)
            .map(
              (e) =>
                  ConsultationQuestionInput.fromJson(e as Map<String, dynamic>),
            )
            .toList(),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'operationId': 'operation_id',
    'sessionYearId': 'session_year_id',
    'opensAt': 'opens_at',
    'closesAt': 'closes_at',
    'resultsVisibility': 'results_visibility',
  },
);

Map<String, dynamic> _$ConsultationConfigurationInputToJson(
  ConsultationConfigurationInput instance,
) => <String, dynamic>{
  'operation_id': instance.operationId,
  'revision': instance.revision,
  'session_year_id': instance.sessionYearId,
  'type': _$ConsultationConfigurationInputTypeEnumEnumMap[instance.type]!,
  'title': instance.title,
  'instructions': ?instance.instructions,
  'opens_at': instance.opensAt.toIso8601String(),
  'closes_at': instance.closesAt.toIso8601String(),
  'anonymous': instance.anonymous,
  'results_visibility':
      _$ConsultationConfigurationInputResultsVisibilityEnumEnumMap[instance
          .resultsVisibility]!,
  'audience': instance.audience.toJson(),
  'questions': instance.questions.map((e) => e.toJson()).toList(),
};

const _$ConsultationConfigurationInputTypeEnumEnumMap = {
  ConsultationConfigurationInputTypeEnum.poll: 'poll',
  ConsultationConfigurationInputTypeEnum.survey: 'survey',
  ConsultationConfigurationInputTypeEnum.election: 'election',
  ConsultationConfigurationInputTypeEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};

const _$ConsultationConfigurationInputResultsVisibilityEnumEnumMap = {
  ConsultationConfigurationInputResultsVisibilityEnum.afterClose: 'after_close',
  ConsultationConfigurationInputResultsVisibilityEnum.publicLive: 'public_live',
  ConsultationConfigurationInputResultsVisibilityEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
