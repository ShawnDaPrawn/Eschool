//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'wellbeing_category.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class WellbeingCategory {
  /// Returns a new [WellbeingCategory] instance.
  WellbeingCategory({
    required this.id,

    required this.name,

    required this.emergencyGuidance,

    required this.active,

    required this.revision,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  @JsonKey(name: r'emergency_guidance', required: true, includeIfNull: false)
  final String emergencyGuidance;

  @JsonKey(name: r'active', required: true, includeIfNull: false)
  final bool active;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is WellbeingCategory &&
          other.id == id &&
          other.name == name &&
          other.emergencyGuidance == emergencyGuidance &&
          other.active == active &&
          other.revision == revision;

  @override
  int get hashCode =>
      id.hashCode +
      name.hashCode +
      emergencyGuidance.hashCode +
      active.hashCode +
      revision.hashCode;

  factory WellbeingCategory.fromJson(Map<String, dynamic> json) =>
      _$WellbeingCategoryFromJson(json);

  Map<String, dynamic> toJson() => _$WellbeingCategoryToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
