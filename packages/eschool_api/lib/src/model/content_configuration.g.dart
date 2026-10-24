// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'content_configuration.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ContentConfiguration _$ContentConfigurationFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'ContentConfiguration',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'session_year_id',
        'school_years',
        'groups',
        'topics',
        'settings',
        'can_manage',
        'maximum_source_bytes',
      ],
    );
    final val = ContentConfiguration(
      sessionYearId: $checkedConvert(
        'session_year_id',
        (v) => (v as num).toInt(),
      ),
      schoolYears: $checkedConvert(
        'school_years',
        (v) => (v as List<dynamic>)
            .map((e) => ContentYear.fromJson(e as Map<String, dynamic>))
            .toList(),
      ),
      groups: $checkedConvert(
        'groups',
        (v) => (v as List<dynamic>)
            .map((e) => ContentGroup.fromJson(e as Map<String, dynamic>))
            .toList(),
      ),
      topics: $checkedConvert(
        'topics',
        (v) => (v as List<dynamic>)
            .map((e) => ContentTopicChoice.fromJson(e as Map<String, dynamic>))
            .toList(),
      ),
      settings: $checkedConvert(
        'settings',
        (v) => ContentSettings.fromJson(v as Map<String, dynamic>),
      ),
      canManage: $checkedConvert('can_manage', (v) => v as bool),
      maximumSourceBytes: $checkedConvert(
        'maximum_source_bytes',
        (v) => (v as num).toInt(),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'sessionYearId': 'session_year_id',
    'schoolYears': 'school_years',
    'canManage': 'can_manage',
    'maximumSourceBytes': 'maximum_source_bytes',
  },
);

Map<String, dynamic> _$ContentConfigurationToJson(
  ContentConfiguration instance,
) => <String, dynamic>{
  'session_year_id': instance.sessionYearId,
  'school_years': instance.schoolYears.map((e) => e.toJson()).toList(),
  'groups': instance.groups.map((e) => e.toJson()).toList(),
  'topics': instance.topics.map((e) => e.toJson()).toList(),
  'settings': instance.settings.toJson(),
  'can_manage': instance.canManage,
  'maximum_source_bytes': instance.maximumSourceBytes,
};
