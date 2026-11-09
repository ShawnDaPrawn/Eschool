// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'practice_marking_suggestion.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PracticeMarkingSuggestion _$PracticeMarkingSuggestionFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('PracticeMarkingSuggestion', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['correct', 'reason']);
  final val = PracticeMarkingSuggestion(
    correct: $checkedConvert('correct', (v) => v as bool),
    reason: $checkedConvert('reason', (v) => v as String),
  );
  return val;
});

Map<String, dynamic> _$PracticeMarkingSuggestionToJson(
  PracticeMarkingSuggestion instance,
) => <String, dynamic>{'correct': instance.correct, 'reason': instance.reason};
