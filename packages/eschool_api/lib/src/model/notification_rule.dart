//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'notification_rule.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class NotificationRule {
  /// Returns a new [NotificationRule] instance.
  NotificationRule({
    required this.type,

    required this.channel,

    required this.mode,
  });

  /// The notification type's key, for example `fees.invoice`.
  @JsonKey(name: r'type', required: true, includeIfNull: false)
  final String type;

  /// `push`, `email` or `in_app`.
  @JsonKey(name: r'channel', required: true, includeIfNull: false)
  final String channel;

  /// `mandatory`, `default_on`, `default_off` or `off`.
  @JsonKey(name: r'mode', required: true, includeIfNull: false)
  final String mode;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is NotificationRule &&
          other.type == type &&
          other.channel == channel &&
          other.mode == mode;

  @override
  int get hashCode => type.hashCode + channel.hashCode + mode.hashCode;

  factory NotificationRule.fromJson(Map<String, dynamic> json) =>
      _$NotificationRuleFromJson(json);

  Map<String, dynamic> toJson() => _$NotificationRuleToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
