//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'calendar_feed_created_response_data.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class CalendarFeedCreatedResponseData {
  /// Returns a new [CalendarFeedCreatedResponseData] instance.
  CalendarFeedCreatedResponseData({
    required this.id,

    required this.learnerId,

    required this.includeLessons,

    required this.url,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  // minimum: 1
  @JsonKey(name: r'learner_id', required: true, includeIfNull: false)
  final int learnerId;

  @JsonKey(name: r'include_lessons', required: true, includeIfNull: false)
  final bool includeLessons;

  /// The secret subscription address. Shown only here; treat it like a password.
  @JsonKey(name: r'url', required: true, includeIfNull: false)
  final String url;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is CalendarFeedCreatedResponseData &&
          other.id == id &&
          other.learnerId == learnerId &&
          other.includeLessons == includeLessons &&
          other.url == url;

  @override
  int get hashCode =>
      id.hashCode + learnerId.hashCode + includeLessons.hashCode + url.hashCode;

  factory CalendarFeedCreatedResponseData.fromJson(Map<String, dynamic> json) =>
      _$CalendarFeedCreatedResponseDataFromJson(json);

  Map<String, dynamic> toJson() =>
      _$CalendarFeedCreatedResponseDataToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
