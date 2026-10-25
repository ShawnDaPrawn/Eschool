// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'consultation_configuration.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ConsultationConfiguration _$ConsultationConfigurationFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'ConsultationConfiguration',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'id',
        'revision',
        'status',
        'type',
        'title',
        'instructions',
        'session_year_id',
        'anonymous',
        'results_visibility',
        'opens_at',
        'closes_at',
        'ended_at',
        'writable',
        'audience',
        'questions',
      ],
    );
    final val = ConsultationConfiguration(
      id: $checkedConvert('id', (v) => (v as num).toInt()),
      revision: $checkedConvert('revision', (v) => (v as num).toInt()),
      status: $checkedConvert(
        'status',
        (v) => $enumDecode(
          _$ConsultationConfigurationStatusEnumEnumMap,
          v,
          unknownValue:
              ConsultationConfigurationStatusEnum.unknownDefaultOpenApi,
        ),
      ),
      type: $checkedConvert(
        'type',
        (v) => $enumDecode(
          _$ConsultationConfigurationTypeEnumEnumMap,
          v,
          unknownValue: ConsultationConfigurationTypeEnum.unknownDefaultOpenApi,
        ),
      ),
      title: $checkedConvert('title', (v) => v as String),
      instructions: $checkedConvert('instructions', (v) => v as String),
      sessionYearId: $checkedConvert(
        'session_year_id',
        (v) => (v as num).toInt(),
      ),
      anonymous: $checkedConvert('anonymous', (v) => v as bool),
      resultsVisibility: $checkedConvert(
        'results_visibility',
        (v) => $enumDecode(
          _$ConsultationConfigurationResultsVisibilityEnumEnumMap,
          v,
          unknownValue: ConsultationConfigurationResultsVisibilityEnum
              .unknownDefaultOpenApi,
        ),
      ),
      opensAt: $checkedConvert('opens_at', (v) => DateTime.parse(v as String)),
      closesAt: $checkedConvert(
        'closes_at',
        (v) => DateTime.parse(v as String),
      ),
      endedAt: $checkedConvert(
        'ended_at',
        (v) => v == null ? null : DateTime.parse(v as String),
      ),
      writable: $checkedConvert('writable', (v) => v as bool),
      audience: $checkedConvert(
        'audience',
        (v) => ConsultationAudience.fromJson(v as Map<String, dynamic>),
      ),
      questions: $checkedConvert(
        'questions',
        (v) => (v as List<dynamic>)
            .map(
              (e) => ConsultationQuestion.fromJson(e as Map<String, dynamic>),
            )
            .toList(),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'sessionYearId': 'session_year_id',
    'resultsVisibility': 'results_visibility',
    'opensAt': 'opens_at',
    'closesAt': 'closes_at',
    'endedAt': 'ended_at',
  },
);

Map<String, dynamic> _$ConsultationConfigurationToJson(
  ConsultationConfiguration instance,
) => <String, dynamic>{
  'id': instance.id,
  'revision': instance.revision,
  'status': _$ConsultationConfigurationStatusEnumEnumMap[instance.status]!,
  'type': _$ConsultationConfigurationTypeEnumEnumMap[instance.type]!,
  'title': instance.title,
  'instructions': instance.instructions,
  'session_year_id': instance.sessionYearId,
  'anonymous': instance.anonymous,
  'results_visibility':
      _$ConsultationConfigurationResultsVisibilityEnumEnumMap[instance
          .resultsVisibility]!,
  'opens_at': instance.opensAt.toIso8601String(),
  'closes_at': instance.closesAt.toIso8601String(),
  'ended_at': instance.endedAt?.toIso8601String(),
  'writable': instance.writable,
  'audience': instance.audience.toJson(),
  'questions': instance.questions.map((e) => e.toJson()).toList(),
};

const _$ConsultationConfigurationStatusEnumEnumMap = {
  ConsultationConfigurationStatusEnum.draft: 'draft',
  ConsultationConfigurationStatusEnum.scheduled: 'scheduled',
  ConsultationConfigurationStatusEnum.open: 'open',
  ConsultationConfigurationStatusEnum.closed: 'closed',
  ConsultationConfigurationStatusEnum.cancelled: 'cancelled',
  ConsultationConfigurationStatusEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};

const _$ConsultationConfigurationTypeEnumEnumMap = {
  ConsultationConfigurationTypeEnum.poll: 'poll',
  ConsultationConfigurationTypeEnum.survey: 'survey',
  ConsultationConfigurationTypeEnum.election: 'election',
  ConsultationConfigurationTypeEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};

const _$ConsultationConfigurationResultsVisibilityEnumEnumMap = {
  ConsultationConfigurationResultsVisibilityEnum.afterClose: 'after_close',
  ConsultationConfigurationResultsVisibilityEnum.publicLive: 'public_live',
  ConsultationConfigurationResultsVisibilityEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
