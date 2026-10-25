//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'wellbeing_notice.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class WellbeingNotice {
  /// Returns a new [WellbeingNotice] instance.
  WellbeingNotice({
    required this.id,

    required this.title,

    required this.message,

    required this.link,

    required this.createdAt,

    required this.readAt,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(name: r'title', required: true, includeIfNull: false)
  final String title;

  @JsonKey(name: r'message', required: true, includeIfNull: false)
  final String message;

  @JsonKey(name: r'link', required: true, includeIfNull: false)
  final String link;

  @JsonKey(name: r'created_at', required: true, includeIfNull: false)
  final DateTime createdAt;

  @JsonKey(name: r'read_at', required: true, includeIfNull: true)
  final DateTime? readAt;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is WellbeingNotice &&
          other.id == id &&
          other.title == title &&
          other.message == message &&
          other.link == link &&
          other.createdAt == createdAt &&
          other.readAt == readAt;

  @override
  int get hashCode =>
      id.hashCode +
      title.hashCode +
      message.hashCode +
      link.hashCode +
      createdAt.hashCode +
      (readAt == null ? 0 : readAt.hashCode);

  factory WellbeingNotice.fromJson(Map<String, dynamic> json) =>
      _$WellbeingNoticeFromJson(json);

  Map<String, dynamic> toJson() => _$WellbeingNoticeToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
