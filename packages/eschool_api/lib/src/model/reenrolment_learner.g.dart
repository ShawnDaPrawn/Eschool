// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'reenrolment_learner.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ReenrolmentLearner _$ReenrolmentLearnerFromJson(Map<String, dynamic> json) =>
    $checkedCreate('ReenrolmentLearner', json, ($checkedConvert) {
      $checkKeys(json, requiredKeys: const ['learner_id', 'contracts']);
      final val = ReenrolmentLearner(
        learnerId: $checkedConvert('learner_id', (v) => (v as num).toInt()),
        contracts: $checkedConvert(
          'contracts',
          (v) => (v as List<dynamic>)
              .map(
                (e) => ReenrolmentLearnerContractsInner.fromJson(
                  e as Map<String, dynamic>,
                ),
              )
              .toList(),
        ),
      );
      return val;
    }, fieldKeyMap: const {'learnerId': 'learner_id'});

Map<String, dynamic> _$ReenrolmentLearnerToJson(ReenrolmentLearner instance) =>
    <String, dynamic>{
      'learner_id': instance.learnerId,
      'contracts': instance.contracts.map((e) => e.toJson()).toList(),
    };
