//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'calendar_feed.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class CalendarFeed {
  /// Returns a new [CalendarFeed] instance.
  CalendarFeed({
    required this.id,

    required this.learnerId,

    required this.learnerName,

    required this.includeLessons,

    required this.createdAt,

    required this.lastUsedAt,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  // minimum: 1
  @JsonKey(name: r'learner_id', required: true, includeIfNull: false)
  final int learnerId;

  @JsonKey(name: r'learner_name', required: true, includeIfNull: false)
  final String learnerName;

  @JsonKey(name: r'include_lessons', required: true, includeIfNull: false)
  final bool includeLessons;

  @JsonKey(name: r'created_at', required: true, includeIfNull: false)
  final DateTime createdAt;

  @JsonKey(name: r'last_used_at', required: true, includeIfNull: true)
  final DateTime? lastUsedAt;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is CalendarFeed &&
          other.id == id &&
          other.learnerId == learnerId &&
          other.learnerName == learnerName &&
          other.includeLessons == includeLessons &&
          other.createdAt == createdAt &&
          other.lastUsedAt == lastUsedAt;

  @override
  int get hashCode =>
      id.hashCode +
      learnerId.hashCode +
      learnerName.hashCode +
      includeLessons.hashCode +
      createdAt.hashCode +
      (lastUsedAt == null ? 0 : lastUsedAt.hashCode);

  factory CalendarFeed.fromJson(Map<String, dynamic> json) =>
      _$CalendarFeedFromJson(json);

  Map<String, dynamic> toJson() => _$CalendarFeedToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
