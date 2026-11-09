//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'calendar_feed_revoked_response_data.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class CalendarFeedRevokedResponseData {
  /// Returns a new [CalendarFeedRevokedResponseData] instance.
  CalendarFeedRevokedResponseData({required this.id, required this.revoked});

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(name: r'revoked', required: true, includeIfNull: false)
  final bool revoked;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is CalendarFeedRevokedResponseData &&
          other.id == id &&
          other.revoked == revoked;

  @override
  int get hashCode => id.hashCode + revoked.hashCode;

  factory CalendarFeedRevokedResponseData.fromJson(Map<String, dynamic> json) =>
      _$CalendarFeedRevokedResponseDataFromJson(json);

  Map<String, dynamic> toJson() =>
      _$CalendarFeedRevokedResponseDataToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
