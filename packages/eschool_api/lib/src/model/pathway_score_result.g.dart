// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'pathway_score_result.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PathwayScoreResult _$PathwayScoreResultFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'PathwayScoreResult',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'projected_score',
            'known_subtotal',
            'minimum_score',
            'status',
            'missing_subject_ids',
            'below_minimum_subject_ids',
            'counted_subject_ids',
            'subjects',
          ],
        );
        final val = PathwayScoreResult(
          projectedScore: $checkedConvert(
            'projected_score',
            (v) => (v as num?)?.toInt(),
          ),
          knownSubtotal: $checkedConvert(
            'known_subtotal',
            (v) => (v as num).toInt(),
          ),
          minimumScore: $checkedConvert(
            'minimum_score',
            (v) => (v as num).toInt(),
          ),
          status: $checkedConvert(
            'status',
            (v) => $enumDecode(
              _$PathwayScoreResultStatusEnumEnumMap,
              v,
              unknownValue: PathwayScoreResultStatusEnum.unknownDefaultOpenApi,
            ),
          ),
          missingSubjectIds: $checkedConvert(
            'missing_subject_ids',
            (v) => (v as List<dynamic>).map((e) => (e as num).toInt()).toList(),
          ),
          belowMinimumSubjectIds: $checkedConvert(
            'below_minimum_subject_ids',
            (v) => (v as List<dynamic>).map((e) => (e as num).toInt()).toList(),
          ),
          countedSubjectIds: $checkedConvert(
            'counted_subject_ids',
            (v) => (v as List<dynamic>).map((e) => (e as num).toInt()).toList(),
          ),
          subjects: $checkedConvert(
            'subjects',
            (v) => (v as List<dynamic>)
                .map(
                  (e) =>
                      PathwayScoreEvidence.fromJson(e as Map<String, dynamic>),
                )
                .toList(),
          ),
        );
        return val;
      },
      fieldKeyMap: const {
        'projectedScore': 'projected_score',
        'knownSubtotal': 'known_subtotal',
        'minimumScore': 'minimum_score',
        'missingSubjectIds': 'missing_subject_ids',
        'belowMinimumSubjectIds': 'below_minimum_subject_ids',
        'countedSubjectIds': 'counted_subject_ids',
      },
    );

Map<String, dynamic> _$PathwayScoreResultToJson(PathwayScoreResult instance) =>
    <String, dynamic>{
      'projected_score': instance.projectedScore,
      'known_subtotal': instance.knownSubtotal,
      'minimum_score': instance.minimumScore,
      'status': _$PathwayScoreResultStatusEnumEnumMap[instance.status]!,
      'missing_subject_ids': instance.missingSubjectIds,
      'below_minimum_subject_ids': instance.belowMinimumSubjectIds,
      'counted_subject_ids': instance.countedSubjectIds,
      'subjects': instance.subjects.map((e) => e.toJson()).toList(),
    };

const _$PathwayScoreResultStatusEnumEnumMap = {
  PathwayScoreResultStatusEnum.unknown: 'unknown',
  PathwayScoreResultStatusEnum.belowPublishedMinimum: 'below_published_minimum',
  PathwayScoreResultStatusEnum.meetsPublishedMinimum: 'meets_published_minimum',
  PathwayScoreResultStatusEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
