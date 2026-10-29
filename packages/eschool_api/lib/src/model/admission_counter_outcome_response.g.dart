// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'admission_counter_outcome_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AdmissionCounterOutcomeResponse _$AdmissionCounterOutcomeResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('AdmissionCounterOutcomeResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = AdmissionCounterOutcomeResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => AdmissionCounterOutcome.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$AdmissionCounterOutcomeResponseToJson(
  AdmissionCounterOutcomeResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
