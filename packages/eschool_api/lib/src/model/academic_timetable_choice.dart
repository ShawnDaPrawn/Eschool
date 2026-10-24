//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'academic_timetable_choice.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicTimetableChoice {
  /// Returns a new [AcademicTimetableChoice] instance.
  AcademicTimetableChoice({required this.id, required this.name});

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicTimetableChoice && other.id == id && other.name == name;

  @override
  int get hashCode => id.hashCode + name.hashCode;

  factory AcademicTimetableChoice.fromJson(Map<String, dynamic> json) =>
      _$AcademicTimetableChoiceFromJson(json);

  Map<String, dynamic> toJson() => _$AcademicTimetableChoiceToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
