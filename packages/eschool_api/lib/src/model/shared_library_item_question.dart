//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'shared_library_item_question.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SharedLibraryItemQuestion {
  /// Returns a new [SharedLibraryItemQuestion] instance.
  SharedLibraryItemQuestion({
    required this.kind,

    required this.prompt,

    required this.options,

    required this.answers,

    required this.steps,

    required this.explanation,

    required this.maxSeconds,
  });

  @JsonKey(name: r'kind', required: true, includeIfNull: false)
  final String kind;

  @JsonKey(name: r'prompt', required: true, includeIfNull: false)
  final String prompt;

  @JsonKey(name: r'options', required: true, includeIfNull: false)
  final List<Object> options;

  @JsonKey(name: r'answers', required: true, includeIfNull: false)
  final List<String> answers;

  @JsonKey(name: r'steps', required: true, includeIfNull: false)
  final List<Object> steps;

  @JsonKey(name: r'explanation', required: true, includeIfNull: false)
  final String explanation;

  @JsonKey(name: r'max_seconds', required: true, includeIfNull: true)
  final int? maxSeconds;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is SharedLibraryItemQuestion &&
          other.kind == kind &&
          other.prompt == prompt &&
          other.options == options &&
          other.answers == answers &&
          other.steps == steps &&
          other.explanation == explanation &&
          other.maxSeconds == maxSeconds;

  @override
  int get hashCode =>
      kind.hashCode +
      prompt.hashCode +
      options.hashCode +
      answers.hashCode +
      steps.hashCode +
      explanation.hashCode +
      (maxSeconds == null ? 0 : maxSeconds.hashCode);

  factory SharedLibraryItemQuestion.fromJson(Map<String, dynamic> json) =>
      _$SharedLibraryItemQuestionFromJson(json);

  Map<String, dynamic> toJson() => _$SharedLibraryItemQuestionToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
