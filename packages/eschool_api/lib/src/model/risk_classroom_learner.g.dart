// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'risk_classroom_learner.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

RiskClassroomLearner _$RiskClassroomLearnerFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'RiskClassroomLearner',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const ['learner_id', 'name', 'observed_score', 'signals'],
    );
    final val = RiskClassroomLearner(
      learnerId: $checkedConvert('learner_id', (v) => (v as num).toInt()),
      name: $checkedConvert('name', (v) => v as String),
      observedScore: $checkedConvert(
        'observed_score',
        (v) => (v as num?)?.toInt(),
      ),
      signals: $checkedConvert(
        'signals',
        (v) => (v as List<dynamic>)
            .map((e) => RiskClassroomSignal.fromJson(e as Map<String, dynamic>))
            .toList(),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'learnerId': 'learner_id',
    'observedScore': 'observed_score',
  },
);

Map<String, dynamic> _$RiskClassroomLearnerToJson(
  RiskClassroomLearner instance,
) => <String, dynamic>{
  'learner_id': instance.learnerId,
  'name': instance.name,
  'observed_score': instance.observedScore,
  'signals': instance.signals.map((e) => e.toJson()).toList(),
};
