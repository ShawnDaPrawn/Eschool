//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'incident_public_configuration_categories_inner.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class IncidentPublicConfigurationCategoriesInner {
  /// Returns a new [IncidentPublicConfigurationCategoriesInner] instance.
  IncidentPublicConfigurationCategoriesInner({
    required this.id,

    required this.name,

    required this.emergencyGuidance,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  @JsonKey(name: r'emergency_guidance', required: true, includeIfNull: false)
  final String emergencyGuidance;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is IncidentPublicConfigurationCategoriesInner &&
          other.id == id &&
          other.name == name &&
          other.emergencyGuidance == emergencyGuidance;

  @override
  int get hashCode => id.hashCode + name.hashCode + emergencyGuidance.hashCode;

  factory IncidentPublicConfigurationCategoriesInner.fromJson(
    Map<String, dynamic> json,
  ) => _$IncidentPublicConfigurationCategoriesInnerFromJson(json);

  Map<String, dynamic> toJson() =>
      _$IncidentPublicConfigurationCategoriesInnerToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
