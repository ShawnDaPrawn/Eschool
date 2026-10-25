// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'risk_signal_learner.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

RiskSignalLearner _$RiskSignalLearnerFromJson(Map<String, dynamic> json) =>
    $checkedCreate('RiskSignalLearner', json, ($checkedConvert) {
      $checkKeys(json, requiredKeys: const ['learner_id', 'name', 'signals']);
      final val = RiskSignalLearner(
        learnerId: $checkedConvert('learner_id', (v) => (v as num).toInt()),
        name: $checkedConvert('name', (v) => v as String),
        signals: $checkedConvert(
          'signals',
          (v) => (v as List<dynamic>)
              .map((e) => RiskSignal.fromJson(e as Map<String, dynamic>))
              .toList(),
        ),
      );
      return val;
    }, fieldKeyMap: const {'learnerId': 'learner_id'});

Map<String, dynamic> _$RiskSignalLearnerToJson(RiskSignalLearner instance) =>
    <String, dynamic>{
      'learner_id': instance.learnerId,
      'name': instance.name,
      'signals': instance.signals.map((e) => e.toJson()).toList(),
    };
