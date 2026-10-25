// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'consultation_summary.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ConsultationSummary _$ConsultationSummaryFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'ConsultationSummary',
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
      ],
    );
    final val = ConsultationSummary(
      id: $checkedConvert('id', (v) => (v as num).toInt()),
      revision: $checkedConvert('revision', (v) => (v as num).toInt()),
      status: $checkedConvert(
        'status',
        (v) => $enumDecode(
          _$ConsultationSummaryStatusEnumEnumMap,
          v,
          unknownValue: ConsultationSummaryStatusEnum.unknownDefaultOpenApi,
        ),
      ),
      type: $checkedConvert(
        'type',
        (v) => $enumDecode(
          _$ConsultationSummaryTypeEnumEnumMap,
          v,
          unknownValue: ConsultationSummaryTypeEnum.unknownDefaultOpenApi,
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
          _$ConsultationSummaryResultsVisibilityEnumEnumMap,
          v,
          unknownValue:
              ConsultationSummaryResultsVisibilityEnum.unknownDefaultOpenApi,
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

Map<String, dynamic> _$ConsultationSummaryToJson(
  ConsultationSummary instance,
) => <String, dynamic>{
  'id': instance.id,
  'revision': instance.revision,
  'status': _$ConsultationSummaryStatusEnumEnumMap[instance.status]!,
  'type': _$ConsultationSummaryTypeEnumEnumMap[instance.type]!,
  'title': instance.title,
  'instructions': instance.instructions,
  'session_year_id': instance.sessionYearId,
  'anonymous': instance.anonymous,
  'results_visibility':
      _$ConsultationSummaryResultsVisibilityEnumEnumMap[instance
          .resultsVisibility]!,
  'opens_at': instance.opensAt.toIso8601String(),
  'closes_at': instance.closesAt.toIso8601String(),
  'ended_at': instance.endedAt?.toIso8601String(),
  'writable': instance.writable,
};

const _$ConsultationSummaryStatusEnumEnumMap = {
  ConsultationSummaryStatusEnum.draft: 'draft',
  ConsultationSummaryStatusEnum.scheduled: 'scheduled',
  ConsultationSummaryStatusEnum.open: 'open',
  ConsultationSummaryStatusEnum.closed: 'closed',
  ConsultationSummaryStatusEnum.cancelled: 'cancelled',
  ConsultationSummaryStatusEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};

const _$ConsultationSummaryTypeEnumEnumMap = {
  ConsultationSummaryTypeEnum.poll: 'poll',
  ConsultationSummaryTypeEnum.survey: 'survey',
  ConsultationSummaryTypeEnum.election: 'election',
  ConsultationSummaryTypeEnum.unknownDefaultOpenApi: 'unknown_default_open_api',
};

const _$ConsultationSummaryResultsVisibilityEnumEnumMap = {
  ConsultationSummaryResultsVisibilityEnum.afterClose: 'after_close',
  ConsultationSummaryResultsVisibilityEnum.publicLive: 'public_live',
  ConsultationSummaryResultsVisibilityEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
