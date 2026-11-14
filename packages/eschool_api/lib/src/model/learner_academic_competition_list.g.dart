// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'learner_academic_competition_list.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

LearnerAcademicCompetitionList _$LearnerAcademicCompetitionListFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('LearnerAcademicCompetitionList', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['items']);
  final val = LearnerAcademicCompetitionList(
    items: $checkedConvert(
      'items',
      (v) => (v as List<dynamic>)
          .map(
            (e) =>
                LearnerAcademicCompetition.fromJson(e as Map<String, dynamic>),
          )
          .toList(),
    ),
  );
  return val;
});

Map<String, dynamic> _$LearnerAcademicCompetitionListToJson(
  LearnerAcademicCompetitionList instance,
) => <String, dynamic>{'items': instance.items.map((e) => e.toJson()).toList()};
