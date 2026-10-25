//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'mastery_subject_choice.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class MasterySubjectChoice {
  /// Returns a new [MasterySubjectChoice] instance.
  MasterySubjectChoice({required this.id, required this.name});

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is MasterySubjectChoice && other.id == id && other.name == name;

  @override
  int get hashCode => id.hashCode + name.hashCode;

  factory MasterySubjectChoice.fromJson(Map<String, dynamic> json) =>
      _$MasterySubjectChoiceFromJson(json);

  Map<String, dynamic> toJson() => _$MasterySubjectChoiceToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
