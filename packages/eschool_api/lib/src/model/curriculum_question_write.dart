//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'curriculum_question_write.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class CurriculumQuestionWrite {
  /// Returns a new [CurriculumQuestionWrite] instance.
  CurriculumQuestionWrite({
    required this.number,

    required this.maxMark,

    required this.topicKeys,
  });

  @JsonKey(name: r'number', required: true, includeIfNull: false)
  final String number;

  @JsonKey(name: r'max_mark', required: true, includeIfNull: false)
  final String maxMark;

  @JsonKey(name: r'topic_keys', required: true, includeIfNull: false)
  final List<String> topicKeys;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is CurriculumQuestionWrite &&
          other.number == number &&
          other.maxMark == maxMark &&
          other.topicKeys == topicKeys;

  @override
  int get hashCode => number.hashCode + maxMark.hashCode + topicKeys.hashCode;

  factory CurriculumQuestionWrite.fromJson(Map<String, dynamic> json) =>
      _$CurriculumQuestionWriteFromJson(json);

  Map<String, dynamic> toJson() => _$CurriculumQuestionWriteToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
