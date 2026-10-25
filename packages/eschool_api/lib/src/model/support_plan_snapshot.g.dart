// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'support_plan_snapshot.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SupportPlanSnapshot _$SupportPlanSnapshotFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'SupportPlanSnapshot',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'started_on',
            'review_due_on',
            'state',
            'contents',
            'accommodation_revision',
          ],
        );
        final val = SupportPlanSnapshot(
          startedOn: $checkedConvert('started_on', (v) => v as String),
          reviewDueOn: $checkedConvert('review_due_on', (v) => v as String?),
          state: $checkedConvert(
            'state',
            (v) => $enumDecode(
              _$SupportPlanSnapshotStateEnumEnumMap,
              v,
              unknownValue: SupportPlanSnapshotStateEnum.unknownDefaultOpenApi,
            ),
          ),
          contents: $checkedConvert(
            'contents',
            (v) => SupportPlanContents.fromJson(v as Map<String, dynamic>),
          ),
          accommodationRevision: $checkedConvert(
            'accommodation_revision',
            (v) => (v as num).toInt(),
          ),
        );
        return val;
      },
      fieldKeyMap: const {
        'startedOn': 'started_on',
        'reviewDueOn': 'review_due_on',
        'accommodationRevision': 'accommodation_revision',
      },
    );

Map<String, dynamic> _$SupportPlanSnapshotToJson(
  SupportPlanSnapshot instance,
) => <String, dynamic>{
  'started_on': instance.startedOn,
  'review_due_on': instance.reviewDueOn,
  'state': _$SupportPlanSnapshotStateEnumEnumMap[instance.state]!,
  'contents': instance.contents.toJson(),
  'accommodation_revision': instance.accommodationRevision,
};

const _$SupportPlanSnapshotStateEnumEnumMap = {
  SupportPlanSnapshotStateEnum.active: 'active',
  SupportPlanSnapshotStateEnum.closed: 'closed',
  SupportPlanSnapshotStateEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
