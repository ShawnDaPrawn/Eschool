// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'reasoning_feedback.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ReasoningFeedback _$ReasoningFeedbackFromJson(Map<String, dynamic> json) =>
    $checkedCreate('ReasoningFeedback', json, ($checkedConvert) {
      $checkKeys(
        json,
        requiredKeys: const [
          'structure',
          'evidence',
          'rebuttal',
          'fallacies',
          'summary',
        ],
      );
      final val = ReasoningFeedback(
        structure: $checkedConvert('structure', (v) => v as String),
        evidence: $checkedConvert('evidence', (v) => v as String),
        rebuttal: $checkedConvert('rebuttal', (v) => v as String),
        fallacies: $checkedConvert(
          'fallacies',
          (v) => (v as List<dynamic>)
              .map(
                (e) => ReasoningFeedbackFallaciesInner.fromJson(
                  e as Map<String, dynamic>,
                ),
              )
              .toList(),
        ),
        summary: $checkedConvert('summary', (v) => v as String),
      );
      return val;
    });

Map<String, dynamic> _$ReasoningFeedbackToJson(ReasoningFeedback instance) =>
    <String, dynamic>{
      'structure': instance.structure,
      'evidence': instance.evidence,
      'rebuttal': instance.rebuttal,
      'fallacies': instance.fallacies.map((e) => e.toJson()).toList(),
      'summary': instance.summary,
    };
