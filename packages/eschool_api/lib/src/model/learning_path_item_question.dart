//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/learning_path_item_question_options_inner.dart';
import 'package:json_annotation/json_annotation.dart';

part 'learning_path_item_question.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class LearningPathItemQuestion {
  /// Returns a new [LearningPathItemQuestion] instance.
  LearningPathItemQuestion({
    required this.kind,

    required this.prompt,

    required this.options,
  });

  @JsonKey(name: r'kind', required: true, includeIfNull: false)
  final String kind;

  @JsonKey(name: r'prompt', required: true, includeIfNull: false)
  final String prompt;

  @JsonKey(name: r'options', required: true, includeIfNull: false)
  final List<LearningPathItemQuestionOptionsInner> options;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is LearningPathItemQuestion &&
          other.kind == kind &&
          other.prompt == prompt &&
          other.options == options;

  @override
  int get hashCode => kind.hashCode + prompt.hashCode + options.hashCode;

  factory LearningPathItemQuestion.fromJson(Map<String, dynamic> json) =>
      _$LearningPathItemQuestionFromJson(json);

  Map<String, dynamic> toJson() => _$LearningPathItemQuestionToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
