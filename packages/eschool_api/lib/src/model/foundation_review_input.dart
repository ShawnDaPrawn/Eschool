//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'foundation_review_input.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class FoundationReviewInput {
  /// Returns a new [FoundationReviewInput] instance.
  FoundationReviewInput({
    required this.operationId,

    required this.answer,

    required this.rating,
  });

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  @JsonKey(name: r'answer', required: true, includeIfNull: false)
  final String answer;

  // minimum: 2
  // maximum: 4
  @JsonKey(name: r'rating', required: true, includeIfNull: false)
  final int rating;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is FoundationReviewInput &&
          other.operationId == operationId &&
          other.answer == answer &&
          other.rating == rating;

  @override
  int get hashCode => operationId.hashCode + answer.hashCode + rating.hashCode;

  factory FoundationReviewInput.fromJson(Map<String, dynamic> json) =>
      _$FoundationReviewInputFromJson(json);

  Map<String, dynamic> toJson() => _$FoundationReviewInputToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
