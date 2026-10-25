//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/incident_public_configuration_categories_inner.dart';
import 'package:json_annotation/json_annotation.dart';

part 'incident_public_configuration.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class IncidentPublicConfiguration {
  /// Returns a new [IncidentPublicConfiguration] instance.
  IncidentPublicConfiguration({
    required this.emergencyGuidance,

    required this.categories,
  });

  @JsonKey(name: r'emergency_guidance', required: true, includeIfNull: false)
  final String emergencyGuidance;

  @JsonKey(name: r'categories', required: true, includeIfNull: false)
  final List<IncidentPublicConfigurationCategoriesInner> categories;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is IncidentPublicConfiguration &&
          other.emergencyGuidance == emergencyGuidance &&
          other.categories == categories;

  @override
  int get hashCode => emergencyGuidance.hashCode + categories.hashCode;

  factory IncidentPublicConfiguration.fromJson(Map<String, dynamic> json) =>
      _$IncidentPublicConfigurationFromJson(json);

  Map<String, dynamic> toJson() => _$IncidentPublicConfigurationToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
