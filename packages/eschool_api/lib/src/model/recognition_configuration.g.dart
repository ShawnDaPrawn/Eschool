// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'recognition_configuration.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

RecognitionConfiguration _$RecognitionConfigurationFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'RecognitionConfiguration',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const ['access', 'settings', 'years', 'grades'],
    );
    final val = RecognitionConfiguration(
      access: $checkedConvert(
        'access',
        (v) => RecognitionAccess.fromJson(v as Map<String, dynamic>),
      ),
      settings: $checkedConvert(
        'settings',
        (v) => RecognitionSettings.fromJson(v as Map<String, dynamic>),
      ),
      years: $checkedConvert(
        'years',
        (v) => (v as List<dynamic>)
            .map((e) => RecognitionYear.fromJson(e as Map<String, dynamic>))
            .toList(),
      ),
      grades: $checkedConvert(
        'grades',
        (v) => (v as List<dynamic>)
            .map(
              (e) =>
                  RecognitionNamedReference.fromJson(e as Map<String, dynamic>),
            )
            .toList(),
      ),
      houses: $checkedConvert(
        'houses',
        (v) => (v as List<dynamic>?)
            ?.map((e) => RecognitionHouse.fromJson(e as Map<String, dynamic>))
            .toList(),
      ),
      pointCategories: $checkedConvert(
        'point_categories',
        (v) => (v as List<dynamic>?)
            ?.map(
              (e) =>
                  RecognitionPointCategory.fromJson(e as Map<String, dynamic>),
            )
            .toList(),
      ),
      awardCategories: $checkedConvert(
        'award_categories',
        (v) => (v as List<dynamic>?)
            ?.map(
              (e) =>
                  RecognitionAwardCategory.fromJson(e as Map<String, dynamic>),
            )
            .toList(),
      ),
      awardDefinitions: $checkedConvert(
        'award_definitions',
        (v) => (v as List<dynamic>?)
            ?.map(
              (e) => RecognitionAwardDefinition.fromJson(
                e as Map<String, dynamic>,
              ),
            )
            .toList(),
      ),
      awardWindows: $checkedConvert(
        'award_windows',
        (v) => (v as List<dynamic>?)
            ?.map(
              (e) => RecognitionAwardWindow.fromJson(e as Map<String, dynamic>),
            )
            .toList(),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'pointCategories': 'point_categories',
    'awardCategories': 'award_categories',
    'awardDefinitions': 'award_definitions',
    'awardWindows': 'award_windows',
  },
);

Map<String, dynamic> _$RecognitionConfigurationToJson(
  RecognitionConfiguration instance,
) => <String, dynamic>{
  'access': instance.access.toJson(),
  'settings': instance.settings.toJson(),
  'years': instance.years.map((e) => e.toJson()).toList(),
  'grades': instance.grades.map((e) => e.toJson()).toList(),
  'houses': ?instance.houses?.map((e) => e.toJson()).toList(),
  'point_categories': ?instance.pointCategories
      ?.map((e) => e.toJson())
      .toList(),
  'award_categories': ?instance.awardCategories
      ?.map((e) => e.toJson())
      .toList(),
  'award_definitions': ?instance.awardDefinitions
      ?.map((e) => e.toJson())
      .toList(),
  'award_windows': ?instance.awardWindows?.map((e) => e.toJson()).toList(),
};
