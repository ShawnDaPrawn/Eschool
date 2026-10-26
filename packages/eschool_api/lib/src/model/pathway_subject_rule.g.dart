// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'pathway_subject_rule.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PathwaySubjectRule _$PathwaySubjectRuleFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'PathwaySubjectRule',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const ['subject_id', 'minimum_bp', 'counts_for_score'],
        );
        final val = PathwaySubjectRule(
          subjectId: $checkedConvert('subject_id', (v) => (v as num).toInt()),
          minimumBp: $checkedConvert('minimum_bp', (v) => (v as num?)?.toInt()),
          countsForScore: $checkedConvert('counts_for_score', (v) => v as bool),
        );
        return val;
      },
      fieldKeyMap: const {
        'subjectId': 'subject_id',
        'minimumBp': 'minimum_bp',
        'countsForScore': 'counts_for_score',
      },
    );

Map<String, dynamic> _$PathwaySubjectRuleToJson(PathwaySubjectRule instance) =>
    <String, dynamic>{
      'subject_id': instance.subjectId,
      'minimum_bp': instance.minimumBp,
      'counts_for_score': instance.countsForScore,
    };
