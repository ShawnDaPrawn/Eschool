// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'foundation_eligibility.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

FoundationEligibility _$FoundationEligibilityFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('FoundationEligibility', json, ($checkedConvert) {
  $checkKeys(
    json,
    requiredKeys: const ['teaching_group_id', 'eligible', 'revision'],
  );
  final val = FoundationEligibility(
    teachingGroupId: $checkedConvert(
      'teaching_group_id',
      (v) => (v as num).toInt(),
    ),
    eligible: $checkedConvert('eligible', (v) => v as bool),
    revision: $checkedConvert('revision', (v) => (v as num).toInt()),
  );
  return val;
}, fieldKeyMap: const {'teachingGroupId': 'teaching_group_id'});

Map<String, dynamic> _$FoundationEligibilityToJson(
  FoundationEligibility instance,
) => <String, dynamic>{
  'teaching_group_id': instance.teachingGroupId,
  'eligible': instance.eligible,
  'revision': instance.revision,
};
