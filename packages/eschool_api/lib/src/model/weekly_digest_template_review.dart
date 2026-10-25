//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'weekly_digest_template_review.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class WeeklyDigestTemplateReview {
  /// Returns a new [WeeklyDigestTemplateReview] instance.
  WeeklyDigestTemplateReview({
    required this.operationId,

    required this.revision,

    required this.decision,
  });

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @JsonKey(
    name: r'decision',
    required: true,
    includeIfNull: false,
    unknownEnumValue:
        WeeklyDigestTemplateReviewDecisionEnum.unknownDefaultOpenApi,
  )
  final WeeklyDigestTemplateReviewDecisionEnum decision;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is WeeklyDigestTemplateReview &&
          other.operationId == operationId &&
          other.revision == revision &&
          other.decision == decision;

  @override
  int get hashCode =>
      operationId.hashCode + revision.hashCode + decision.hashCode;

  factory WeeklyDigestTemplateReview.fromJson(Map<String, dynamic> json) =>
      _$WeeklyDigestTemplateReviewFromJson(json);

  Map<String, dynamic> toJson() => _$WeeklyDigestTemplateReviewToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum WeeklyDigestTemplateReviewDecisionEnum {
  @JsonValue(r'approve')
  approve(r'approve'),
  @JsonValue(r'revoke')
  revoke(r'revoke'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const WeeklyDigestTemplateReviewDecisionEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
