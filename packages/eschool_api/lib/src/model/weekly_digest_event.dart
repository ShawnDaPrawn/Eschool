//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'weekly_digest_event.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class WeeklyDigestEvent {
  /// Returns a new [WeeklyDigestEvent] instance.
  WeeklyDigestEvent({
    required this.eventId,

    required this.date,

    required this.title,
  });

  // minimum: 1
  @JsonKey(name: r'event_id', required: true, includeIfNull: false)
  final int eventId;

  @JsonKey(name: r'date', required: true, includeIfNull: false)
  final String date;

  @JsonKey(name: r'title', required: true, includeIfNull: false)
  final String title;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is WeeklyDigestEvent &&
          other.eventId == eventId &&
          other.date == date &&
          other.title == title;

  @override
  int get hashCode => eventId.hashCode + date.hashCode + title.hashCode;

  factory WeeklyDigestEvent.fromJson(Map<String, dynamic> json) =>
      _$WeeklyDigestEventFromJson(json);

  Map<String, dynamic> toJson() => _$WeeklyDigestEventToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
