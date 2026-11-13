// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_competition_detail_awards_inner.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicCompetitionDetailAwardsInner
_$AcademicCompetitionDetailAwardsInnerFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'AcademicCompetitionDetailAwardsInner',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'learner_id',
            'basis',
            'award_presentation_id',
            'title',
          ],
        );
        final val = AcademicCompetitionDetailAwardsInner(
          learnerId: $checkedConvert('learner_id', (v) => (v as num).toInt()),
          basis: $checkedConvert(
            'basis',
            (v) => $enumDecode(
              _$AcademicCompetitionDetailAwardsInnerBasisEnumEnumMap,
              v,
              unknownValue: AcademicCompetitionDetailAwardsInnerBasisEnum
                  .unknownDefaultOpenApi,
            ),
          ),
          awardPresentationId: $checkedConvert(
            'award_presentation_id',
            (v) => (v as num).toInt(),
          ),
          title: $checkedConvert('title', (v) => v as String),
        );
        return val;
      },
      fieldKeyMap: const {
        'learnerId': 'learner_id',
        'awardPresentationId': 'award_presentation_id',
      },
    );

Map<String, dynamic> _$AcademicCompetitionDetailAwardsInnerToJson(
  AcademicCompetitionDetailAwardsInner instance,
) => <String, dynamic>{
  'learner_id': instance.learnerId,
  'basis':
      _$AcademicCompetitionDetailAwardsInnerBasisEnumEnumMap[instance.basis]!,
  'award_presentation_id': instance.awardPresentationId,
  'title': instance.title,
};

const _$AcademicCompetitionDetailAwardsInnerBasisEnumEnumMap = {
  AcademicCompetitionDetailAwardsInnerBasisEnum.participation: 'participation',
  AcademicCompetitionDetailAwardsInnerBasisEnum.placing: 'placing',
  AcademicCompetitionDetailAwardsInnerBasisEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
