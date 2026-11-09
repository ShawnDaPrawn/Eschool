//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/notification_inbox_page.dart';
import 'package:json_annotation/json_annotation.dart';

part 'notification_inbox_response.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class NotificationInboxResponse {
  /// Returns a new [NotificationInboxResponse] instance.
  NotificationInboxResponse({
    required this.error,

    required this.message,

    required this.data,

    required this.code,
  });

  /// Always false.
  @JsonKey(name: r'error', required: true, includeIfNull: false)
  final bool error;

  @JsonKey(name: r'message', required: true, includeIfNull: false)
  final String message;

  @JsonKey(name: r'data', required: true, includeIfNull: false)
  final NotificationInboxPage data;

  /// Always 200.
  @JsonKey(name: r'code', required: true, includeIfNull: false)
  final int code;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is NotificationInboxResponse &&
          other.error == error &&
          other.message == message &&
          other.data == data &&
          other.code == code;

  @override
  int get hashCode =>
      error.hashCode + message.hashCode + data.hashCode + code.hashCode;

  factory NotificationInboxResponse.fromJson(Map<String, dynamic> json) =>
      _$NotificationInboxResponseFromJson(json);

  Map<String, dynamic> toJson() => _$NotificationInboxResponseToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
