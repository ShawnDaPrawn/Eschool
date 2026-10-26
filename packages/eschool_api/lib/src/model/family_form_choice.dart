//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'family_form_choice.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class FamilyFormChoice {
  /// Returns a new [FamilyFormChoice] instance.
  FamilyFormChoice({
    required this.id,

    required this.name,

    required this.personId,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  // minimum: 1
  @JsonKey(name: r'person_id', required: true, includeIfNull: false)
  final int personId;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is FamilyFormChoice &&
          other.id == id &&
          other.name == name &&
          other.personId == personId;

  @override
  int get hashCode => id.hashCode + name.hashCode + personId.hashCode;

  factory FamilyFormChoice.fromJson(Map<String, dynamic> json) =>
      _$FamilyFormChoiceFromJson(json);

  Map<String, dynamic> toJson() => _$FamilyFormChoiceToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
