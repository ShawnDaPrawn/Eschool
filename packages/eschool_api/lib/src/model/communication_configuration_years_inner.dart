//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'communication_configuration_years_inner.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class CommunicationConfigurationYearsInner {
  /// Returns a new [CommunicationConfigurationYearsInner] instance.
  CommunicationConfigurationYearsInner({required this.id, required this.name});

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is CommunicationConfigurationYearsInner &&
          other.id == id &&
          other.name == name;

  @override
  int get hashCode => id.hashCode + name.hashCode;

  factory CommunicationConfigurationYearsInner.fromJson(
    Map<String, dynamic> json,
  ) => _$CommunicationConfigurationYearsInnerFromJson(json);

  Map<String, dynamic> toJson() =>
      _$CommunicationConfigurationYearsInnerToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
