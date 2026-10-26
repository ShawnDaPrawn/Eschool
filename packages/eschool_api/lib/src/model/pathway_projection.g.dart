// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'pathway_projection.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PathwayProjection _$PathwayProjectionFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'PathwayProjection',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'school_id',
            'reader_id',
            'learner_id',
            'reference_id',
            'revision',
            'kind',
            'source',
            'score',
            'selection',
            'released_evidence',
          ],
        );
        final val = PathwayProjection(
          schoolId: $checkedConvert('school_id', (v) => (v as num).toInt()),
          readerId: $checkedConvert('reader_id', (v) => (v as num).toInt()),
          learnerId: $checkedConvert('learner_id', (v) => (v as num).toInt()),
          referenceId: $checkedConvert('reference_id', (v) => v as String),
          revision: $checkedConvert('revision', (v) => (v as num).toInt()),
          kind: $checkedConvert(
            'kind',
            (v) => $enumDecode(
              _$PathwayProjectionKindEnumEnumMap,
              v,
              unknownValue: PathwayProjectionKindEnum.unknownDefaultOpenApi,
            ),
          ),
          source_: $checkedConvert(
            'source',
            (v) => $enumDecode(
              _$PathwayProjectionSource_EnumEnumMap,
              v,
              unknownValue: PathwayProjectionSource_Enum.unknownDefaultOpenApi,
            ),
          ),
          score: $checkedConvert(
            'score',
            (v) => v == null
                ? null
                : PathwayScoreResult.fromJson(v as Map<String, dynamic>),
          ),
          selection: $checkedConvert(
            'selection',
            (v) => v == null
                ? null
                : PathwaySelectionResult.fromJson(v as Map<String, dynamic>),
          ),
          releasedEvidence: $checkedConvert(
            'released_evidence',
            (v) => (v as List<dynamic>)
                .map(
                  (e) => PathwayReleasedEvidence.fromJson(
                    e as Map<String, dynamic>,
                  ),
                )
                .toList(),
          ),
        );
        return val;
      },
      fieldKeyMap: const {
        'schoolId': 'school_id',
        'readerId': 'reader_id',
        'learnerId': 'learner_id',
        'referenceId': 'reference_id',
        'source_': 'source',
        'releasedEvidence': 'released_evidence',
      },
    );

Map<String, dynamic> _$PathwayProjectionToJson(PathwayProjection instance) =>
    <String, dynamic>{
      'school_id': instance.schoolId,
      'reader_id': instance.readerId,
      'learner_id': instance.learnerId,
      'reference_id': instance.referenceId,
      'revision': instance.revision,
      'kind': _$PathwayProjectionKindEnumEnumMap[instance.kind]!,
      'source': _$PathwayProjectionSource_EnumEnumMap[instance.source_]!,
      'score': instance.score?.toJson(),
      'selection': instance.selection?.toJson(),
      'released_evidence': instance.releasedEvidence
          .map((e) => e.toJson())
          .toList(),
    };

const _$PathwayProjectionKindEnumEnumMap = {
  PathwayProjectionKindEnum.programme: 'programme',
  PathwayProjectionKindEnum.subjectChoiceGuide: 'subject_choice_guide',
  PathwayProjectionKindEnum.unknownDefaultOpenApi: 'unknown_default_open_api',
};

const _$PathwayProjectionSource_EnumEnumMap = {
  PathwayProjectionSource_Enum.released: 'released',
  PathwayProjectionSource_Enum.scenario: 'scenario',
  PathwayProjectionSource_Enum.selection: 'selection',
  PathwayProjectionSource_Enum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
