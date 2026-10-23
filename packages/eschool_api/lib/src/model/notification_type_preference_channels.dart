//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/notification_channel_preference.dart';
import 'package:json_annotation/json_annotation.dart';

part 'notification_type_preference_channels.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class NotificationTypePreferenceChannels {
  /// Returns a new [NotificationTypePreferenceChannels] instance.
  NotificationTypePreferenceChannels({this.push, this.email, this.inApp});

  @JsonKey(name: r'push', required: false, includeIfNull: false)
  final NotificationChannelPreference? push;

  @JsonKey(name: r'email', required: false, includeIfNull: false)
  final NotificationChannelPreference? email;

  @JsonKey(name: r'in_app', required: false, includeIfNull: false)
  final NotificationChannelPreference? inApp;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is NotificationTypePreferenceChannels &&
          other.push == push &&
          other.email == email &&
          other.inApp == inApp;

  @override
  int get hashCode => push.hashCode + email.hashCode + inApp.hashCode;

  factory NotificationTypePreferenceChannels.fromJson(
    Map<String, dynamic> json,
  ) => _$NotificationTypePreferenceChannelsFromJson(json);

  Map<String, dynamic> toJson() =>
      _$NotificationTypePreferenceChannelsToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
