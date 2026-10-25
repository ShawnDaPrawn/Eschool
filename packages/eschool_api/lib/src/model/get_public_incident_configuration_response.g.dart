// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'get_public_incident_configuration_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

GetPublicIncidentConfigurationResponse
_$GetPublicIncidentConfigurationResponseFromJson(Map<String, dynamic> json) =>
    $checkedCreate('GetPublicIncidentConfigurationResponse', json, (
      $checkedConvert,
    ) {
      $checkKeys(
        json,
        requiredKeys: const ['error', 'message', 'data', 'code'],
      );
      final val = GetPublicIncidentConfigurationResponse(
        error: $checkedConvert('error', (v) => v as bool),
        message: $checkedConvert('message', (v) => v as String),
        data: $checkedConvert(
          'data',
          (v) =>
              IncidentPublicConfiguration.fromJson(v as Map<String, dynamic>),
        ),
        code: $checkedConvert('code', (v) => (v as num).toInt()),
      );
      return val;
    });

Map<String, dynamic> _$GetPublicIncidentConfigurationResponseToJson(
  GetPublicIncidentConfigurationResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
