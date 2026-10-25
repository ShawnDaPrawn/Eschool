//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'staff_private_notice.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class StaffPrivateNotice {
  /// Returns a new [StaffPrivateNotice] instance.
  StaffPrivateNotice({
    required this.id,

    required this.feature,

    required this.title,

    required this.message,

    required this.writable,

    required this.readAt,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(name: r'feature', required: true, includeIfNull: false)
  final String feature;

  @JsonKey(name: r'title', required: true, includeIfNull: false)
  final String title;

  @JsonKey(name: r'message', required: true, includeIfNull: false)
  final String message;

  @JsonKey(name: r'writable', required: true, includeIfNull: false)
  final bool writable;

  @JsonKey(name: r'read_at', required: true, includeIfNull: true)
  final DateTime? readAt;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is StaffPrivateNotice &&
          other.id == id &&
          other.feature == feature &&
          other.title == title &&
          other.message == message &&
          other.writable == writable &&
          other.readAt == readAt;

  @override
  int get hashCode =>
      id.hashCode +
      feature.hashCode +
      title.hashCode +
      message.hashCode +
      writable.hashCode +
      (readAt == null ? 0 : readAt.hashCode);

  factory StaffPrivateNotice.fromJson(Map<String, dynamic> json) =>
      _$StaffPrivateNoticeFromJson(json);

  Map<String, dynamic> toJson() => _$StaffPrivateNoticeToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
