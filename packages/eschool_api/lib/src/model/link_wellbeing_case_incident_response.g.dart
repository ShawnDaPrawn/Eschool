// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'link_wellbeing_case_incident_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

LinkWellbeingCaseIncidentResponse _$LinkWellbeingCaseIncidentResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('LinkWellbeingCaseIncidentResponse', json, (
  $checkedConvert,
) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = LinkWellbeingCaseIncidentResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => WellbeingCaseLinkReceipt.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$LinkWellbeingCaseIncidentResponseToJson(
  LinkWellbeingCaseIncidentResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
