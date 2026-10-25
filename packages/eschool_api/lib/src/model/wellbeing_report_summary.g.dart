// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'wellbeing_report_summary.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

WellbeingReportSummary _$WellbeingReportSummaryFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'WellbeingReportSummary',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'id',
        'category_id',
        'anonymous',
        'urgency',
        'status',
        'revision',
        'created_at',
        'category_name',
      ],
    );
    final val = WellbeingReportSummary(
      id: $checkedConvert('id', (v) => (v as num).toInt()),
      categoryId: $checkedConvert('category_id', (v) => (v as num).toInt()),
      anonymous: $checkedConvert('anonymous', (v) => v as bool),
      urgency: $checkedConvert(
        'urgency',
        (v) => $enumDecode(
          _$WellbeingReportSummaryUrgencyEnumEnumMap,
          v,
          unknownValue: WellbeingReportSummaryUrgencyEnum.unknownDefaultOpenApi,
        ),
      ),
      status: $checkedConvert(
        'status',
        (v) => $enumDecode(
          _$WellbeingReportSummaryStatusEnumEnumMap,
          v,
          unknownValue: WellbeingReportSummaryStatusEnum.unknownDefaultOpenApi,
        ),
      ),
      revision: $checkedConvert('revision', (v) => (v as num).toInt()),
      createdAt: $checkedConvert(
        'created_at',
        (v) => DateTime.parse(v as String),
      ),
      categoryName: $checkedConvert('category_name', (v) => v as String),
    );
    return val;
  },
  fieldKeyMap: const {
    'categoryId': 'category_id',
    'createdAt': 'created_at',
    'categoryName': 'category_name',
  },
);

Map<String, dynamic> _$WellbeingReportSummaryToJson(
  WellbeingReportSummary instance,
) => <String, dynamic>{
  'id': instance.id,
  'category_id': instance.categoryId,
  'anonymous': instance.anonymous,
  'urgency': _$WellbeingReportSummaryUrgencyEnumEnumMap[instance.urgency]!,
  'status': _$WellbeingReportSummaryStatusEnumEnumMap[instance.status]!,
  'revision': instance.revision,
  'created_at': instance.createdAt.toIso8601String(),
  'category_name': instance.categoryName,
};

const _$WellbeingReportSummaryUrgencyEnumEnumMap = {
  WellbeingReportSummaryUrgencyEnum.routine: 'routine',
  WellbeingReportSummaryUrgencyEnum.urgent: 'urgent',
  WellbeingReportSummaryUrgencyEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};

const _$WellbeingReportSummaryStatusEnumEnumMap = {
  WellbeingReportSummaryStatusEnum.open: 'open',
  WellbeingReportSummaryStatusEnum.triaged: 'triaged',
  WellbeingReportSummaryStatusEnum.resolved: 'resolved',
  WellbeingReportSummaryStatusEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
