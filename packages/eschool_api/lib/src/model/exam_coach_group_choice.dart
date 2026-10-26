//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'exam_coach_group_choice.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ExamCoachGroupChoice {
  /// Returns a new [ExamCoachGroupChoice] instance.
  ExamCoachGroupChoice({
    required this.id,

    required this.name,

    required this.subjectName,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  @JsonKey(name: r'subject_name', required: true, includeIfNull: false)
  final String subjectName;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ExamCoachGroupChoice &&
          other.id == id &&
          other.name == name &&
          other.subjectName == subjectName;

  @override
  int get hashCode => id.hashCode + name.hashCode + subjectName.hashCode;

  factory ExamCoachGroupChoice.fromJson(Map<String, dynamic> json) =>
      _$ExamCoachGroupChoiceFromJson(json);

  Map<String, dynamic> toJson() => _$ExamCoachGroupChoiceToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
