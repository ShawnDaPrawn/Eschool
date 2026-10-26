// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'peer_match.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PeerMatch _$PeerMatchFromJson(Map<String, dynamic> json) => $checkedCreate(
  'PeerMatch',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'tutor_learner_id',
        'tutor_name',
        'tutee_learner_id',
        'topic_id',
        'topic_name',
        'reason',
      ],
    );
    final val = PeerMatch(
      tutorLearnerId: $checkedConvert(
        'tutor_learner_id',
        (v) => (v as num).toInt(),
      ),
      tutorName: $checkedConvert('tutor_name', (v) => v as String),
      tuteeLearnerId: $checkedConvert(
        'tutee_learner_id',
        (v) => (v as num).toInt(),
      ),
      topicId: $checkedConvert('topic_id', (v) => (v as num).toInt()),
      topicName: $checkedConvert('topic_name', (v) => v as String),
      reason: $checkedConvert(
        'reason',
        (v) => $enumDecode(
          _$PeerMatchReasonEnumEnumMap,
          v,
          unknownValue: PeerMatchReasonEnum.unknownDefaultOpenApi,
        ),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'tutorLearnerId': 'tutor_learner_id',
    'tutorName': 'tutor_name',
    'tuteeLearnerId': 'tutee_learner_id',
    'topicId': 'topic_id',
    'topicName': 'topic_name',
  },
);

Map<String, dynamic> _$PeerMatchToJson(PeerMatch instance) => <String, dynamic>{
  'tutor_learner_id': instance.tutorLearnerId,
  'tutor_name': instance.tutorName,
  'tutee_learner_id': instance.tuteeLearnerId,
  'topic_id': instance.topicId,
  'topic_name': instance.topicName,
  'reason': _$PeerMatchReasonEnumEnumMap[instance.reason]!,
};

const _$PeerMatchReasonEnumEnumMap = {
  PeerMatchReasonEnum.notYetAssessed: 'not_yet_assessed',
  PeerMatchReasonEnum.prerequisiteGap: 'prerequisite_gap',
  PeerMatchReasonEnum.topicGap: 'topic_gap',
  PeerMatchReasonEnum.unknownDefaultOpenApi: 'unknown_default_open_api',
};
