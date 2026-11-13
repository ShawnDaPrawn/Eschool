//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/reasoning_review.dart';
import 'package:eschool_api/src/model/academic_competition_detail_awards_inner.dart';
import 'package:eschool_api/src/model/academic_result.dart';
import 'package:eschool_api/src/model/academic_competition_detail_adjudicators_inner.dart';
import 'package:json_annotation/json_annotation.dart';

part 'academic_competition_detail.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicCompetitionDetail {
  /// Returns a new [AcademicCompetitionDetail] instance.
  AcademicCompetitionDetail({
    required this.kind,

    required this.entry,

    required this.motion,

    required this.format,

    required this.rounds,

    required this.hostStance,

    required this.myStance,

    required this.theirWinner,

    required this.adjudicators,

    required this.result,

    required this.reviews,

    required this.awards,

    required this.reasoningFeedback,

    required this.canEditDetails,

    required this.canAdjudicate,

    required this.canSubmit,

    required this.canAward,
  });

  @JsonKey(
    name: r'kind',
    required: true,
    includeIfNull: false,
    unknownEnumValue: AcademicCompetitionDetailKindEnum.unknownDefaultOpenApi,
  )
  final AcademicCompetitionDetailKindEnum kind;

  @JsonKey(
    name: r'entry',
    required: true,
    includeIfNull: false,
    unknownEnumValue: AcademicCompetitionDetailEntryEnum.unknownDefaultOpenApi,
  )
  final AcademicCompetitionDetailEntryEnum entry;

  @JsonKey(name: r'motion', required: true, includeIfNull: false)
  final String motion;

  @JsonKey(name: r'format', required: true, includeIfNull: true)
  final String? format;

  @JsonKey(name: r'rounds', required: true, includeIfNull: true)
  final int? rounds;

  @JsonKey(
    name: r'host_stance',
    required: true,
    includeIfNull: true,
    unknownEnumValue:
        AcademicCompetitionDetailHostStanceEnum.unknownDefaultOpenApi,
  )
  final AcademicCompetitionDetailHostStanceEnum? hostStance;

  @JsonKey(
    name: r'my_stance',
    required: true,
    includeIfNull: true,
    unknownEnumValue:
        AcademicCompetitionDetailMyStanceEnum.unknownDefaultOpenApi,
  )
  final AcademicCompetitionDetailMyStanceEnum? myStance;

  /// The winner the other school submitted, shown only while the two results differ.
  @JsonKey(
    name: r'their_winner',
    required: true,
    includeIfNull: true,
    unknownEnumValue:
        AcademicCompetitionDetailTheirWinnerEnum.unknownDefaultOpenApi,
  )
  final AcademicCompetitionDetailTheirWinnerEnum? theirWinner;

  /// This school's own adjudicators (managers and adjudicators only); never another school's.
  @JsonKey(name: r'adjudicators', required: true, includeIfNull: false)
  final List<AcademicCompetitionDetailAdjudicatorsInner> adjudicators;

  /// This school's adjudicators' result (managers and adjudicators only); a draft is never seen by another school.
  @JsonKey(name: r'result', required: true, includeIfNull: true)
  final AcademicResult? result;

  @JsonKey(name: r'reviews', required: true, includeIfNull: false)
  final List<ReasoningReview> reviews;

  @JsonKey(name: r'awards', required: true, includeIfNull: false)
  final List<AcademicCompetitionDetailAwardsInner> awards;

  /// Whether this adjudicator may ask for AI feedback here (kind, platform and school switches).
  @JsonKey(name: r'reasoning_feedback', required: true, includeIfNull: false)
  final bool reasoningFeedback;

  @JsonKey(name: r'can_edit_details', required: true, includeIfNull: false)
  final bool canEditDetails;

  @JsonKey(name: r'can_adjudicate', required: true, includeIfNull: false)
  final bool canAdjudicate;

  @JsonKey(name: r'can_submit', required: true, includeIfNull: false)
  final bool canSubmit;

  @JsonKey(name: r'can_award', required: true, includeIfNull: false)
  final bool canAward;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicCompetitionDetail &&
          other.kind == kind &&
          other.entry == entry &&
          other.motion == motion &&
          other.format == format &&
          other.rounds == rounds &&
          other.hostStance == hostStance &&
          other.myStance == myStance &&
          other.theirWinner == theirWinner &&
          other.adjudicators == adjudicators &&
          other.result == result &&
          other.reviews == reviews &&
          other.awards == awards &&
          other.reasoningFeedback == reasoningFeedback &&
          other.canEditDetails == canEditDetails &&
          other.canAdjudicate == canAdjudicate &&
          other.canSubmit == canSubmit &&
          other.canAward == canAward;

  @override
  int get hashCode =>
      kind.hashCode +
      entry.hashCode +
      motion.hashCode +
      (format == null ? 0 : format.hashCode) +
      (rounds == null ? 0 : rounds.hashCode) +
      (hostStance == null ? 0 : hostStance.hashCode) +
      (myStance == null ? 0 : myStance.hashCode) +
      (theirWinner == null ? 0 : theirWinner.hashCode) +
      adjudicators.hashCode +
      (result == null ? 0 : result.hashCode) +
      reviews.hashCode +
      awards.hashCode +
      reasoningFeedback.hashCode +
      canEditDetails.hashCode +
      canAdjudicate.hashCode +
      canSubmit.hashCode +
      canAward.hashCode;

  factory AcademicCompetitionDetail.fromJson(Map<String, dynamic> json) =>
      _$AcademicCompetitionDetailFromJson(json);

  Map<String, dynamic> toJson() => _$AcademicCompetitionDetailToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum AcademicCompetitionDetailKindEnum {
  @JsonValue(r'debate')
  debate(r'debate'),
  @JsonValue(r'quiz')
  quiz(r'quiz'),
  @JsonValue(r'speech')
  speech(r'speech'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const AcademicCompetitionDetailKindEnum(this.value);

  final String value;

  @override
  String toString() => value;
}

enum AcademicCompetitionDetailEntryEnum {
  @JsonValue(r'team')
  team(r'team'),
  @JsonValue(r'individual')
  individual(r'individual'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const AcademicCompetitionDetailEntryEnum(this.value);

  final String value;

  @override
  String toString() => value;
}

enum AcademicCompetitionDetailHostStanceEnum {
  @JsonValue(r'proposition')
  proposition(r'proposition'),
  @JsonValue(r'opposition')
  opposition(r'opposition'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const AcademicCompetitionDetailHostStanceEnum(this.value);

  final String value;

  @override
  String toString() => value;
}

enum AcademicCompetitionDetailMyStanceEnum {
  @JsonValue(r'proposition')
  proposition(r'proposition'),
  @JsonValue(r'opposition')
  opposition(r'opposition'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const AcademicCompetitionDetailMyStanceEnum(this.value);

  final String value;

  @override
  String toString() => value;
}

/// The winner the other school submitted, shown only while the two results differ.
enum AcademicCompetitionDetailTheirWinnerEnum {
  @JsonValue(r'host')
  host(r'host'),
  @JsonValue(r'guest')
  guest(r'guest'),
  @JsonValue(r'draw')
  draw(r'draw'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const AcademicCompetitionDetailTheirWinnerEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
