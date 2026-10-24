//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'curriculum_question.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class CurriculumQuestion {
  /// Returns a new [CurriculumQuestion] instance.
  CurriculumQuestion({
    required this.id,

    required this.number,

    required this.maxMark,

    required this.topicKeys,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(name: r'number', required: true, includeIfNull: false)
  final String number;

  @JsonKey(name: r'max_mark', required: true, includeIfNull: false)
  final String maxMark;

  @JsonKey(name: r'topic_keys', required: true, includeIfNull: false)
  final List<String> topicKeys;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is CurriculumQuestion &&
          other.id == id &&
          other.number == number &&
          other.maxMark == maxMark &&
          other.topicKeys == topicKeys;

  @override
  int get hashCode =>
      id.hashCode + number.hashCode + maxMark.hashCode + topicKeys.hashCode;

  factory CurriculumQuestion.fromJson(Map<String, dynamic> json) =>
      _$CurriculumQuestionFromJson(json);

  Map<String, dynamic> toJson() => _$CurriculumQuestionToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
