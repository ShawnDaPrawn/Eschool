// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'submit_anonymous_incident_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SubmitAnonymousIncidentResponse _$SubmitAnonymousIncidentResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('SubmitAnonymousIncidentResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = SubmitAnonymousIncidentResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => IncidentSubmissionReceipt.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$SubmitAnonymousIncidentResponseToJson(
  SubmitAnonymousIncidentResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
