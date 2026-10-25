//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'practice_choice.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class PracticeChoice {
  /// Returns a new [PracticeChoice] instance.
  PracticeChoice({required this.id, required this.name});

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is PracticeChoice && other.id == id && other.name == name;

  @override
  int get hashCode => id.hashCode + name.hashCode;

  factory PracticeChoice.fromJson(Map<String, dynamic> json) =>
      _$PracticeChoiceFromJson(json);

  Map<String, dynamic> toJson() => _$PracticeChoiceToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
