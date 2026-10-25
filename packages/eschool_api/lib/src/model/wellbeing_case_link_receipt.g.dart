// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'wellbeing_case_link_receipt.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

WellbeingCaseLinkReceipt _$WellbeingCaseLinkReceiptFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'WellbeingCaseLinkReceipt',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'id',
        'learner_id',
        'incident_report_id',
        'status',
        'revision',
      ],
    );
    final val = WellbeingCaseLinkReceipt(
      id: $checkedConvert('id', (v) => (v as num).toInt()),
      learnerId: $checkedConvert('learner_id', (v) => (v as num).toInt()),
      incidentReportId: $checkedConvert(
        'incident_report_id',
        (v) => (v as num).toInt(),
      ),
      status: $checkedConvert(
        'status',
        (v) => $enumDecode(
          _$WellbeingCaseLinkReceiptStatusEnumEnumMap,
          v,
          unknownValue:
              WellbeingCaseLinkReceiptStatusEnum.unknownDefaultOpenApi,
        ),
      ),
      revision: $checkedConvert('revision', (v) => (v as num).toInt()),
    );
    return val;
  },
  fieldKeyMap: const {
    'learnerId': 'learner_id',
    'incidentReportId': 'incident_report_id',
  },
);

Map<String, dynamic> _$WellbeingCaseLinkReceiptToJson(
  WellbeingCaseLinkReceipt instance,
) => <String, dynamic>{
  'id': instance.id,
  'learner_id': instance.learnerId,
  'incident_report_id': instance.incidentReportId,
  'status': _$WellbeingCaseLinkReceiptStatusEnumEnumMap[instance.status]!,
  'revision': instance.revision,
};

const _$WellbeingCaseLinkReceiptStatusEnumEnumMap = {
  WellbeingCaseLinkReceiptStatusEnum.open: 'open',
  WellbeingCaseLinkReceiptStatusEnum.closed: 'closed',
  WellbeingCaseLinkReceiptStatusEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
