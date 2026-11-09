//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'practice_test_tutor_review.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class PracticeTestTutorReview {
  /// Returns a new [PracticeTestTutorReview] instance.
  PracticeTestTutorReview({
    required this.groupId,

    required this.topicId,

    required this.sourceVersionIds,
  });

  // minimum: 1
  @JsonKey(name: r'group_id', required: true, includeIfNull: false)
  final int groupId;

  // minimum: 1
  @JsonKey(name: r'topic_id', required: true, includeIfNull: false)
  final int topicId;

  @JsonKey(name: r'source_version_ids', required: true, includeIfNull: false)
  final List<int> sourceVersionIds;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is PracticeTestTutorReview &&
          other.groupId == groupId &&
          other.topicId == topicId &&
          other.sourceVersionIds == sourceVersionIds;

  @override
  int get hashCode =>
      groupId.hashCode + topicId.hashCode + sourceVersionIds.hashCode;

  factory PracticeTestTutorReview.fromJson(Map<String, dynamic> json) =>
      _$PracticeTestTutorReviewFromJson(json);

  Map<String, dynamic> toJson() => _$PracticeTestTutorReviewToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
