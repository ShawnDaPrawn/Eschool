// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'identify_incident_reporter_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

IdentifyIncidentReporterResponse _$IdentifyIncidentReporterResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('IdentifyIncidentReporterResponse', json, (
  $checkedConvert,
) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = IdentifyIncidentReporterResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => IncidentPublicView.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$IdentifyIncidentReporterResponseToJson(
  IdentifyIncidentReporterResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
