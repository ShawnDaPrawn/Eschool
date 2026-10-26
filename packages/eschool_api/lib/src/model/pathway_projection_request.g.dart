// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'pathway_projection_request.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PathwayProjectionRequest _$PathwayProjectionRequestFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'PathwayProjectionRequest',
  json,
  ($checkedConvert) {
    $checkKeys(json, requiredKeys: const ['learner_id', 'revision', 'source']);
    final val = PathwayProjectionRequest(
      learnerId: $checkedConvert('learner_id', (v) => (v as num).toInt()),
      revision: $checkedConvert('revision', (v) => (v as num).toInt()),
      source_: $checkedConvert(
        'source',
        (v) => $enumDecode(
          _$PathwayProjectionRequestSource_EnumEnumMap,
          v,
          unknownValue:
              PathwayProjectionRequestSource_Enum.unknownDefaultOpenApi,
        ),
      ),
      sessionYearId: $checkedConvert(
        'session_year_id',
        (v) => (v as num?)?.toInt(),
      ),
      termId: $checkedConvert('term_id', (v) => (v as num?)?.toInt()),
      marks: $checkedConvert(
        'marks',
        (v) => (v as List<dynamic>?)
            ?.map((e) => PathwayMark.fromJson(e as Map<String, dynamic>))
            .toList(),
      ),
      subjectIds: $checkedConvert(
        'subject_ids',
        (v) => (v as List<dynamic>?)?.map((e) => (e as num).toInt()).toList(),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'learnerId': 'learner_id',
    'source_': 'source',
    'sessionYearId': 'session_year_id',
    'termId': 'term_id',
    'subjectIds': 'subject_ids',
  },
);

Map<String, dynamic> _$PathwayProjectionRequestToJson(
  PathwayProjectionRequest instance,
) => <String, dynamic>{
  'learner_id': instance.learnerId,
  'revision': instance.revision,
  'source': _$PathwayProjectionRequestSource_EnumEnumMap[instance.source_]!,
  'session_year_id': ?instance.sessionYearId,
  'term_id': ?instance.termId,
  'marks': ?instance.marks?.map((e) => e.toJson()).toList(),
  'subject_ids': ?instance.subjectIds,
};

const _$PathwayProjectionRequestSource_EnumEnumMap = {
  PathwayProjectionRequestSource_Enum.released: 'released',
  PathwayProjectionRequestSource_Enum.scenario: 'scenario',
  PathwayProjectionRequestSource_Enum.selection: 'selection',
  PathwayProjectionRequestSource_Enum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
