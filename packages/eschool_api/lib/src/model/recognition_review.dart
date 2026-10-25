//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'recognition_review.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class RecognitionReview {
  /// Returns a new [RecognitionReview] instance.
  RecognitionReview({
    required this.operationId,

    required this.revision,

    required this.decision,

    required this.reason,
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
    unknownEnumValue: RecognitionReviewDecisionEnum.unknownDefaultOpenApi,
  )
  final RecognitionReviewDecisionEnum decision;

  @JsonKey(name: r'reason', required: true, includeIfNull: false)
  final String reason;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is RecognitionReview &&
          other.operationId == operationId &&
          other.revision == revision &&
          other.decision == decision &&
          other.reason == reason;

  @override
  int get hashCode =>
      operationId.hashCode +
      revision.hashCode +
      decision.hashCode +
      reason.hashCode;

  factory RecognitionReview.fromJson(Map<String, dynamic> json) =>
      _$RecognitionReviewFromJson(json);

  Map<String, dynamic> toJson() => _$RecognitionReviewToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum RecognitionReviewDecisionEnum {
  @JsonValue(r'approved')
  approved(r'approved'),
  @JsonValue(r'rejected')
  rejected(r'rejected'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const RecognitionReviewDecisionEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
