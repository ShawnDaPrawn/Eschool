//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/practice_question.dart';
import 'package:eschool_api/src/model/practice_review.dart';
import 'package:json_annotation/json_annotation.dart';

part 'practice_set_entry.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class PracticeSetEntry {
  /// Returns a new [PracticeSetEntry] instance.
  PracticeSetEntry({
    required this.id,

    required this.itemId,

    required this.topicId,

    required this.sourceVersionId,

    required this.estimatedSeconds,

    required this.definition,

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

  @JsonKey(name: r'definition', required: true, includeIfNull: false)
  final PracticeQuestion definition;

  @JsonKey(name: r'review', required: true, includeIfNull: true)
  final PracticeReview? review;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is PracticeSetEntry &&
          other.id == id &&
          other.itemId == itemId &&
          other.topicId == topicId &&
          other.sourceVersionId == sourceVersionId &&
          other.estimatedSeconds == estimatedSeconds &&
          other.definition == definition &&
          other.review == review;

  @override
  int get hashCode =>
      id.hashCode +
      itemId.hashCode +
      topicId.hashCode +
      sourceVersionId.hashCode +
      estimatedSeconds.hashCode +
      definition.hashCode +
      (review == null ? 0 : review.hashCode);

  factory PracticeSetEntry.fromJson(Map<String, dynamic> json) =>
      _$PracticeSetEntryFromJson(json);

  Map<String, dynamic> toJson() => _$PracticeSetEntryToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
