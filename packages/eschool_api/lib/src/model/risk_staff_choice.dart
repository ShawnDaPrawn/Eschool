//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'risk_staff_choice.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class RiskStaffChoice {
  /// Returns a new [RiskStaffChoice] instance.
  RiskStaffChoice({required this.id, required this.name});

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is RiskStaffChoice && other.id == id && other.name == name;

  @override
  int get hashCode => id.hashCode + name.hashCode;

  factory RiskStaffChoice.fromJson(Map<String, dynamic> json) =>
      _$RiskStaffChoiceFromJson(json);

  Map<String, dynamic> toJson() => _$RiskStaffChoiceToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
