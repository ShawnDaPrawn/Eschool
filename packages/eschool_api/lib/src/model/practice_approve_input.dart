//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/practice_question.dart';
import 'package:json_annotation/json_annotation.dart';

part 'practice_approve_input.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class PracticeApproveInput {
  /// Returns a new [PracticeApproveInput] instance.
  PracticeApproveInput({
    required this.operationId,

    required this.expectedRevision,

    required this.topicId,

    required this.contentVersionId,

    required this.estimatedSeconds,

    required this.active,

    required this.definition,
  });

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  // minimum: 0
  @JsonKey(name: r'expected_revision', required: true, includeIfNull: false)
  final int expectedRevision;

  // minimum: 1
  @JsonKey(name: r'topic_id', required: true, includeIfNull: false)
  final int topicId;

  // minimum: 1
  @JsonKey(name: r'content_version_id', required: true, includeIfNull: false)
  final int contentVersionId;

  // minimum: 60
  // maximum: 300
  @JsonKey(name: r'estimated_seconds', required: true, includeIfNull: false)
  final int estimatedSeconds;

  @JsonKey(name: r'active', required: true, includeIfNull: false)
  final bool active;

  @JsonKey(name: r'definition', required: true, includeIfNull: false)
  final PracticeQuestion definition;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is PracticeApproveInput &&
          other.operationId == operationId &&
          other.expectedRevision == expectedRevision &&
          other.topicId == topicId &&
          other.contentVersionId == contentVersionId &&
          other.estimatedSeconds == estimatedSeconds &&
          other.active == active &&
          other.definition == definition;

  @override
  int get hashCode =>
      operationId.hashCode +
      expectedRevision.hashCode +
      topicId.hashCode +
      contentVersionId.hashCode +
      estimatedSeconds.hashCode +
      active.hashCode +
      definition.hashCode;

  factory PracticeApproveInput.fromJson(Map<String, dynamic> json) =>
      _$PracticeApproveInputFromJson(json);

  Map<String, dynamic> toJson() => _$PracticeApproveInputToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
