// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'peer_session.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PeerSession _$PeerSessionFromJson(Map<String, dynamic> json) => $checkedCreate(
  'PeerSession',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'id',
        'revision',
        'teaching_group_id',
        'topic_id',
        'topic_name',
        'tutor_learner_id',
        'tutor_name',
        'tutee_learner_id',
        'tutee_name',
        'supervisor_user_id',
        'supervisor_name',
        'starts_at',
        'ends_at',
        'venue',
        'status',
        'verified_minutes',
        'service_minutes',
        'service_for_reader',
        'house_point_id',
        'can_complete',
        'can_cancel',
        'can_reverse',
      ],
    );
    final val = PeerSession(
      id: $checkedConvert('id', (v) => v as String),
      revision: $checkedConvert('revision', (v) => (v as num).toInt()),
      teachingGroupId: $checkedConvert(
        'teaching_group_id',
        (v) => (v as num).toInt(),
      ),
      topicId: $checkedConvert('topic_id', (v) => (v as num).toInt()),
      topicName: $checkedConvert('topic_name', (v) => v as String),
      tutorLearnerId: $checkedConvert(
        'tutor_learner_id',
        (v) => (v as num).toInt(),
      ),
      tutorName: $checkedConvert('tutor_name', (v) => v as String?),
      tuteeLearnerId: $checkedConvert(
        'tutee_learner_id',
        (v) => (v as num).toInt(),
      ),
      tuteeName: $checkedConvert('tutee_name', (v) => v as String?),
      supervisorUserId: $checkedConvert(
        'supervisor_user_id',
        (v) => (v as num).toInt(),
      ),
      supervisorName: $checkedConvert('supervisor_name', (v) => v as String),
      startsAt: $checkedConvert(
        'starts_at',
        (v) => DateTime.parse(v as String),
      ),
      endsAt: $checkedConvert('ends_at', (v) => DateTime.parse(v as String)),
      venue: $checkedConvert('venue', (v) => v as String),
      status: $checkedConvert(
        'status',
        (v) => $enumDecode(
          _$PeerSessionStatusEnumEnumMap,
          v,
          unknownValue: PeerSessionStatusEnum.unknownDefaultOpenApi,
        ),
      ),
      verifiedMinutes: $checkedConvert(
        'verified_minutes',
        (v) => (v as num?)?.toInt(),
      ),
      serviceMinutes: $checkedConvert(
        'service_minutes',
        (v) => (v as num?)?.toInt(),
      ),
      serviceForReader: $checkedConvert('service_for_reader', (v) => v as bool),
      housePointId: $checkedConvert(
        'house_point_id',
        (v) => (v as num?)?.toInt(),
      ),
      canComplete: $checkedConvert('can_complete', (v) => v as bool),
      canCancel: $checkedConvert('can_cancel', (v) => v as bool),
      canReverse: $checkedConvert('can_reverse', (v) => v as bool),
    );
    return val;
  },
  fieldKeyMap: const {
    'teachingGroupId': 'teaching_group_id',
    'topicId': 'topic_id',
    'topicName': 'topic_name',
    'tutorLearnerId': 'tutor_learner_id',
    'tutorName': 'tutor_name',
    'tuteeLearnerId': 'tutee_learner_id',
    'tuteeName': 'tutee_name',
    'supervisorUserId': 'supervisor_user_id',
    'supervisorName': 'supervisor_name',
    'startsAt': 'starts_at',
    'endsAt': 'ends_at',
    'verifiedMinutes': 'verified_minutes',
    'serviceMinutes': 'service_minutes',
    'serviceForReader': 'service_for_reader',
    'housePointId': 'house_point_id',
    'canComplete': 'can_complete',
    'canCancel': 'can_cancel',
    'canReverse': 'can_reverse',
  },
);

Map<String, dynamic> _$PeerSessionToJson(PeerSession instance) =>
    <String, dynamic>{
      'id': instance.id,
      'revision': instance.revision,
      'teaching_group_id': instance.teachingGroupId,
      'topic_id': instance.topicId,
      'topic_name': instance.topicName,
      'tutor_learner_id': instance.tutorLearnerId,
      'tutor_name': instance.tutorName,
      'tutee_learner_id': instance.tuteeLearnerId,
      'tutee_name': instance.tuteeName,
      'supervisor_user_id': instance.supervisorUserId,
      'supervisor_name': instance.supervisorName,
      'starts_at': instance.startsAt.toIso8601String(),
      'ends_at': instance.endsAt.toIso8601String(),
      'venue': instance.venue,
      'status': _$PeerSessionStatusEnumEnumMap[instance.status]!,
      'verified_minutes': instance.verifiedMinutes,
      'service_minutes': instance.serviceMinutes,
      'service_for_reader': instance.serviceForReader,
      'house_point_id': instance.housePointId,
      'can_complete': instance.canComplete,
      'can_cancel': instance.canCancel,
      'can_reverse': instance.canReverse,
    };

const _$PeerSessionStatusEnumEnumMap = {
  PeerSessionStatusEnum.scheduled: 'scheduled',
  PeerSessionStatusEnum.completed: 'completed',
  PeerSessionStatusEnum.cancelled: 'cancelled',
  PeerSessionStatusEnum.reversed: 'reversed',
  PeerSessionStatusEnum.unknownDefaultOpenApi: 'unknown_default_open_api',
};
