//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'practice_set_question_diagram.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class PracticeSetQuestionDiagram {
  /// Returns a new [PracticeSetQuestionDiagram] instance.
  PracticeSetQuestionDiagram({required this.width, required this.height});

  // minimum: 1
  // maximum: 4096
  @JsonKey(name: r'width', required: true, includeIfNull: false)
  final int width;

  // minimum: 1
  // maximum: 4096
  @JsonKey(name: r'height', required: true, includeIfNull: false)
  final int height;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is PracticeSetQuestionDiagram &&
          other.width == width &&
          other.height == height;

  @override
  int get hashCode => width.hashCode + height.hashCode;

  factory PracticeSetQuestionDiagram.fromJson(Map<String, dynamic> json) =>
      _$PracticeSetQuestionDiagramFromJson(json);

  Map<String, dynamic> toJson() => _$PracticeSetQuestionDiagramToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
