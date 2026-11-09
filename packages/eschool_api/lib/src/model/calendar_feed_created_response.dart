//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/calendar_feed_created_response_data.dart';
import 'package:json_annotation/json_annotation.dart';

part 'calendar_feed_created_response.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class CalendarFeedCreatedResponse {
  /// Returns a new [CalendarFeedCreatedResponse] instance.
  CalendarFeedCreatedResponse({
    required this.error,

    required this.message,

    required this.code,

    required this.data,
  });

  @JsonKey(name: r'error', required: true, includeIfNull: false)
  final bool error;

  @JsonKey(name: r'message', required: true, includeIfNull: false)
  final String message;

  @JsonKey(name: r'code', required: true, includeIfNull: false)
  final int code;

  @JsonKey(name: r'data', required: true, includeIfNull: false)
  final CalendarFeedCreatedResponseData data;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is CalendarFeedCreatedResponse &&
          other.error == error &&
          other.message == message &&
          other.code == code &&
          other.data == data;

  @override
  int get hashCode =>
      error.hashCode + message.hashCode + code.hashCode + data.hashCode;

  factory CalendarFeedCreatedResponse.fromJson(Map<String, dynamic> json) =>
      _$CalendarFeedCreatedResponseFromJson(json);

  Map<String, dynamic> toJson() => _$CalendarFeedCreatedResponseToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
