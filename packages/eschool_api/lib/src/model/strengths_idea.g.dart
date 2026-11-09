// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'strengths_idea.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

StrengthsIdea _$StrengthsIdeaFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'StrengthsIdea',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'career_profile_id',
            'title',
            'summary',
            'because',
            'source_title',
            'source_url',
          ],
        );
        final val = StrengthsIdea(
          careerProfileId: $checkedConvert(
            'career_profile_id',
            (v) => v as String,
          ),
          title: $checkedConvert('title', (v) => v as String),
          summary: $checkedConvert('summary', (v) => v as String),
          because: $checkedConvert('because', (v) => v as String),
          sourceTitle: $checkedConvert('source_title', (v) => v as String),
          sourceUrl: $checkedConvert('source_url', (v) => v as String),
        );
        return val;
      },
      fieldKeyMap: const {
        'careerProfileId': 'career_profile_id',
        'sourceTitle': 'source_title',
        'sourceUrl': 'source_url',
      },
    );

Map<String, dynamic> _$StrengthsIdeaToJson(StrengthsIdea instance) =>
    <String, dynamic>{
      'career_profile_id': instance.careerProfileId,
      'title': instance.title,
      'summary': instance.summary,
      'because': instance.because,
      'source_title': instance.sourceTitle,
      'source_url': instance.sourceUrl,
    };
