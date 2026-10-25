//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'staff_private_notice_read.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class StaffPrivateNoticeRead {
  /// Returns a new [StaffPrivateNoticeRead] instance.
  StaffPrivateNoticeRead({
    required this.id,

    required this.schoolId,

    required this.readerId,

    required this.readAt,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  // minimum: 1
  @JsonKey(name: r'school_id', required: true, includeIfNull: false)
  final int schoolId;

  // minimum: 1
  @JsonKey(name: r'reader_id', required: true, includeIfNull: false)
  final int readerId;

  @JsonKey(name: r'read_at', required: true, includeIfNull: false)
  final DateTime readAt;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is StaffPrivateNoticeRead &&
          other.id == id &&
          other.schoolId == schoolId &&
          other.readerId == readerId &&
          other.readAt == readAt;

  @override
  int get hashCode =>
      id.hashCode + schoolId.hashCode + readerId.hashCode + readAt.hashCode;

  factory StaffPrivateNoticeRead.fromJson(Map<String, dynamic> json) =>
      _$StaffPrivateNoticeReadFromJson(json);

  Map<String, dynamic> toJson() => _$StaffPrivateNoticeReadToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
