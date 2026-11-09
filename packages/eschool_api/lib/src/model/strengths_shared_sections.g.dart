// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'strengths_shared_sections.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

StrengthsSharedSections _$StrengthsSharedSectionsFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('StrengthsSharedSections', json, ($checkedConvert) {
  final val = StrengthsSharedSections(
    growing: $checkedConvert(
      'growing',
      (v) => v == null
          ? null
          : StrengthsTopicSection.fromJson(v as Map<String, dynamic>),
    ),
    remembering: $checkedConvert(
      'remembering',
      (v) => v == null
          ? null
          : StrengthsTopicSection.fromJson(v as Map<String, dynamic>),
    ),
    explored: $checkedConvert(
      'explored',
      (v) => v == null
          ? null
          : StrengthsExploredSection.fromJson(v as Map<String, dynamic>),
    ),
    activities: $checkedConvert(
      'activities',
      (v) => v == null
          ? null
          : StrengthsActivitiesSection.fromJson(v as Map<String, dynamic>),
    ),
    reading: $checkedConvert(
      'reading',
      (v) => v == null
          ? null
          : StrengthsReadingSection.fromJson(v as Map<String, dynamic>),
    ),
    mightEnjoy: $checkedConvert(
      'might_enjoy',
      (v) => v == null
          ? null
          : StrengthsIdeasSection.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
}, fieldKeyMap: const {'mightEnjoy': 'might_enjoy'});

Map<String, dynamic> _$StrengthsSharedSectionsToJson(
  StrengthsSharedSections instance,
) => <String, dynamic>{
  'growing': ?instance.growing?.toJson(),
  'remembering': ?instance.remembering?.toJson(),
  'explored': ?instance.explored?.toJson(),
  'activities': ?instance.activities?.toJson(),
  'reading': ?instance.reading?.toJson(),
  'might_enjoy': ?instance.mightEnjoy?.toJson(),
};
