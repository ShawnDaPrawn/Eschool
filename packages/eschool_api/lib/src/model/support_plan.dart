//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/support_review.dart';
import 'package:eschool_api/src/model/support_disclosure_summary.dart';
import 'package:json_annotation/json_annotation.dart';

part 'support_plan.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SupportPlan {
  /// Returns a new [SupportPlan] instance.
  SupportPlan({
    required this.id,

    required this.learnerId,

    required this.learnerName,

    required this.revision,

    required this.state,

    required this.reviewDueOn,

    required this.startedOn,

    required this.siasStage,

    required this.needs,

    required this.accommodations,

    required this.reviewsHasMore,

    required this.disclosuresHasMore,

    required this.reviews,

    required this.disclosures,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  // minimum: 1
  @JsonKey(name: r'learner_id', required: true, includeIfNull: false)
  final int learnerId;

  @JsonKey(name: r'learner_name', required: true, includeIfNull: false)
  final String learnerName;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @JsonKey(
    name: r'state',
    required: true,
    includeIfNull: false,
    unknownEnumValue: SupportPlanStateEnum.unknownDefaultOpenApi,
  )
  final SupportPlanStateEnum state;

  @JsonKey(name: r'review_due_on', required: true, includeIfNull: true)
  final String? reviewDueOn;

  @JsonKey(name: r'started_on', required: true, includeIfNull: false)
  final String startedOn;

  @JsonKey(name: r'sias_stage', required: true, includeIfNull: false)
  final String siasStage;

  @JsonKey(name: r'needs', required: true, includeIfNull: false)
  final String needs;

  @JsonKey(name: r'accommodations', required: true, includeIfNull: false)
  final String accommodations;

  @JsonKey(name: r'reviews_has_more', required: true, includeIfNull: false)
  final bool reviewsHasMore;

  @JsonKey(name: r'disclosures_has_more', required: true, includeIfNull: false)
  final bool disclosuresHasMore;

  @JsonKey(name: r'reviews', required: true, includeIfNull: false)
  final List<SupportReview> reviews;

  @JsonKey(name: r'disclosures', required: true, includeIfNull: false)
  final List<SupportDisclosureSummary> disclosures;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is SupportPlan &&
          other.id == id &&
          other.learnerId == learnerId &&
          other.learnerName == learnerName &&
          other.revision == revision &&
          other.state == state &&
          other.reviewDueOn == reviewDueOn &&
          other.startedOn == startedOn &&
          other.siasStage == siasStage &&
          other.needs == needs &&
          other.accommodations == accommodations &&
          other.reviewsHasMore == reviewsHasMore &&
          other.disclosuresHasMore == disclosuresHasMore &&
          other.reviews == reviews &&
          other.disclosures == disclosures;

  @override
  int get hashCode =>
      id.hashCode +
      learnerId.hashCode +
      learnerName.hashCode +
      revision.hashCode +
      state.hashCode +
      (reviewDueOn == null ? 0 : reviewDueOn.hashCode) +
      startedOn.hashCode +
      siasStage.hashCode +
      needs.hashCode +
      accommodations.hashCode +
      reviewsHasMore.hashCode +
      disclosuresHasMore.hashCode +
      reviews.hashCode +
      disclosures.hashCode;

  factory SupportPlan.fromJson(Map<String, dynamic> json) =>
      _$SupportPlanFromJson(json);

  Map<String, dynamic> toJson() => _$SupportPlanToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum SupportPlanStateEnum {
  @JsonValue(r'active')
  active(r'active'),
  @JsonValue(r'closed')
  closed(r'closed'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const SupportPlanStateEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
