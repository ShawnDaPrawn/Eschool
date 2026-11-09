// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'strengths_sections.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

StrengthsSections _$StrengthsSectionsFromJson(Map<String, dynamic> json) =>
    $checkedCreate('StrengthsSections', json, ($checkedConvert) {
      $checkKeys(
        json,
        requiredKeys: const [
          'growing',
          'remembering',
          'explored',
          'activities',
          'reading',
          'might_enjoy',
        ],
      );
      final val = StrengthsSections(
        growing: $checkedConvert(
          'growing',
          (v) => StrengthsTopicSection.fromJson(v as Map<String, dynamic>),
        ),
        remembering: $checkedConvert(
          'remembering',
          (v) => StrengthsTopicSection.fromJson(v as Map<String, dynamic>),
        ),
        explored: $checkedConvert(
          'explored',
          (v) => StrengthsExploredSection.fromJson(v as Map<String, dynamic>),
        ),
        activities: $checkedConvert(
          'activities',
          (v) => StrengthsActivitiesSection.fromJson(v as Map<String, dynamic>),
        ),
        reading: $checkedConvert(
          'reading',
          (v) => StrengthsReadingSection.fromJson(v as Map<String, dynamic>),
        ),
        mightEnjoy: $checkedConvert(
          'might_enjoy',
          (v) => StrengthsIdeasSection.fromJson(v as Map<String, dynamic>),
        ),
      );
      return val;
    }, fieldKeyMap: const {'mightEnjoy': 'might_enjoy'});

Map<String, dynamic> _$StrengthsSectionsToJson(StrengthsSections instance) =>
    <String, dynamic>{
      'growing': instance.growing.toJson(),
      'remembering': instance.remembering.toJson(),
      'explored': instance.explored.toJson(),
      'activities': instance.activities.toJson(),
      'reading': instance.reading.toJson(),
      'might_enjoy': instance.mightEnjoy.toJson(),
    };
