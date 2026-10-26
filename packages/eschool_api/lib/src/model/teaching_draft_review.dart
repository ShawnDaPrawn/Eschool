//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/teaching_draft_definition.dart';
import 'package:json_annotation/json_annotation.dart';

part 'teaching_draft_review.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class TeachingDraftReview {
  /// Returns a new [TeachingDraftReview] instance.
  TeachingDraftReview({
    required this.operationId,

    required this.expectedRevision,

    required this.action,

    required this.definition,

    required this.confirmed,
  });

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  // minimum: 0
  @JsonKey(name: r'expected_revision', required: true, includeIfNull: false)
  final int expectedRevision;

  @JsonKey(
    name: r'action',
    required: true,
    includeIfNull: false,
    unknownEnumValue: TeachingDraftReviewActionEnum.unknownDefaultOpenApi,
  )
  final TeachingDraftReviewActionEnum action;

  @JsonKey(name: r'definition', required: true, includeIfNull: true)
  final TeachingDraftDefinition? definition;

  @JsonKey(name: r'confirmed', required: true, includeIfNull: false)
  final bool confirmed;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is TeachingDraftReview &&
          other.operationId == operationId &&
          other.expectedRevision == expectedRevision &&
          other.action == action &&
          other.definition == definition &&
          other.confirmed == confirmed;

  @override
  int get hashCode =>
      operationId.hashCode +
      expectedRevision.hashCode +
      action.hashCode +
      (definition == null ? 0 : definition.hashCode) +
      confirmed.hashCode;

  factory TeachingDraftReview.fromJson(Map<String, dynamic> json) =>
      _$TeachingDraftReviewFromJson(json);

  Map<String, dynamic> toJson() => _$TeachingDraftReviewToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum TeachingDraftReviewActionEnum {
  @JsonValue(r'edit')
  edit(r'edit'),
  @JsonValue(r'approve')
  approve(r'approve'),
  @JsonValue(r'discard')
  discard(r'discard'),
  @JsonValue(r'publish_practice')
  publishPractice(r'publish_practice'),
  @JsonValue(r'confirm_marks')
  confirmMarks(r'confirm_marks'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const TeachingDraftReviewActionEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
