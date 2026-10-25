// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'get_consultation_configuration_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

GetConsultationConfigurationResponse
_$GetConsultationConfigurationResponseFromJson(Map<String, dynamic> json) =>
    $checkedCreate('GetConsultationConfigurationResponse', json, (
      $checkedConvert,
    ) {
      $checkKeys(
        json,
        requiredKeys: const ['error', 'message', 'data', 'code'],
      );
      final val = GetConsultationConfigurationResponse(
        error: $checkedConvert('error', (v) => v as bool),
        message: $checkedConvert('message', (v) => v as String),
        data: $checkedConvert(
          'data',
          (v) => ConsultationConfiguration.fromJson(v as Map<String, dynamic>),
        ),
        code: $checkedConvert('code', (v) => (v as num).toInt()),
      );
      return val;
    });

Map<String, dynamic> _$GetConsultationConfigurationResponseToJson(
  GetConsultationConfigurationResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
