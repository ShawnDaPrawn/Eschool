//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'exam_coach_exam_choice.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ExamCoachExamChoice {
  /// Returns a new [ExamCoachExamChoice] instance.
  ExamCoachExamChoice({
    required this.id,

    required this.name,

    required this.examOn,

    required this.scheduleSignature,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  @JsonKey(name: r'exam_on', required: true, includeIfNull: false)
  final String examOn;

  @JsonKey(name: r'schedule_signature', required: true, includeIfNull: false)
  final String scheduleSignature;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ExamCoachExamChoice &&
          other.id == id &&
          other.name == name &&
          other.examOn == examOn &&
          other.scheduleSignature == scheduleSignature;

  @override
  int get hashCode =>
      id.hashCode +
      name.hashCode +
      examOn.hashCode +
      scheduleSignature.hashCode;

  factory ExamCoachExamChoice.fromJson(Map<String, dynamic> json) =>
      _$ExamCoachExamChoiceFromJson(json);

  Map<String, dynamic> toJson() => _$ExamCoachExamChoiceToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
