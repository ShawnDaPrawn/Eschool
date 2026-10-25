// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'mastery_roster_learner.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

MasteryRosterLearner _$MasteryRosterLearnerFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'MasteryRosterLearner',
  json,
  ($checkedConvert) {
    $checkKeys(json, requiredKeys: const ['learner_id', 'display_name']);
    final val = MasteryRosterLearner(
      learnerId: $checkedConvert('learner_id', (v) => (v as num).toInt()),
      displayName: $checkedConvert('display_name', (v) => v as String),
    );
    return val;
  },
  fieldKeyMap: const {'learnerId': 'learner_id', 'displayName': 'display_name'},
);

Map<String, dynamic> _$MasteryRosterLearnerToJson(
  MasteryRosterLearner instance,
) => <String, dynamic>{
  'learner_id': instance.learnerId,
  'display_name': instance.displayName,
};
