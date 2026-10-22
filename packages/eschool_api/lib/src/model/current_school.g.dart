// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'current_school.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

CurrentSchool _$CurrentSchoolFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'CurrentSchool',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'id',
            'name',
            'short_name',
            'crest_url',
            'tagline',
            'branding',
            'timezone',
            'session_year',
          ],
        );
        final val = CurrentSchool(
          id: $checkedConvert('id', (v) => (v as num).toInt()),
          name: $checkedConvert('name', (v) => v as String),
          shortName: $checkedConvert('short_name', (v) => v as String?),
          crestUrl: $checkedConvert('crest_url', (v) => v as String?),
          tagline: $checkedConvert('tagline', (v) => v as String?),
          branding: $checkedConvert(
            'branding',
            (v) => Branding.fromJson(v as Map<String, dynamic>),
          ),
          timezone: $checkedConvert('timezone', (v) => v as String),
          sessionYear: $checkedConvert(
            'session_year',
            (v) => v == null
                ? null
                : SessionYear.fromJson(v as Map<String, dynamic>),
          ),
        );
        return val;
      },
      fieldKeyMap: const {
        'shortName': 'short_name',
        'crestUrl': 'crest_url',
        'sessionYear': 'session_year',
      },
    );

Map<String, dynamic> _$CurrentSchoolToJson(CurrentSchool instance) =>
    <String, dynamic>{
      'id': instance.id,
      'name': instance.name,
      'short_name': instance.shortName,
      'crest_url': instance.crestUrl,
      'tagline': instance.tagline,
      'branding': instance.branding.toJson(),
      'timezone': instance.timezone,
      'session_year': instance.sessionYear?.toJson(),
    };
