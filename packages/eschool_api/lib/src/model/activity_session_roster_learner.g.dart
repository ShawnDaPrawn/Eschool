// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'activity_session_roster_learner.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ActivitySessionRosterLearner _$ActivitySessionRosterLearnerFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'ActivitySessionRosterLearner',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const ['learner_id', 'learner_name', 'status', 'revision'],
    );
    final val = ActivitySessionRosterLearner(
      learnerId: $checkedConvert('learner_id', (v) => (v as num).toInt()),
      learnerName: $checkedConvert('learner_name', (v) => v as String),
      status: $checkedConvert('status', (v) => v as String?),
      revision: $checkedConvert('revision', (v) => (v as num).toInt()),
    );
    return val;
  },
  fieldKeyMap: const {'learnerId': 'learner_id', 'learnerName': 'learner_name'},
);

Map<String, dynamic> _$ActivitySessionRosterLearnerToJson(
  ActivitySessionRosterLearner instance,
) => <String, dynamic>{
  'learner_id': instance.learnerId,
  'learner_name': instance.learnerName,
  'status': instance.status,
  'revision': instance.revision,
};
