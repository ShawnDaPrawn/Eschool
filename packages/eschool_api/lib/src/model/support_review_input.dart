//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'support_review_input.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SupportReviewInput {
  /// Returns a new [SupportReviewInput] instance.
  SupportReviewInput({
    required this.operationId,

    required this.revision,

    required this.reviewedOn,

    this.nextReviewOn,

    required this.outcome,
  });

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @JsonKey(name: r'reviewed_on', required: true, includeIfNull: false)
  final String reviewedOn;

  @JsonKey(name: r'next_review_on', required: false, includeIfNull: false)
  final String? nextReviewOn;

  @JsonKey(name: r'outcome', required: true, includeIfNull: false)
  final String outcome;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is SupportReviewInput &&
          other.operationId == operationId &&
          other.revision == revision &&
          other.reviewedOn == reviewedOn &&
          other.nextReviewOn == nextReviewOn &&
          other.outcome == outcome;

  @override
  int get hashCode =>
      operationId.hashCode +
      revision.hashCode +
      reviewedOn.hashCode +
      (nextReviewOn == null ? 0 : nextReviewOn.hashCode) +
      outcome.hashCode;

  factory SupportReviewInput.fromJson(Map<String, dynamic> json) =>
      _$SupportReviewInputFromJson(json);

  Map<String, dynamic> toJson() => _$SupportReviewInputToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
