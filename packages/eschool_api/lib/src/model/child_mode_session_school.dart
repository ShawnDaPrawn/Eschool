//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'child_mode_session_school.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ChildModeSessionSchool {
  /// Returns a new [ChildModeSessionSchool] instance.
  ChildModeSessionSchool({required this.id, required this.name});

  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ChildModeSessionSchool && other.id == id && other.name == name;

  @override
  int get hashCode => id.hashCode + name.hashCode;

  factory ChildModeSessionSchool.fromJson(Map<String, dynamic> json) =>
      _$ChildModeSessionSchoolFromJson(json);

  Map<String, dynamic> toJson() => _$ChildModeSessionSchoolToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
