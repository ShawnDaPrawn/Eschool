// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'submit_identified_incident_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SubmitIdentifiedIncidentResponse _$SubmitIdentifiedIncidentResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('SubmitIdentifiedIncidentResponse', json, (
  $checkedConvert,
) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = SubmitIdentifiedIncidentResponse(
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

Map<String, dynamic> _$SubmitIdentifiedIncidentResponseToJson(
  SubmitIdentifiedIncidentResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
