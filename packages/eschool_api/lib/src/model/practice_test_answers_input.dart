//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/practice_test_answer.dart';
import 'package:json_annotation/json_annotation.dart';

part 'practice_test_answers_input.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class PracticeTestAnswersInput {
  /// Returns a new [PracticeTestAnswersInput] instance.
  PracticeTestAnswersInput({required this.answers});

  @JsonKey(name: r'answers', required: true, includeIfNull: false)
  final List<PracticeTestAnswer> answers;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is PracticeTestAnswersInput && other.answers == answers;

  @override
  int get hashCode => answers.hashCode;

  factory PracticeTestAnswersInput.fromJson(Map<String, dynamic> json) =>
      _$PracticeTestAnswersInputFromJson(json);

  Map<String, dynamic> toJson() => _$PracticeTestAnswersInputToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
