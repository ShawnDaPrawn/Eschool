//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'update_my_quiet_hours_request.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class UpdateMyQuietHoursRequest {
  /// Returns a new [UpdateMyQuietHoursRequest] instance.
  UpdateMyQuietHoursRequest({
    required this.enabled,

    required this.startsAt,

    required this.endsAt,

    this.timezone,
  });

  @JsonKey(name: r'enabled', required: true, includeIfNull: false)
  final bool enabled;

  @JsonKey(name: r'starts_at', required: true, includeIfNull: false)
  final String startsAt;

  @JsonKey(name: r'ends_at', required: true, includeIfNull: false)
  final String endsAt;

  /// An IANA time zone such as Africa/Johannesburg.
  @JsonKey(name: r'timezone', required: false, includeIfNull: false)
  final String? timezone;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is UpdateMyQuietHoursRequest &&
          other.enabled == enabled &&
          other.startsAt == startsAt &&
          other.endsAt == endsAt &&
          other.timezone == timezone;

  @override
  int get hashCode =>
      enabled.hashCode +
      startsAt.hashCode +
      endsAt.hashCode +
      (timezone == null ? 0 : timezone.hashCode);

  factory UpdateMyQuietHoursRequest.fromJson(Map<String, dynamic> json) =>
      _$UpdateMyQuietHoursRequestFromJson(json);

  Map<String, dynamic> toJson() => _$UpdateMyQuietHoursRequestToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
