// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'risk_learner_choice.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

RiskLearnerChoice _$RiskLearnerChoiceFromJson(Map<String, dynamic> json) =>
    $checkedCreate('RiskLearnerChoice', json, ($checkedConvert) {
      $checkKeys(json, requiredKeys: const ['learner_id', 'name']);
      final val = RiskLearnerChoice(
        learnerId: $checkedConvert('learner_id', (v) => (v as num).toInt()),
        name: $checkedConvert('name', (v) => v as String),
      );
      return val;
    }, fieldKeyMap: const {'learnerId': 'learner_id'});

Map<String, dynamic> _$RiskLearnerChoiceToJson(RiskLearnerChoice instance) =>
    <String, dynamic>{'learner_id': instance.learnerId, 'name': instance.name};
