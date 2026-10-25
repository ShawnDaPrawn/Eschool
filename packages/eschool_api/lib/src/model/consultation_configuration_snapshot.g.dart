// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'consultation_configuration_snapshot.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ConsultationConfigurationSnapshot _$ConsultationConfigurationSnapshotFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'ConsultationConfigurationSnapshot',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'type',
        'title',
        'audience',
        'anonymous',
        'results_visibility',
        'status',
        'opens_at',
        'closes_at',
        'reason',
        'questions',
      ],
    );
    final val = ConsultationConfigurationSnapshot(
      type: $checkedConvert(
        'type',
        (v) => $enumDecode(
          _$ConsultationConfigurationSnapshotTypeEnumEnumMap,
          v,
          unknownValue:
              ConsultationConfigurationSnapshotTypeEnum.unknownDefaultOpenApi,
        ),
      ),
      title: $checkedConvert('title', (v) => v as String),
      audience: $checkedConvert(
        'audience',
        (v) => ConsultationAudience.fromJson(v as Map<String, dynamic>),
      ),
      anonymous: $checkedConvert('anonymous', (v) => v as bool),
      resultsVisibility: $checkedConvert(
        'results_visibility',
        (v) => $enumDecode(
          _$ConsultationConfigurationSnapshotResultsVisibilityEnumEnumMap,
          v,
          unknownValue: ConsultationConfigurationSnapshotResultsVisibilityEnum
              .unknownDefaultOpenApi,
        ),
      ),
      status: $checkedConvert(
        'status',
        (v) => $enumDecode(
          _$ConsultationConfigurationSnapshotStatusEnumEnumMap,
          v,
          unknownValue:
              ConsultationConfigurationSnapshotStatusEnum.unknownDefaultOpenApi,
        ),
      ),
      opensAt: $checkedConvert('opens_at', (v) => DateTime.parse(v as String)),
      closesAt: $checkedConvert(
        'closes_at',
        (v) => DateTime.parse(v as String),
      ),
      reason: $checkedConvert('reason', (v) => v as String?),
      questions: $checkedConvert(
        'questions',
        (v) => (v as List<dynamic>)
            .map(
              (e) => ConsultationQuestion.fromJson(e as Map<String, dynamic>),
            )
            .toList(),
      ),
      instructions: $checkedConvert('instructions', (v) => v as String?),
      sessionYearId: $checkedConvert(
        'session_year_id',
        (v) => (v as num?)?.toInt(),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'resultsVisibility': 'results_visibility',
    'opensAt': 'opens_at',
    'closesAt': 'closes_at',
    'sessionYearId': 'session_year_id',
  },
);

Map<String, dynamic> _$ConsultationConfigurationSnapshotToJson(
  ConsultationConfigurationSnapshot instance,
) => <String, dynamic>{
  'type': _$ConsultationConfigurationSnapshotTypeEnumEnumMap[instance.type]!,
  'title': instance.title,
  'audience': instance.audience.toJson(),
  'anonymous': instance.anonymous,
  'results_visibility':
      _$ConsultationConfigurationSnapshotResultsVisibilityEnumEnumMap[instance
          .resultsVisibility]!,
  'status':
      _$ConsultationConfigurationSnapshotStatusEnumEnumMap[instance.status]!,
  'opens_at': instance.opensAt.toIso8601String(),
  'closes_at': instance.closesAt.toIso8601String(),
  'reason': instance.reason,
  'questions': instance.questions.map((e) => e.toJson()).toList(),
  'instructions': ?instance.instructions,
  'session_year_id': ?instance.sessionYearId,
};

const _$ConsultationConfigurationSnapshotTypeEnumEnumMap = {
  ConsultationConfigurationSnapshotTypeEnum.poll: 'poll',
  ConsultationConfigurationSnapshotTypeEnum.survey: 'survey',
  ConsultationConfigurationSnapshotTypeEnum.election: 'election',
  ConsultationConfigurationSnapshotTypeEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};

const _$ConsultationConfigurationSnapshotResultsVisibilityEnumEnumMap = {
  ConsultationConfigurationSnapshotResultsVisibilityEnum.afterClose:
      'after_close',
  ConsultationConfigurationSnapshotResultsVisibilityEnum.publicLive:
      'public_live',
  ConsultationConfigurationSnapshotResultsVisibilityEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};

const _$ConsultationConfigurationSnapshotStatusEnumEnumMap = {
  ConsultationConfigurationSnapshotStatusEnum.draft: 'draft',
  ConsultationConfigurationSnapshotStatusEnum.scheduled: 'scheduled',
  ConsultationConfigurationSnapshotStatusEnum.open: 'open',
  ConsultationConfigurationSnapshotStatusEnum.closed: 'closed',
  ConsultationConfigurationSnapshotStatusEnum.cancelled: 'cancelled',
  ConsultationConfigurationSnapshotStatusEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
