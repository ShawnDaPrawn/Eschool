// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'wellbeing_case_link_input.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

WellbeingCaseLinkInput _$WellbeingCaseLinkInputFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'WellbeingCaseLinkInput',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const ['operation_id', 'revision', 'incident_report_id'],
    );
    final val = WellbeingCaseLinkInput(
      operationId: $checkedConvert('operation_id', (v) => v as String),
      revision: $checkedConvert('revision', (v) => (v as num).toInt()),
      incidentReportId: $checkedConvert(
        'incident_report_id',
        (v) => (v as num).toInt(),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'operationId': 'operation_id',
    'incidentReportId': 'incident_report_id',
  },
);

Map<String, dynamic> _$WellbeingCaseLinkInputToJson(
  WellbeingCaseLinkInput instance,
) => <String, dynamic>{
  'operation_id': instance.operationId,
  'revision': instance.revision,
  'incident_report_id': instance.incidentReportId,
};
