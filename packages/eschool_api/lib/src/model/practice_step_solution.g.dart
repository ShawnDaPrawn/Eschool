// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'practice_step_solution.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PracticeStepSolution _$PracticeStepSolutionFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('PracticeStepSolution', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['id', 'answers']);
  final val = PracticeStepSolution(
    id: $checkedConvert('id', (v) => v as String),
    answers: $checkedConvert(
      'answers',
      (v) => (v as List<dynamic>).map((e) => e as String).toList(),
    ),
  );
  return val;
});

Map<String, dynamic> _$PracticeStepSolutionToJson(
  PracticeStepSolution instance,
) => <String, dynamic>{'id': instance.id, 'answers': instance.answers};
