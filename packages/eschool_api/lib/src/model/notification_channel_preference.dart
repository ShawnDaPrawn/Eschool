//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'notification_channel_preference.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class NotificationChannelPreference {
  /// Returns a new [NotificationChannelPreference] instance.
  NotificationChannelPreference({
    required this.enabled,

    required this.locked,

    required this.reason,
  });

  @JsonKey(name: r'enabled', required: true, includeIfNull: false)
  final bool enabled;

  @JsonKey(name: r'locked', required: true, includeIfNull: false)
  final bool locked;

  @JsonKey(name: r'reason', required: true, includeIfNull: true)
  final String? reason;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is NotificationChannelPreference &&
          other.enabled == enabled &&
          other.locked == locked &&
          other.reason == reason;

  @override
  int get hashCode =>
      enabled.hashCode +
      locked.hashCode +
      (reason == null ? 0 : reason.hashCode);

  factory NotificationChannelPreference.fromJson(Map<String, dynamic> json) =>
      _$NotificationChannelPreferenceFromJson(json);

  Map<String, dynamic> toJson() => _$NotificationChannelPreferenceToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
