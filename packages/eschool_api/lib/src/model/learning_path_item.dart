//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/learning_path_item_question.dart';
import 'package:json_annotation/json_annotation.dart';

part 'learning_path_item.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class LearningPathItem {
  /// Returns a new [LearningPathItem] instance.
  LearningPathItem({
    required this.id,

    required this.purpose,

    required this.position,

    required this.question,

    required this.answered,

    required this.correct,

    required this.explanation,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(
    name: r'purpose',
    required: true,
    includeIfNull: false,
    unknownEnumValue: LearningPathItemPurposeEnum.unknownDefaultOpenApi,
  )
  final LearningPathItemPurposeEnum purpose;

  // minimum: 1
  // maximum: 10
  @JsonKey(name: r'position', required: true, includeIfNull: false)
  final int position;

  @JsonKey(name: r'question', required: true, includeIfNull: false)
  final LearningPathItemQuestion question;

  @JsonKey(name: r'answered', required: true, includeIfNull: false)
  final bool answered;

  @JsonKey(name: r'correct', required: true, includeIfNull: true)
  final bool? correct;

  @JsonKey(name: r'explanation', required: true, includeIfNull: true)
  final String? explanation;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is LearningPathItem &&
          other.id == id &&
          other.purpose == purpose &&
          other.position == position &&
          other.question == question &&
          other.answered == answered &&
          other.correct == correct &&
          other.explanation == explanation;

  @override
  int get hashCode =>
      id.hashCode +
      purpose.hashCode +
      position.hashCode +
      question.hashCode +
      answered.hashCode +
      (correct == null ? 0 : correct.hashCode) +
      (explanation == null ? 0 : explanation.hashCode);

  factory LearningPathItem.fromJson(Map<String, dynamic> json) =>
      _$LearningPathItemFromJson(json);

  Map<String, dynamic> toJson() => _$LearningPathItemToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum LearningPathItemPurposeEnum {
  @JsonValue(r'practice')
  practice(r'practice'),
  @JsonValue(r'check')
  check(r'check'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const LearningPathItemPurposeEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
