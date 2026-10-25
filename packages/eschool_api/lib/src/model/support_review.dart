//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'support_review.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SupportReview {
  /// Returns a new [SupportReview] instance.
  SupportReview({
    required this.id,

    required this.authorId,

    required this.authorName,

    required this.reviewedOn,

    required this.nextReviewOn,

    required this.kind,

    required this.outcome,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  // minimum: 1
  @JsonKey(name: r'author_id', required: true, includeIfNull: false)
  final int authorId;

  @JsonKey(name: r'author_name', required: true, includeIfNull: false)
  final String authorName;

  @JsonKey(name: r'reviewed_on', required: true, includeIfNull: false)
  final String reviewedOn;

  @JsonKey(name: r'next_review_on', required: true, includeIfNull: true)
  final String? nextReviewOn;

  @JsonKey(
    name: r'kind',
    required: true,
    includeIfNull: false,
    unknownEnumValue: SupportReviewKindEnum.unknownDefaultOpenApi,
  )
  final SupportReviewKindEnum kind;

  @JsonKey(name: r'outcome', required: true, includeIfNull: false)
  final String outcome;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is SupportReview &&
          other.id == id &&
          other.authorId == authorId &&
          other.authorName == authorName &&
          other.reviewedOn == reviewedOn &&
          other.nextReviewOn == nextReviewOn &&
          other.kind == kind &&
          other.outcome == outcome;

  @override
  int get hashCode =>
      id.hashCode +
      authorId.hashCode +
      authorName.hashCode +
      reviewedOn.hashCode +
      (nextReviewOn == null ? 0 : nextReviewOn.hashCode) +
      kind.hashCode +
      outcome.hashCode;

  factory SupportReview.fromJson(Map<String, dynamic> json) =>
      _$SupportReviewFromJson(json);

  Map<String, dynamic> toJson() => _$SupportReviewToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum SupportReviewKindEnum {
  @JsonValue(r'review')
  review(r'review'),
  @JsonValue(r'closure')
  closure(r'closure'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const SupportReviewKindEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
