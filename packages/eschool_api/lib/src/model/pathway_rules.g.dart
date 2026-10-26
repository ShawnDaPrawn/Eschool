// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'pathway_rules.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PathwayRules _$PathwayRulesFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'PathwayRules',
  json,
  ($checkedConvert) {
    final val = PathwayRules(
      subjects: $checkedConvert(
        'subjects',
        (v) => (v as List<dynamic>?)
            ?.map((e) => PathwaySubjectRule.fromJson(e as Map<String, dynamic>))
            .toList(),
      ),
      bands: $checkedConvert(
        'bands',
        (v) => (v as List<dynamic>?)
            ?.map((e) => PathwayScoreBand.fromJson(e as Map<String, dynamic>))
            .toList(),
      ),
      bestN: $checkedConvert('best_n', (v) => (v as num?)?.toInt()),
      minimumScore: $checkedConvert(
        'minimum_score',
        (v) => (v as num?)?.toInt(),
      ),
      compulsorySubjectIds: $checkedConvert(
        'compulsory_subject_ids',
        (v) => (v as List<dynamic>?)?.map((e) => (e as num).toInt()).toList(),
      ),
      electiveGroups: $checkedConvert(
        'elective_groups',
        (v) => (v as List<dynamic>?)
            ?.map(
              (e) => PathwayElectiveGroup.fromJson(e as Map<String, dynamic>),
            )
            .toList(),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'bestN': 'best_n',
    'minimumScore': 'minimum_score',
    'compulsorySubjectIds': 'compulsory_subject_ids',
    'electiveGroups': 'elective_groups',
  },
);

Map<String, dynamic> _$PathwayRulesToJson(
  PathwayRules instance,
) => <String, dynamic>{
  'subjects': ?instance.subjects?.map((e) => e.toJson()).toList(),
  'bands': ?instance.bands?.map((e) => e.toJson()).toList(),
  'best_n': ?instance.bestN,
  'minimum_score': ?instance.minimumScore,
  'compulsory_subject_ids': ?instance.compulsorySubjectIds,
  'elective_groups': ?instance.electiveGroups?.map((e) => e.toJson()).toList(),
};
