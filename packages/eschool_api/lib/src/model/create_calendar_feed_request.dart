//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'create_calendar_feed_request.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class CreateCalendarFeedRequest {
  /// Returns a new [CreateCalendarFeedRequest] instance.
  CreateCalendarFeedRequest({
    required this.learnerId,

    this.includeLessons = false,
  });

  // minimum: 1
  @JsonKey(name: r'learner_id', required: true, includeIfNull: false)
  final int learnerId;

  /// Exams are always included; lessons for the next four weeks only when true.
  @JsonKey(
    defaultValue: false,
    name: r'include_lessons',
    required: false,
    includeIfNull: false,
  )
  final bool? includeLessons;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is CreateCalendarFeedRequest &&
          other.learnerId == learnerId &&
          other.includeLessons == includeLessons;

  @override
  int get hashCode => learnerId.hashCode + includeLessons.hashCode;

  factory CreateCalendarFeedRequest.fromJson(Map<String, dynamic> json) =>
      _$CreateCalendarFeedRequestFromJson(json);

  Map<String, dynamic> toJson() => _$CreateCalendarFeedRequestToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
