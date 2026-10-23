//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/notification_type_preference_channels.dart';
import 'package:json_annotation/json_annotation.dart';

part 'notification_type_preference.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class NotificationTypePreference {
  /// Returns a new [NotificationTypePreference] instance.
  NotificationTypePreference({
    required this.type,

    required this.label,

    required this.module,

    required this.channels,
  });

  @JsonKey(name: r'type', required: true, includeIfNull: false)
  final String type;

  @JsonKey(name: r'label', required: true, includeIfNull: false)
  final String label;

  @JsonKey(name: r'module', required: true, includeIfNull: true)
  final String? module;

  @JsonKey(name: r'channels', required: true, includeIfNull: false)
  final NotificationTypePreferenceChannels channels;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is NotificationTypePreference &&
          other.type == type &&
          other.label == label &&
          other.module == module &&
          other.channels == channels;

  @override
  int get hashCode =>
      type.hashCode +
      label.hashCode +
      (module == null ? 0 : module.hashCode) +
      channels.hashCode;

  factory NotificationTypePreference.fromJson(Map<String, dynamic> json) =>
      _$NotificationTypePreferenceFromJson(json);

  Map<String, dynamic> toJson() => _$NotificationTypePreferenceToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
