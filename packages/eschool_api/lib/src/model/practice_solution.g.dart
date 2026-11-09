// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'practice_solution.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PracticeSolution _$PracticeSolutionFromJson(Map<String, dynamic> json) =>
    $checkedCreate('PracticeSolution', json, ($checkedConvert) {
      $checkKeys(
        json,
        requiredKeys: const ['explanation', 'answers', 'steps', 'mapping'],
      );
      final val = PracticeSolution(
        explanation: $checkedConvert('explanation', (v) => v as String),
        answers: $checkedConvert(
          'answers',
          (v) => (v as List<dynamic>).map((e) => e as String).toList(),
        ),
        steps: $checkedConvert(
          'steps',
          (v) => (v as List<dynamic>)
              .map(
                (e) => PracticeStepSolution.fromJson(e as Map<String, dynamic>),
              )
              .toList(),
        ),
        mapping: $checkedConvert(
          'mapping',
          (v) => (v as List<dynamic>)
              .map(
                (e) =>
                    PracticeRegionSolution.fromJson(e as Map<String, dynamic>),
              )
              .toList(),
        ),
      );
      return val;
    });

Map<String, dynamic> _$PracticeSolutionToJson(PracticeSolution instance) =>
    <String, dynamic>{
      'explanation': instance.explanation,
      'answers': instance.answers,
      'steps': instance.steps.map((e) => e.toJson()).toList(),
      'mapping': instance.mapping.map((e) => e.toJson()).toList(),
    };
