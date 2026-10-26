// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'pathway_configuration_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PathwayConfigurationResponse _$PathwayConfigurationResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('PathwayConfigurationResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = PathwayConfigurationResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => PathwayConfiguration.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$PathwayConfigurationResponseToJson(
  PathwayConfigurationResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
