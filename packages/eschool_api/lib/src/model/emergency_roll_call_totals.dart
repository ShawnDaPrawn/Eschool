//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'emergency_roll_call_totals.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class EmergencyRollCallTotals {
  /// Returns a new [EmergencyRollCallTotals] instance.
  EmergencyRollCallTotals({
    required this.accounted,

    required this.unaccounted,

    required this.released,
  });

  // minimum: 0
  @JsonKey(name: r'accounted', required: true, includeIfNull: false)
  final int accounted;

  // minimum: 0
  @JsonKey(name: r'unaccounted', required: true, includeIfNull: false)
  final int unaccounted;

  // minimum: 0
  @JsonKey(name: r'released', required: true, includeIfNull: false)
  final int released;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is EmergencyRollCallTotals &&
          other.accounted == accounted &&
          other.unaccounted == unaccounted &&
          other.released == released;

  @override
  int get hashCode =>
      accounted.hashCode + unaccounted.hashCode + released.hashCode;

  factory EmergencyRollCallTotals.fromJson(Map<String, dynamic> json) =>
      _$EmergencyRollCallTotalsFromJson(json);

  Map<String, dynamic> toJson() => _$EmergencyRollCallTotalsToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
