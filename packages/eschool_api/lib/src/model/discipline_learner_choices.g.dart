// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'discipline_learner_choices.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

DisciplineLearnerChoices _$DisciplineLearnerChoicesFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'DisciplineLearnerChoices',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const ['session_year_id', 'page', 'last_page', 'learners'],
    );
    final val = DisciplineLearnerChoices(
      sessionYearId: $checkedConvert(
        'session_year_id',
        (v) => (v as num).toInt(),
      ),
      page: $checkedConvert('page', (v) => (v as num).toInt()),
      lastPage: $checkedConvert('last_page', (v) => (v as num).toInt()),
      learners: $checkedConvert(
        'learners',
        (v) => (v as List<dynamic>)
            .map(
              (e) =>
                  DisciplineLearnerChoice.fromJson(e as Map<String, dynamic>),
            )
            .toList(),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'sessionYearId': 'session_year_id',
    'lastPage': 'last_page',
  },
);

Map<String, dynamic> _$DisciplineLearnerChoicesToJson(
  DisciplineLearnerChoices instance,
) => <String, dynamic>{
  'session_year_id': instance.sessionYearId,
  'page': instance.page,
  'last_page': instance.lastPage,
  'learners': instance.learners.map((e) => e.toJson()).toList(),
};
