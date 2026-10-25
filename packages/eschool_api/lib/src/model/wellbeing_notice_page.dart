//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/wellbeing_notice.dart';
import 'package:json_annotation/json_annotation.dart';

part 'wellbeing_notice_page.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class WellbeingNoticePage {
  /// Returns a new [WellbeingNoticePage] instance.
  WellbeingNoticePage({
    required this.items,

    required this.page,

    required this.lastPage,

    required this.unreadCount,
  });

  @JsonKey(name: r'items', required: true, includeIfNull: false)
  final List<WellbeingNotice> items;

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
      other is WellbeingNoticePage &&
          other.items == items &&
          other.page == page &&
          other.lastPage == lastPage &&
          other.unreadCount == unreadCount;

  @override
  int get hashCode =>
      items.hashCode + page.hashCode + lastPage.hashCode + unreadCount.hashCode;

  factory WellbeingNoticePage.fromJson(Map<String, dynamic> json) =>
      _$WellbeingNoticePageFromJson(json);

  Map<String, dynamic> toJson() => _$WellbeingNoticePageToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
