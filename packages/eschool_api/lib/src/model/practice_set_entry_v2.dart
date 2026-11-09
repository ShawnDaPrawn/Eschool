//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/practice_set_question.dart';
import 'package:eschool_api/src/model/practice_review_v2.dart';
import 'package:json_annotation/json_annotation.dart';

part 'practice_set_entry_v2.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class PracticeSetEntryV2 {
  /// Returns a new [PracticeSetEntryV2] instance.
  PracticeSetEntryV2({
    required this.id,

    required this.itemId,

    required this.topicId,

    required this.sourceVersionId,

    required this.estimatedSeconds,

    required this.question,

    required this.review,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  // minimum: 1
  @JsonKey(name: r'item_id', required: true, includeIfNull: false)
  final int itemId;

  // minimum: 1
  @JsonKey(name: r'topic_id', required: true, includeIfNull: false)
  final int topicId;

  // minimum: 1
  @JsonKey(name: r'source_version_id', required: true, includeIfNull: false)
  final int sourceVersionId;

  // minimum: 60
  // maximum: 300
  @JsonKey(name: r'estimated_seconds', required: true, includeIfNull: false)
  final int estimatedSeconds;

  @JsonKey(name: r'question', required: true, includeIfNull: false)
  final PracticeSetQuestion question;

  @JsonKey(name: r'review', required: true, includeIfNull: true)
  final PracticeReviewV2? review;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is PracticeSetEntryV2 &&
          other.id == id &&
          other.itemId == itemId &&
          other.topicId == topicId &&
          other.sourceVersionId == sourceVersionId &&
          other.estimatedSeconds == estimatedSeconds &&
          other.question == question &&
          other.review == review;

  @override
  int get hashCode =>
      id.hashCode +
      itemId.hashCode +
      topicId.hashCode +
      sourceVersionId.hashCode +
      estimatedSeconds.hashCode +
      question.hashCode +
      (review == null ? 0 : review.hashCode);

  factory PracticeSetEntryV2.fromJson(Map<String, dynamic> json) =>
      _$PracticeSetEntryV2FromJson(json);

  Map<String, dynamic> toJson() => _$PracticeSetEntryV2ToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
