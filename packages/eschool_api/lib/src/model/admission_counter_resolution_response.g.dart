// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'admission_counter_resolution_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AdmissionCounterResolutionResponse _$AdmissionCounterResolutionResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('AdmissionCounterResolutionResponse', json, (
  $checkedConvert,
) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = AdmissionCounterResolutionResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => AdmissionCounterResolution.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$AdmissionCounterResolutionResponseToJson(
  AdmissionCounterResolutionResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
