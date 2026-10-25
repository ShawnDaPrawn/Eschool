// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'support_case_summary.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SupportCaseSummary _$SupportCaseSummaryFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'SupportCaseSummary',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'id',
            'learner_id',
            'learner_name',
            'revision',
            'state',
            'review_due_on',
          ],
        );
        final val = SupportCaseSummary(
          id: $checkedConvert('id', (v) => (v as num).toInt()),
          learnerId: $checkedConvert('learner_id', (v) => (v as num).toInt()),
          learnerName: $checkedConvert('learner_name', (v) => v as String),
          revision: $checkedConvert('revision', (v) => (v as num).toInt()),
          state: $checkedConvert(
            'state',
            (v) => $enumDecode(
              _$SupportCaseSummaryStateEnumEnumMap,
              v,
              unknownValue: SupportCaseSummaryStateEnum.unknownDefaultOpenApi,
            ),
          ),
          reviewDueOn: $checkedConvert('review_due_on', (v) => v as String?),
        );
        return val;
      },
      fieldKeyMap: const {
        'learnerId': 'learner_id',
        'learnerName': 'learner_name',
        'reviewDueOn': 'review_due_on',
      },
    );

Map<String, dynamic> _$SupportCaseSummaryToJson(SupportCaseSummary instance) =>
    <String, dynamic>{
      'id': instance.id,
      'learner_id': instance.learnerId,
      'learner_name': instance.learnerName,
      'revision': instance.revision,
      'state': _$SupportCaseSummaryStateEnumEnumMap[instance.state]!,
      'review_due_on': instance.reviewDueOn,
    };

const _$SupportCaseSummaryStateEnumEnumMap = {
  SupportCaseSummaryStateEnum.active: 'active',
  SupportCaseSummaryStateEnum.closed: 'closed',
  SupportCaseSummaryStateEnum.unknownDefaultOpenApi: 'unknown_default_open_api',
};
