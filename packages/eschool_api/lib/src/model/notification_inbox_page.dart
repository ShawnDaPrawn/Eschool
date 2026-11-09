//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/notification_inbox_item.dart';
import 'package:json_annotation/json_annotation.dart';

part 'notification_inbox_page.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class NotificationInboxPage {
  /// Returns a new [NotificationInboxPage] instance.
  NotificationInboxPage({
    required this.schoolId,

    required this.readerId,

    required this.items,

    required this.page,

    required this.lastPage,

    required this.total,
  });

  // minimum: 1
  @JsonKey(name: r'school_id', required: true, includeIfNull: false)
  final int schoolId;

  // minimum: 1
  @JsonKey(name: r'reader_id', required: true, includeIfNull: false)
  final int readerId;

  @JsonKey(name: r'items', required: true, includeIfNull: false)
  final List<NotificationInboxItem> items;

  // minimum: 1
  @JsonKey(name: r'page', required: true, includeIfNull: false)
  final int page;

  // minimum: 1
  @JsonKey(name: r'last_page', required: true, includeIfNull: false)
  final int lastPage;

  // minimum: 0
  @JsonKey(name: r'total', required: true, includeIfNull: false)
  final int total;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is NotificationInboxPage &&
          other.schoolId == schoolId &&
          other.readerId == readerId &&
          other.items == items &&
          other.page == page &&
          other.lastPage == lastPage &&
          other.total == total;

  @override
  int get hashCode =>
      schoolId.hashCode +
      readerId.hashCode +
      items.hashCode +
      page.hashCode +
      lastPage.hashCode +
      total.hashCode;

  factory NotificationInboxPage.fromJson(Map<String, dynamic> json) =>
      _$NotificationInboxPageFromJson(json);

  Map<String, dynamic> toJson() => _$NotificationInboxPageToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
