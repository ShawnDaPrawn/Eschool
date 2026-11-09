//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/emergency_roll_call_totals.dart';
import 'package:eschool_api/src/model/emergency_roll_call_class.dart';
import 'package:json_annotation/json_annotation.dart';

part 'emergency_roll_call.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class EmergencyRollCall {
  /// Returns a new [EmergencyRollCall] instance.
  EmergencyRollCall({
    required this.alertId,

    required this.drill,

    required this.status,

    required this.classes,

    required this.totals,

    required this.visitorsOnSite,
  });

  @JsonKey(name: r'alert_id', required: true, includeIfNull: false)
  final String alertId;

  @JsonKey(name: r'drill', required: true, includeIfNull: false)
  final bool drill;

  @JsonKey(
    name: r'status',
    required: true,
    includeIfNull: false,
    unknownEnumValue: EmergencyRollCallStatusEnum.unknownDefaultOpenApi,
  )
  final EmergencyRollCallStatusEnum status;

  @JsonKey(name: r'classes', required: true, includeIfNull: false)
  final List<EmergencyRollCallClass> classes;

  @JsonKey(name: r'totals', required: true, includeIfNull: false)
  final EmergencyRollCallTotals totals;

  /// Only for staff who see every class.
  // minimum: 0
  @JsonKey(name: r'visitors_on_site', required: true, includeIfNull: true)
  final int? visitorsOnSite;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is EmergencyRollCall &&
          other.alertId == alertId &&
          other.drill == drill &&
          other.status == status &&
          other.classes == classes &&
          other.totals == totals &&
          other.visitorsOnSite == visitorsOnSite;

  @override
  int get hashCode =>
      alertId.hashCode +
      drill.hashCode +
      status.hashCode +
      classes.hashCode +
      totals.hashCode +
      (visitorsOnSite == null ? 0 : visitorsOnSite.hashCode);

  factory EmergencyRollCall.fromJson(Map<String, dynamic> json) =>
      _$EmergencyRollCallFromJson(json);

  Map<String, dynamic> toJson() => _$EmergencyRollCallToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum EmergencyRollCallStatusEnum {
  @JsonValue(r'active')
  active(r'active'),
  @JsonValue(r'all_clear')
  allClear(r'all_clear'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const EmergencyRollCallStatusEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
