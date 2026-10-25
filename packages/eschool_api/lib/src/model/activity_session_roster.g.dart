// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'activity_session_roster.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ActivitySessionRoster _$ActivitySessionRosterFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'ActivitySessionRoster',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'session_id',
        'group_id',
        'session_revision',
        'learners',
      ],
    );
    final val = ActivitySessionRoster(
      sessionId: $checkedConvert('session_id', (v) => (v as num).toInt()),
      groupId: $checkedConvert('group_id', (v) => (v as num).toInt()),
      sessionRevision: $checkedConvert(
        'session_revision',
        (v) => (v as num).toInt(),
      ),
      learners: $checkedConvert(
        'learners',
        (v) => (v as List<dynamic>)
            .map(
              (e) => ActivitySessionRosterLearner.fromJson(
                e as Map<String, dynamic>,
              ),
            )
            .toList(),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'sessionId': 'session_id',
    'groupId': 'group_id',
    'sessionRevision': 'session_revision',
  },
);

Map<String, dynamic> _$ActivitySessionRosterToJson(
  ActivitySessionRoster instance,
) => <String, dynamic>{
  'session_id': instance.sessionId,
  'group_id': instance.groupId,
  'session_revision': instance.sessionRevision,
  'learners': instance.learners.map((e) => e.toJson()).toList(),
};
