// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'wellbeing_case_summary.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

WellbeingCaseSummary _$WellbeingCaseSummaryFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'WellbeingCaseSummary',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'id',
        'learner_id',
        'learner_name',
        'status',
        'opened_at',
        'revision',
      ],
    );
    final val = WellbeingCaseSummary(
      id: $checkedConvert('id', (v) => (v as num).toInt()),
      learnerId: $checkedConvert('learner_id', (v) => (v as num).toInt()),
      learnerName: $checkedConvert('learner_name', (v) => v as String),
      status: $checkedConvert(
        'status',
        (v) => $enumDecode(
          _$WellbeingCaseSummaryStatusEnumEnumMap,
          v,
          unknownValue: WellbeingCaseSummaryStatusEnum.unknownDefaultOpenApi,
        ),
      ),
      openedAt: $checkedConvert(
        'opened_at',
        (v) => DateTime.parse(v as String),
      ),
      revision: $checkedConvert('revision', (v) => (v as num).toInt()),
    );
    return val;
  },
  fieldKeyMap: const {
    'learnerId': 'learner_id',
    'learnerName': 'learner_name',
    'openedAt': 'opened_at',
  },
);

Map<String, dynamic> _$WellbeingCaseSummaryToJson(
  WellbeingCaseSummary instance,
) => <String, dynamic>{
  'id': instance.id,
  'learner_id': instance.learnerId,
  'learner_name': instance.learnerName,
  'status': _$WellbeingCaseSummaryStatusEnumEnumMap[instance.status]!,
  'opened_at': instance.openedAt.toIso8601String(),
  'revision': instance.revision,
};

const _$WellbeingCaseSummaryStatusEnumEnumMap = {
  WellbeingCaseSummaryStatusEnum.open: 'open',
  WellbeingCaseSummaryStatusEnum.closed: 'closed',
  WellbeingCaseSummaryStatusEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
