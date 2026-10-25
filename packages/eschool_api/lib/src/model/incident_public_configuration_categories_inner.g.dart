// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'incident_public_configuration_categories_inner.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

IncidentPublicConfigurationCategoriesInner
_$IncidentPublicConfigurationCategoriesInnerFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('IncidentPublicConfigurationCategoriesInner', json, (
  $checkedConvert,
) {
  $checkKeys(json, requiredKeys: const ['id', 'name', 'emergency_guidance']);
  final val = IncidentPublicConfigurationCategoriesInner(
    id: $checkedConvert('id', (v) => (v as num).toInt()),
    name: $checkedConvert('name', (v) => v as String),
    emergencyGuidance: $checkedConvert(
      'emergency_guidance',
      (v) => v as String,
    ),
  );
  return val;
}, fieldKeyMap: const {'emergencyGuidance': 'emergency_guidance'});

Map<String, dynamic> _$IncidentPublicConfigurationCategoriesInnerToJson(
  IncidentPublicConfigurationCategoriesInner instance,
) => <String, dynamic>{
  'id': instance.id,
  'name': instance.name,
  'emergency_guidance': instance.emergencyGuidance,
};
