//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/staff_private_notice.dart';
import 'package:json_annotation/json_annotation.dart';

part 'staff_private_notice_page.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class StaffPrivateNoticePage {
  /// Returns a new [StaffPrivateNoticePage] instance.
  StaffPrivateNoticePage({
    required this.schoolId,

    required this.readerId,

    required this.writable,

    required this.items,

    required this.page,

    required this.lastPage,

    required this.unreadCount,
  });

  // minimum: 1
  @JsonKey(name: r'school_id', required: true, includeIfNull: false)
  final int schoolId;

  // minimum: 1
  @JsonKey(name: r'reader_id', required: true, includeIfNull: false)
  final int readerId;

  @JsonKey(name: r'writable', required: true, includeIfNull: false)
  final bool writable;

  @JsonKey(name: r'items', required: true, includeIfNull: false)
  final List<StaffPrivateNotice> items;

  // minimum: 1
  @JsonKey(name: r'page', required: true, includeIfNull: false)
  final int page;

  // minimum: 1
  @JsonKey(name: r'last_page', required: true, includeIfNull: false)
  final int lastPage;

  // minimum: 0
  @JsonKey(name: r'unread_count', required: true, includeIfNull: false)
  final int unreadCount;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is StaffPrivateNoticePage &&
          other.schoolId == schoolId &&
          other.readerId == readerId &&
          other.writable == writable &&
          other.items == items &&
          other.page == page &&
          other.lastPage == lastPage &&
          other.unreadCount == unreadCount;

  @override
  int get hashCode =>
      schoolId.hashCode +
      readerId.hashCode +
      writable.hashCode +
      items.hashCode +
      page.hashCode +
      lastPage.hashCode +
      unreadCount.hashCode;

  factory StaffPrivateNoticePage.fromJson(Map<String, dynamic> json) =>
      _$StaffPrivateNoticePageFromJson(json);

  Map<String, dynamic> toJson() => _$StaffPrivateNoticePageToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
