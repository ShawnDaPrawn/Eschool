// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'progress_learner_option.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ProgressLearnerOption _$ProgressLearnerOptionFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'ProgressLearnerOption',
  json,
  ($checkedConvert) {
    $checkKeys(json, requiredKeys: const ['learner_id', 'display_name']);
    final val = ProgressLearnerOption(
      learnerId: $checkedConvert('learner_id', (v) => (v as num).toInt()),
      displayName: $checkedConvert('display_name', (v) => v as String),
    );
    return val;
  },
  fieldKeyMap: const {'learnerId': 'learner_id', 'displayName': 'display_name'},
);

Map<String, dynamic> _$ProgressLearnerOptionToJson(
  ProgressLearnerOption instance,
) => <String, dynamic>{
  'learner_id': instance.learnerId,
  'display_name': instance.displayName,
};
