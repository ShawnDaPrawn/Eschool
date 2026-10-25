// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'incident_public_configuration.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

IncidentPublicConfiguration _$IncidentPublicConfigurationFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'IncidentPublicConfiguration',
  json,
  ($checkedConvert) {
    $checkKeys(json, requiredKeys: const ['emergency_guidance', 'categories']);
    final val = IncidentPublicConfiguration(
      emergencyGuidance: $checkedConvert(
        'emergency_guidance',
        (v) => v as String,
      ),
      categories: $checkedConvert(
        'categories',
        (v) => (v as List<dynamic>)
            .map(
              (e) => IncidentPublicConfigurationCategoriesInner.fromJson(
                e as Map<String, dynamic>,
              ),
            )
            .toList(),
      ),
      photoConstraints: $checkedConvert(
        'photo_constraints',
        (v) => v == null
            ? null
            : IncidentPhotoConstraints.fromJson(v as Map<String, dynamic>),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'emergencyGuidance': 'emergency_guidance',
    'photoConstraints': 'photo_constraints',
  },
);

Map<String, dynamic> _$IncidentPublicConfigurationToJson(
  IncidentPublicConfiguration instance,
) => <String, dynamic>{
  'emergency_guidance': instance.emergencyGuidance,
  'categories': instance.categories.map((e) => e.toJson()).toList(),
  'photo_constraints': ?instance.photoConstraints?.toJson(),
};
