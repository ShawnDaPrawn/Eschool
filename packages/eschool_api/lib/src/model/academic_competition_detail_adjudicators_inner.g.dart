// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_competition_detail_adjudicators_inner.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicCompetitionDetailAdjudicatorsInner
_$AcademicCompetitionDetailAdjudicatorsInnerFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('AcademicCompetitionDetailAdjudicatorsInner', json, (
  $checkedConvert,
) {
  $checkKeys(json, requiredKeys: const ['user_id', 'name']);
  final val = AcademicCompetitionDetailAdjudicatorsInner(
    userId: $checkedConvert('user_id', (v) => (v as num?)?.toInt()),
    name: $checkedConvert('name', (v) => v as String),
  );
  return val;
}, fieldKeyMap: const {'userId': 'user_id'});

Map<String, dynamic> _$AcademicCompetitionDetailAdjudicatorsInnerToJson(
  AcademicCompetitionDetailAdjudicatorsInner instance,
) => <String, dynamic>{'user_id': instance.userId, 'name': instance.name};
