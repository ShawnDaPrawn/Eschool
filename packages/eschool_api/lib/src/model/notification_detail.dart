//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'notification_detail.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class NotificationDetail {
  /// Returns a new [NotificationDetail] instance.
  NotificationDetail({
    required this.id,

    required this.type,

    required this.title,

    required this.excerpt,

    required this.target,

    required this.hasBody,

    required this.date,

    required this.body,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  /// The notification type, for example `academics.exam_changed`.
  @JsonKey(name: r'type', required: true, includeIfNull: false)
  final String type;

  @JsonKey(name: r'title', required: true, includeIfNull: false)
  final String title;

  /// The collapsed row's text.
  @JsonKey(name: r'excerpt', required: true, includeIfNull: false)
  final String excerpt;

  /// The app path the notice opens, for example `/calendar`.
  @JsonKey(name: r'target', required: true, includeIfNull: false)
  final String target;

  /// Whether getMyNotification has a fuller text than the excerpt.
  @JsonKey(name: r'has_body', required: true, includeIfNull: false)
  final bool hasBody;

  @JsonKey(name: r'date', required: true, includeIfNull: false)
  final DateTime date;

  /// The full text; the excerpt when has_body is false.
  @JsonKey(name: r'body', required: true, includeIfNull: false)
  final String body;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is NotificationDetail &&
          other.id == id &&
          other.type == type &&
          other.title == title &&
          other.excerpt == excerpt &&
          other.target == target &&
          other.hasBody == hasBody &&
          other.date == date &&
          other.body == body;

  @override
  int get hashCode =>
      id.hashCode +
      type.hashCode +
      title.hashCode +
      excerpt.hashCode +
      target.hashCode +
      hasBody.hashCode +
      date.hashCode +
      body.hashCode;

  factory NotificationDetail.fromJson(Map<String, dynamic> json) =>
      _$NotificationDetailFromJson(json);

  Map<String, dynamic> toJson() => _$NotificationDetailToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
