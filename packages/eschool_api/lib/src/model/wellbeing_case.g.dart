// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'wellbeing_case.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

WellbeingCase _$WellbeingCaseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'WellbeingCase',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'id',
        'learner_id',
        'learner_name',
        'incident_report_id',
        'status',
        'opened_at',
        'revision',
        'entries',
        'has_older',
        'next_before',
      ],
    );
    final val = WellbeingCase(
      id: $checkedConvert('id', (v) => (v as num).toInt()),
      learnerId: $checkedConvert('learner_id', (v) => (v as num).toInt()),
      learnerName: $checkedConvert('learner_name', (v) => v as String),
      incidentReportId: $checkedConvert(
        'incident_report_id',
        (v) => (v as num?)?.toInt(),
      ),
      status: $checkedConvert(
        'status',
        (v) => $enumDecode(
          _$WellbeingCaseStatusEnumEnumMap,
          v,
          unknownValue: WellbeingCaseStatusEnum.unknownDefaultOpenApi,
        ),
      ),
      openedAt: $checkedConvert(
        'opened_at',
        (v) => DateTime.parse(v as String),
      ),
      revision: $checkedConvert('revision', (v) => (v as num).toInt()),
      entries: $checkedConvert(
        'entries',
        (v) => (v as List<dynamic>)
            .map((e) => WellbeingCaseEntry.fromJson(e as Map<String, dynamic>))
            .toList(),
      ),
      hasOlder: $checkedConvert('has_older', (v) => v as bool),
      nextBefore: $checkedConvert('next_before', (v) => (v as num?)?.toInt()),
    );
    return val;
  },
  fieldKeyMap: const {
    'learnerId': 'learner_id',
    'learnerName': 'learner_name',
    'incidentReportId': 'incident_report_id',
    'openedAt': 'opened_at',
    'hasOlder': 'has_older',
    'nextBefore': 'next_before',
  },
);

Map<String, dynamic> _$WellbeingCaseToJson(WellbeingCase instance) =>
    <String, dynamic>{
      'id': instance.id,
      'learner_id': instance.learnerId,
      'learner_name': instance.learnerName,
      'incident_report_id': instance.incidentReportId,
      'status': _$WellbeingCaseStatusEnumEnumMap[instance.status]!,
      'opened_at': instance.openedAt.toIso8601String(),
      'revision': instance.revision,
      'entries': instance.entries.map((e) => e.toJson()).toList(),
      'has_older': instance.hasOlder,
      'next_before': instance.nextBefore,
    };

const _$WellbeingCaseStatusEnumEnumMap = {
  WellbeingCaseStatusEnum.open: 'open',
  WellbeingCaseStatusEnum.closed: 'closed',
  WellbeingCaseStatusEnum.unknownDefaultOpenApi: 'unknown_default_open_api',
};
