//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'family_form_group_choice.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class FamilyFormGroupChoice {
  /// Returns a new [FamilyFormGroupChoice] instance.
  FamilyFormGroupChoice({
    required this.id,

    required this.name,

    required this.sessionYearId,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  // minimum: 1
  @JsonKey(name: r'session_year_id', required: true, includeIfNull: false)
  final int sessionYearId;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is FamilyFormGroupChoice &&
          other.id == id &&
          other.name == name &&
          other.sessionYearId == sessionYearId;

  @override
  int get hashCode => id.hashCode + name.hashCode + sessionYearId.hashCode;

  factory FamilyFormGroupChoice.fromJson(Map<String, dynamic> json) =>
      _$FamilyFormGroupChoiceFromJson(json);

  Map<String, dynamic> toJson() => _$FamilyFormGroupChoiceToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
