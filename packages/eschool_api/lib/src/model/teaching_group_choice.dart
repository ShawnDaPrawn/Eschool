//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'teaching_group_choice.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class TeachingGroupChoice {
  /// Returns a new [TeachingGroupChoice] instance.
  TeachingGroupChoice({
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
      other is TeachingGroupChoice &&
          other.id == id &&
          other.name == name &&
          other.subjectName == subjectName;

  @override
  int get hashCode => id.hashCode + name.hashCode + subjectName.hashCode;

  factory TeachingGroupChoice.fromJson(Map<String, dynamic> json) =>
      _$TeachingGroupChoiceFromJson(json);

  Map<String, dynamic> toJson() => _$TeachingGroupChoiceToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
