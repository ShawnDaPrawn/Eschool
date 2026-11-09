//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'practice_marking_suggestion.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class PracticeMarkingSuggestion {
  /// Returns a new [PracticeMarkingSuggestion] instance.
  PracticeMarkingSuggestion({required this.correct, required this.reason});

  @JsonKey(name: r'correct', required: true, includeIfNull: false)
  final bool correct;

  @JsonKey(name: r'reason', required: true, includeIfNull: false)
  final String reason;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is PracticeMarkingSuggestion &&
          other.correct == correct &&
          other.reason == reason;

  @override
  int get hashCode => correct.hashCode + reason.hashCode;

  factory PracticeMarkingSuggestion.fromJson(Map<String, dynamic> json) =>
      _$PracticeMarkingSuggestionFromJson(json);

  Map<String, dynamic> toJson() => _$PracticeMarkingSuggestionToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
