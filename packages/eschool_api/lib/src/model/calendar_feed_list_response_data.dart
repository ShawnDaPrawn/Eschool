//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/calendar_feed.dart';
import 'package:json_annotation/json_annotation.dart';

part 'calendar_feed_list_response_data.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class CalendarFeedListResponseData {
  /// Returns a new [CalendarFeedListResponseData] instance.
  CalendarFeedListResponseData({required this.items});

  @JsonKey(name: r'items', required: true, includeIfNull: false)
  final List<CalendarFeed> items;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is CalendarFeedListResponseData && other.items == items;

  @override
  int get hashCode => items.hashCode;

  factory CalendarFeedListResponseData.fromJson(Map<String, dynamic> json) =>
      _$CalendarFeedListResponseDataFromJson(json);

  Map<String, dynamic> toJson() => _$CalendarFeedListResponseDataToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
