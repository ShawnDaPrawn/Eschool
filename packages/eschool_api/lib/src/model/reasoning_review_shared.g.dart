// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'reasoning_review_shared.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ReasoningReviewShared _$ReasoningReviewSharedFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('ReasoningReviewShared', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['learner_id', 'text', 'shared_at']);
  final val = ReasoningReviewShared(
    learnerId: $checkedConvert('learner_id', (v) => (v as num).toInt()),
    text: $checkedConvert('text', (v) => v as String),
    sharedAt: $checkedConvert('shared_at', (v) => DateTime.parse(v as String)),
  );
  return val;
}, fieldKeyMap: const {'learnerId': 'learner_id', 'sharedAt': 'shared_at'});

Map<String, dynamic> _$ReasoningReviewSharedToJson(
  ReasoningReviewShared instance,
) => <String, dynamic>{
  'learner_id': instance.learnerId,
  'text': instance.text,
  'shared_at': instance.sharedAt.toIso8601String(),
};
