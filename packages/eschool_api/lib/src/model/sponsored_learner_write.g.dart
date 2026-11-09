// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'sponsored_learner_write.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SponsoredLearnerWrite _$SponsoredLearnerWriteFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('SponsoredLearnerWrite', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['learner_id']);
  final val = SponsoredLearnerWrite(
    learnerId: $checkedConvert('learner_id', (v) => (v as num).toInt()),
  );
  return val;
}, fieldKeyMap: const {'learnerId': 'learner_id'});

Map<String, dynamic> _$SponsoredLearnerWriteToJson(
  SponsoredLearnerWrite instance,
) => <String, dynamic>{'learner_id': instance.learnerId};
