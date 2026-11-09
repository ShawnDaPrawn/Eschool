//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'quiet_hours_response_data.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class QuietHoursResponseData {
  /// Returns a new [QuietHoursResponseData] instance.
  QuietHoursResponseData({
    required this.enabled,

    required this.startsAt,

    required this.endsAt,

    required this.timezone,

    required this.exemptTypes,
  });

  @JsonKey(name: r'enabled', required: true, includeIfNull: false)
  final bool enabled;

  @JsonKey(name: r'starts_at', required: true, includeIfNull: false)
  final String startsAt;

  @JsonKey(name: r'ends_at', required: true, includeIfNull: false)
  final String endsAt;

  @JsonKey(name: r'timezone', required: true, includeIfNull: false)
  final String timezone;

  /// Notice types that are never held.
  @JsonKey(name: r'exempt_types', required: true, includeIfNull: false)
  final List<String> exemptTypes;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is QuietHoursResponseData &&
          other.enabled == enabled &&
          other.startsAt == startsAt &&
          other.endsAt == endsAt &&
          other.timezone == timezone &&
          other.exemptTypes == exemptTypes;

  @override
  int get hashCode =>
      enabled.hashCode +
      startsAt.hashCode +
      endsAt.hashCode +
      timezone.hashCode +
      exemptTypes.hashCode;

  factory QuietHoursResponseData.fromJson(Map<String, dynamic> json) =>
      _$QuietHoursResponseDataFromJson(json);

  Map<String, dynamic> toJson() => _$QuietHoursResponseDataToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
