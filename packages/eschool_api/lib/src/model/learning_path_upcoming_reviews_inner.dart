//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'learning_path_upcoming_reviews_inner.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class LearningPathUpcomingReviewsInner {
  /// Returns a new [LearningPathUpcomingReviewsInner] instance.
  LearningPathUpcomingReviewsInner({
    required this.topicId,

    required this.name,

    required this.dueOn,
  });

  // minimum: 1
  @JsonKey(name: r'topic_id', required: true, includeIfNull: false)
  final int topicId;

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  @JsonKey(name: r'due_on', required: true, includeIfNull: false)
  final String dueOn;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is LearningPathUpcomingReviewsInner &&
          other.topicId == topicId &&
          other.name == name &&
          other.dueOn == dueOn;

  @override
  int get hashCode => topicId.hashCode + name.hashCode + dueOn.hashCode;

  factory LearningPathUpcomingReviewsInner.fromJson(
    Map<String, dynamic> json,
  ) => _$LearningPathUpcomingReviewsInnerFromJson(json);

  Map<String, dynamic> toJson() =>
      _$LearningPathUpcomingReviewsInnerToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
