// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'communication_configuration_years_inner.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

CommunicationConfigurationYearsInner
_$CommunicationConfigurationYearsInnerFromJson(Map<String, dynamic> json) =>
    $checkedCreate('CommunicationConfigurationYearsInner', json, (
      $checkedConvert,
    ) {
      $checkKeys(json, requiredKeys: const ['id', 'name']);
      final val = CommunicationConfigurationYearsInner(
        id: $checkedConvert('id', (v) => (v as num).toInt()),
        name: $checkedConvert('name', (v) => v as String),
      );
      return val;
    });

Map<String, dynamic> _$CommunicationConfigurationYearsInnerToJson(
  CommunicationConfigurationYearsInner instance,
) => <String, dynamic>{'id': instance.id, 'name': instance.name};
