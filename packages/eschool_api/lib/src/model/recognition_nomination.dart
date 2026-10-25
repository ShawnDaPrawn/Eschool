//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'recognition_nomination.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class RecognitionNomination {
  /// Returns a new [RecognitionNomination] instance.
  RecognitionNomination({
    required this.id,

    required this.revision,

    required this.learnerId,

    required this.learnerName,

    required this.gradeLevelId,

    required this.awardCategoryId,

    required this.awardDefinitionId,

    required this.awardName,

    required this.criteria,

    required this.reason,

    required this.state,

    required this.reviewReason,

    required this.canReview,

    required this.canWithdraw,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  // minimum: 1
  @JsonKey(name: r'learner_id', required: true, includeIfNull: false)
  final int learnerId;

  @JsonKey(name: r'learner_name', required: true, includeIfNull: false)
  final String learnerName;

  // minimum: 1
  @JsonKey(name: r'grade_level_id', required: true, includeIfNull: false)
  final int gradeLevelId;

  // minimum: 1
  @JsonKey(name: r'award_category_id', required: true, includeIfNull: false)
  final int awardCategoryId;

  // minimum: 1
  @JsonKey(name: r'award_definition_id', required: true, includeIfNull: false)
  final int awardDefinitionId;

  @JsonKey(name: r'award_name', required: true, includeIfNull: false)
  final String awardName;

  @JsonKey(name: r'criteria', required: true, includeIfNull: false)
  final String criteria;

  @JsonKey(name: r'reason', required: true, includeIfNull: false)
  final String reason;

  @JsonKey(name: r'state', required: true, includeIfNull: false)
  final String state;

  @JsonKey(name: r'review_reason', required: true, includeIfNull: true)
  final String? reviewReason;

  @JsonKey(name: r'can_review', required: true, includeIfNull: false)
  final bool canReview;

  @JsonKey(name: r'can_withdraw', required: true, includeIfNull: false)
  final bool canWithdraw;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is RecognitionNomination &&
          other.id == id &&
          other.revision == revision &&
          other.learnerId == learnerId &&
          other.learnerName == learnerName &&
          other.gradeLevelId == gradeLevelId &&
          other.awardCategoryId == awardCategoryId &&
          other.awardDefinitionId == awardDefinitionId &&
          other.awardName == awardName &&
          other.criteria == criteria &&
          other.reason == reason &&
          other.state == state &&
          other.reviewReason == reviewReason &&
          other.canReview == canReview &&
          other.canWithdraw == canWithdraw;

  @override
  int get hashCode =>
      id.hashCode +
      revision.hashCode +
      learnerId.hashCode +
      learnerName.hashCode +
      gradeLevelId.hashCode +
      awardCategoryId.hashCode +
      awardDefinitionId.hashCode +
      awardName.hashCode +
      criteria.hashCode +
      reason.hashCode +
      state.hashCode +
      (reviewReason == null ? 0 : reviewReason.hashCode) +
      canReview.hashCode +
      canWithdraw.hashCode;

  factory RecognitionNomination.fromJson(Map<String, dynamic> json) =>
      _$RecognitionNominationFromJson(json);

  Map<String, dynamic> toJson() => _$RecognitionNominationToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
