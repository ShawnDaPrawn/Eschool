//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/reasoning_review_shared.dart';
import 'package:eschool_api/src/model/reasoning_feedback.dart';
import 'package:json_annotation/json_annotation.dart';

part 'reasoning_review.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ReasoningReview {
  /// Returns a new [ReasoningReview] instance.
  ReasoningReview({
    required this.id,

    required this.state,

    required this.source_,

    required this.fromSide,

    required this.stance,

    required this.requestedBy,

    required this.requestedAt,

    required this.feedback,

    required this.failureCode,

    required this.shared,
  });

  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(
    name: r'state',
    required: true,
    includeIfNull: false,
    unknownEnumValue: ReasoningReviewStateEnum.unknownDefaultOpenApi,
  )
  final ReasoningReviewStateEnum state;

  @JsonKey(
    name: r'source',
    required: true,
    includeIfNull: false,
    unknownEnumValue: ReasoningReviewSource_Enum.unknownDefaultOpenApi,
  )
  final ReasoningReviewSource_Enum source_;

  @JsonKey(
    name: r'from_side',
    required: true,
    includeIfNull: false,
    unknownEnumValue: ReasoningReviewFromSideEnum.unknownDefaultOpenApi,
  )
  final ReasoningReviewFromSideEnum fromSide;

  @JsonKey(
    name: r'stance',
    required: true,
    includeIfNull: true,
    unknownEnumValue: ReasoningReviewStanceEnum.unknownDefaultOpenApi,
  )
  final ReasoningReviewStanceEnum? stance;

  @JsonKey(name: r'requested_by', required: true, includeIfNull: false)
  final int requestedBy;

  @JsonKey(name: r'requested_at', required: true, includeIfNull: true)
  final DateTime? requestedAt;

  /// Advice to the adjudicator on reasoning only. Never a score, a result or a verdict.
  @JsonKey(name: r'feedback', required: true, includeIfNull: true)
  final ReasoningFeedback? feedback;

  @JsonKey(name: r'failure_code', required: true, includeIfNull: true)
  final String? failureCode;

  @JsonKey(name: r'shared', required: true, includeIfNull: true)
  final ReasoningReviewShared? shared;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ReasoningReview &&
          other.id == id &&
          other.state == state &&
          other.source_ == source_ &&
          other.fromSide == fromSide &&
          other.stance == stance &&
          other.requestedBy == requestedBy &&
          other.requestedAt == requestedAt &&
          other.feedback == feedback &&
          other.failureCode == failureCode &&
          other.shared == shared;

  @override
  int get hashCode =>
      id.hashCode +
      state.hashCode +
      source_.hashCode +
      fromSide.hashCode +
      (stance == null ? 0 : stance.hashCode) +
      requestedBy.hashCode +
      (requestedAt == null ? 0 : requestedAt.hashCode) +
      (feedback == null ? 0 : feedback.hashCode) +
      (failureCode == null ? 0 : failureCode.hashCode) +
      (shared == null ? 0 : shared.hashCode);

  factory ReasoningReview.fromJson(Map<String, dynamic> json) =>
      _$ReasoningReviewFromJson(json);

  Map<String, dynamic> toJson() => _$ReasoningReviewToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum ReasoningReviewStateEnum {
  @JsonValue(r'pending')
  pending(r'pending'),
  @JsonValue(r'completed')
  completed(r'completed'),
  @JsonValue(r'unavailable')
  unavailable(r'unavailable'),
  @JsonValue(r'quarantined')
  quarantined(r'quarantined'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const ReasoningReviewStateEnum(this.value);

  final String value;

  @override
  String toString() => value;
}

enum ReasoningReviewSource_Enum {
  @JsonValue(r'speech')
  speech(r'speech'),
  @JsonValue(r'written')
  written(r'written'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const ReasoningReviewSource_Enum(this.value);

  final String value;

  @override
  String toString() => value;
}

enum ReasoningReviewFromSideEnum {
  @JsonValue(r'ours')
  ours(r'ours'),
  @JsonValue(r'theirs')
  theirs(r'theirs'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const ReasoningReviewFromSideEnum(this.value);

  final String value;

  @override
  String toString() => value;
}

enum ReasoningReviewStanceEnum {
  @JsonValue(r'proposition')
  proposition(r'proposition'),
  @JsonValue(r'opposition')
  opposition(r'opposition'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const ReasoningReviewStanceEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
