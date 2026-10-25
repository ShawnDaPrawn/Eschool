//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'recognition_issue.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class RecognitionIssue {
  /// Returns a new [RecognitionIssue] instance.
  RecognitionIssue({
    required this.operationId,

    required this.learnerId,

    required this.sessionYearId,

    required this.pointCategoryId,

    required this.points,

    required this.reason,

    required this.public,
  });

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  // minimum: 1
  @JsonKey(name: r'learner_id', required: true, includeIfNull: false)
  final int learnerId;

  // minimum: 1
  @JsonKey(name: r'session_year_id', required: true, includeIfNull: false)
  final int sessionYearId;

  // minimum: 1
  @JsonKey(name: r'point_category_id', required: true, includeIfNull: false)
  final int pointCategoryId;

  // minimum: -10000
  // maximum: 10000
  @JsonKey(name: r'points', required: true, includeIfNull: false)
  final int points;

  @JsonKey(name: r'reason', required: true, includeIfNull: false)
  final String reason;

  @JsonKey(name: r'public', required: true, includeIfNull: false)
  final bool public;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is RecognitionIssue &&
          other.operationId == operationId &&
          other.learnerId == learnerId &&
          other.sessionYearId == sessionYearId &&
          other.pointCategoryId == pointCategoryId &&
          other.points == points &&
          other.reason == reason &&
          other.public == public;

  @override
  int get hashCode =>
      operationId.hashCode +
      learnerId.hashCode +
      sessionYearId.hashCode +
      pointCategoryId.hashCode +
      points.hashCode +
      reason.hashCode +
      public.hashCode;

  factory RecognitionIssue.fromJson(Map<String, dynamic> json) =>
      _$RecognitionIssueFromJson(json);

  Map<String, dynamic> toJson() => _$RecognitionIssueToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
