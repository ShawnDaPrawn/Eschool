// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'reasoning_feedback_fallacies_inner.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ReasoningFeedbackFallaciesInner _$ReasoningFeedbackFallaciesInnerFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('ReasoningFeedbackFallaciesInner', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['name', 'explanation']);
  final val = ReasoningFeedbackFallaciesInner(
    name: $checkedConvert('name', (v) => v as String),
    explanation: $checkedConvert('explanation', (v) => v as String),
  );
  return val;
});

Map<String, dynamic> _$ReasoningFeedbackFallaciesInnerToJson(
  ReasoningFeedbackFallaciesInner instance,
) => <String, dynamic>{
  'name': instance.name,
  'explanation': instance.explanation,
};
