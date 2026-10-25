// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'recognition_configuration_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

RecognitionConfigurationResponse _$RecognitionConfigurationResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('RecognitionConfigurationResponse', json, (
  $checkedConvert,
) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = RecognitionConfigurationResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) => RecognitionConfiguration.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$RecognitionConfigurationResponseToJson(
  RecognitionConfigurationResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
