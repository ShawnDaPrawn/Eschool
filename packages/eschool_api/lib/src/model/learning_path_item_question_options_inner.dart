//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'learning_path_item_question_options_inner.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class LearningPathItemQuestionOptionsInner {
  /// Returns a new [LearningPathItemQuestionOptionsInner] instance.
  LearningPathItemQuestionOptionsInner({required this.id, required this.text});

  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final String id;

  @JsonKey(name: r'text', required: true, includeIfNull: false)
  final String text;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is LearningPathItemQuestionOptionsInner &&
          other.id == id &&
          other.text == text;

  @override
  int get hashCode => id.hashCode + text.hashCode;

  factory LearningPathItemQuestionOptionsInner.fromJson(
    Map<String, dynamic> json,
  ) => _$LearningPathItemQuestionOptionsInnerFromJson(json);

  Map<String, dynamic> toJson() =>
      _$LearningPathItemQuestionOptionsInnerToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
