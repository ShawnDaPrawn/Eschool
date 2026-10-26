// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'peer_session_create.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PeerSessionCreate _$PeerSessionCreateFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'PeerSessionCreate',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'persona',
            'operation_id',
            'teaching_group_id',
            'tutor_learner_id',
            'tutee_learner_id',
            'topic_id',
            'venue',
            'starts_at',
            'ends_at',
          ],
        );
        final val = PeerSessionCreate(
          persona: $checkedConvert(
            'persona',
            (v) => $enumDecode(
              _$PeerSessionCreatePersonaEnumEnumMap,
              v,
              unknownValue: PeerSessionCreatePersonaEnum.unknownDefaultOpenApi,
            ),
          ),
          operationId: $checkedConvert('operation_id', (v) => v as String),
          teachingGroupId: $checkedConvert(
            'teaching_group_id',
            (v) => (v as num).toInt(),
          ),
          tutorLearnerId: $checkedConvert(
            'tutor_learner_id',
            (v) => (v as num).toInt(),
          ),
          tuteeLearnerId: $checkedConvert(
            'tutee_learner_id',
            (v) => (v as num).toInt(),
          ),
          topicId: $checkedConvert('topic_id', (v) => (v as num).toInt()),
          venue: $checkedConvert('venue', (v) => v as String),
          startsAt: $checkedConvert('starts_at', (v) => v as String),
          endsAt: $checkedConvert('ends_at', (v) => v as String),
        );
        return val;
      },
      fieldKeyMap: const {
        'operationId': 'operation_id',
        'teachingGroupId': 'teaching_group_id',
        'tutorLearnerId': 'tutor_learner_id',
        'tuteeLearnerId': 'tutee_learner_id',
        'topicId': 'topic_id',
        'startsAt': 'starts_at',
        'endsAt': 'ends_at',
      },
    );

Map<String, dynamic> _$PeerSessionCreateToJson(PeerSessionCreate instance) =>
    <String, dynamic>{
      'persona': _$PeerSessionCreatePersonaEnumEnumMap[instance.persona]!,
      'operation_id': instance.operationId,
      'teaching_group_id': instance.teachingGroupId,
      'tutor_learner_id': instance.tutorLearnerId,
      'tutee_learner_id': instance.tuteeLearnerId,
      'topic_id': instance.topicId,
      'venue': instance.venue,
      'starts_at': instance.startsAt,
      'ends_at': instance.endsAt,
    };

const _$PeerSessionCreatePersonaEnumEnumMap = {
  PeerSessionCreatePersonaEnum.staff: 'staff',
  PeerSessionCreatePersonaEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
