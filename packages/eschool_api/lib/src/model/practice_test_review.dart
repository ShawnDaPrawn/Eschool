//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/practice_test_tutor_review.dart';
import 'package:json_annotation/json_annotation.dart';

part 'practice_test_review.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class PracticeTestReview {
  /// Returns a new [PracticeTestReview] instance.
  PracticeTestReview({required this.topicIds, required this.tutor});

  @JsonKey(name: r'topic_ids', required: true, includeIfNull: false)
  final List<int> topicIds;

  @JsonKey(name: r'tutor', required: true, includeIfNull: true)
  final PracticeTestTutorReview? tutor;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is PracticeTestReview &&
          other.topicIds == topicIds &&
          other.tutor == tutor;

  @override
  int get hashCode => topicIds.hashCode + (tutor == null ? 0 : tutor.hashCode);

  factory PracticeTestReview.fromJson(Map<String, dynamic> json) =>
      _$PracticeTestReviewFromJson(json);

  Map<String, dynamic> toJson() => _$PracticeTestReviewToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
