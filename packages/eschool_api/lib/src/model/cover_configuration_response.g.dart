// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'cover_configuration_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

CoverConfigurationResponse _$CoverConfigurationResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('CoverConfigurationResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = CoverConfigurationResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) => CoverConfiguration.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$CoverConfigurationResponseToJson(
  CoverConfigurationResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
