//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/bursary_application_household.dart';
import 'package:eschool_api/src/model/bursary_application_documents_inner.dart';
import 'package:eschool_api/src/model/bursary_review_events_inner.dart';
import 'package:json_annotation/json_annotation.dart';

part 'bursary_review.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class BursaryReview {
  /// Returns a new [BursaryReview] instance.
  BursaryReview({
    required this.id,

    required this.learnerId,

    required this.sessionYearId,

    required this.sessionYear,

    required this.state,

    required this.revision,

    required this.submittedAt,

    required this.decidedAt,

    required this.awardKind,

    required this.awardValue,

    required this.currency,

    required this.erased,

    required this.learnerName,

    required this.guardianName,

    required this.household,

    required this.documents,

    required this.feeDiscountId,

    required this.events,
  });

  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final String id;

  // minimum: 1
  @JsonKey(name: r'learner_id', required: true, includeIfNull: false)
  final int learnerId;

  // minimum: 1
  @JsonKey(name: r'session_year_id', required: true, includeIfNull: false)
  final int sessionYearId;

  @JsonKey(name: r'session_year', required: true, includeIfNull: false)
  final String sessionYear;

  @JsonKey(
    name: r'state',
    required: true,
    includeIfNull: false,
    unknownEnumValue: BursaryReviewStateEnum.unknownDefaultOpenApi,
  )
  final BursaryReviewStateEnum state;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @JsonKey(name: r'submitted_at', required: true, includeIfNull: true)
  final DateTime? submittedAt;

  @JsonKey(name: r'decided_at', required: true, includeIfNull: true)
  final DateTime? decidedAt;

  @JsonKey(
    name: r'award_kind',
    required: true,
    includeIfNull: true,
    unknownEnumValue: BursaryReviewAwardKindEnum.unknownDefaultOpenApi,
  )
  final BursaryReviewAwardKindEnum? awardKind;

  @JsonKey(name: r'award_value', required: true, includeIfNull: true)
  final int? awardValue;

  @JsonKey(name: r'currency', required: true, includeIfNull: true)
  final String? currency;

  @JsonKey(name: r'erased', required: true, includeIfNull: false)
  final bool erased;

  @JsonKey(name: r'learner_name', required: true, includeIfNull: false)
  final String learnerName;

  @JsonKey(name: r'guardian_name', required: true, includeIfNull: false)
  final String guardianName;

  @JsonKey(name: r'household', required: true, includeIfNull: true)
  final BursaryApplicationHousehold? household;

  @JsonKey(name: r'documents', required: true, includeIfNull: false)
  final List<BursaryApplicationDocumentsInner> documents;

  @JsonKey(name: r'fee_discount_id', required: true, includeIfNull: true)
  final int? feeDiscountId;

  @JsonKey(name: r'events', required: true, includeIfNull: false)
  final List<BursaryReviewEventsInner> events;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is BursaryReview &&
          other.id == id &&
          other.learnerId == learnerId &&
          other.sessionYearId == sessionYearId &&
          other.sessionYear == sessionYear &&
          other.state == state &&
          other.revision == revision &&
          other.submittedAt == submittedAt &&
          other.decidedAt == decidedAt &&
          other.awardKind == awardKind &&
          other.awardValue == awardValue &&
          other.currency == currency &&
          other.erased == erased &&
          other.learnerName == learnerName &&
          other.guardianName == guardianName &&
          other.household == household &&
          other.documents == documents &&
          other.feeDiscountId == feeDiscountId &&
          other.events == events;

  @override
  int get hashCode =>
      id.hashCode +
      learnerId.hashCode +
      sessionYearId.hashCode +
      sessionYear.hashCode +
      state.hashCode +
      revision.hashCode +
      (submittedAt == null ? 0 : submittedAt.hashCode) +
      (decidedAt == null ? 0 : decidedAt.hashCode) +
      (awardKind == null ? 0 : awardKind.hashCode) +
      (awardValue == null ? 0 : awardValue.hashCode) +
      (currency == null ? 0 : currency.hashCode) +
      erased.hashCode +
      learnerName.hashCode +
      guardianName.hashCode +
      (household == null ? 0 : household.hashCode) +
      documents.hashCode +
      (feeDiscountId == null ? 0 : feeDiscountId.hashCode) +
      events.hashCode;

  factory BursaryReview.fromJson(Map<String, dynamic> json) =>
      _$BursaryReviewFromJson(json);

  Map<String, dynamic> toJson() => _$BursaryReviewToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum BursaryReviewStateEnum {
  @JsonValue(r'draft')
  draft(r'draft'),
  @JsonValue(r'submitted')
  submitted(r'submitted'),
  @JsonValue(r'awarded')
  awarded(r'awarded'),
  @JsonValue(r'declined')
  declined(r'declined'),
  @JsonValue(r'withdrawn')
  withdrawn(r'withdrawn'),
  @JsonValue(r'expired')
  expired(r'expired'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const BursaryReviewStateEnum(this.value);

  final String value;

  @override
  String toString() => value;
}

enum BursaryReviewAwardKindEnum {
  @JsonValue(r'fixed')
  fixed(r'fixed'),
  @JsonValue(r'percent')
  percent(r'percent'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const BursaryReviewAwardKindEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
