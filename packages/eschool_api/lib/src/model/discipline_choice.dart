//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'discipline_choice.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class DisciplineChoice {
  /// Returns a new [DisciplineChoice] instance.
  DisciplineChoice({required this.id, required this.name});

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is DisciplineChoice && other.id == id && other.name == name;

  @override
  int get hashCode => id.hashCode + name.hashCode;

  factory DisciplineChoice.fromJson(Map<String, dynamic> json) =>
      _$DisciplineChoiceFromJson(json);

  Map<String, dynamic> toJson() => _$DisciplineChoiceToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
