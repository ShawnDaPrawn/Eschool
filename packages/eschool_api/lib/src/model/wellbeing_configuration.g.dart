// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'wellbeing_configuration.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

WellbeingConfiguration _$WellbeingConfigurationFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'WellbeingConfiguration',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'professional_choices',
        'professionals_last_page',
        'learner_choices',
        'learners_last_page',
        'categories',
        'categories_last_page',
        'page',
      ],
    );
    final val = WellbeingConfiguration(
      professionalChoices: $checkedConvert(
        'professional_choices',
        (v) => (v as List<dynamic>)
            .map(
              (e) => WellbeingNamedChoice.fromJson(e as Map<String, dynamic>),
            )
            .toList(),
      ),
      professionalsLastPage: $checkedConvert(
        'professionals_last_page',
        (v) => (v as num).toInt(),
      ),
      learnerChoices: $checkedConvert(
        'learner_choices',
        (v) => (v as List<dynamic>)
            .map(
              (e) => WellbeingLearnerChoice.fromJson(e as Map<String, dynamic>),
            )
            .toList(),
      ),
      learnersLastPage: $checkedConvert(
        'learners_last_page',
        (v) => (v as num).toInt(),
      ),
      categories: $checkedConvert(
        'categories',
        (v) => (v as List<dynamic>)
            .map((e) => WellbeingCategory.fromJson(e as Map<String, dynamic>))
            .toList(),
      ),
      categoriesLastPage: $checkedConvert(
        'categories_last_page',
        (v) => (v as num).toInt(),
      ),
      page: $checkedConvert('page', (v) => (v as num).toInt()),
    );
    return val;
  },
  fieldKeyMap: const {
    'professionalChoices': 'professional_choices',
    'professionalsLastPage': 'professionals_last_page',
    'learnerChoices': 'learner_choices',
    'learnersLastPage': 'learners_last_page',
    'categoriesLastPage': 'categories_last_page',
  },
);

Map<String, dynamic> _$WellbeingConfigurationToJson(
  WellbeingConfiguration instance,
) => <String, dynamic>{
  'professional_choices': instance.professionalChoices
      .map((e) => e.toJson())
      .toList(),
  'professionals_last_page': instance.professionalsLastPage,
  'learner_choices': instance.learnerChoices.map((e) => e.toJson()).toList(),
  'learners_last_page': instance.learnersLastPage,
  'categories': instance.categories.map((e) => e.toJson()).toList(),
  'categories_last_page': instance.categoriesLastPage,
  'page': instance.page,
};
