//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'learner_note_update.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class LearnerNoteUpdate {
  /// Returns a new [LearnerNoteUpdate] instance.
  LearnerNoteUpdate({
    required this.operationId,

    required this.expectedRevision,

    required this.body,

    this.archive,
  });

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  // minimum: 1
  @JsonKey(name: r'expected_revision', required: true, includeIfNull: false)
  final int expectedRevision;

  @JsonKey(name: r'body', required: true, includeIfNull: false)
  final String body;

  @JsonKey(name: r'archive', required: false, includeIfNull: false)
  final bool? archive;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is LearnerNoteUpdate &&
          other.operationId == operationId &&
          other.expectedRevision == expectedRevision &&
          other.body == body &&
          other.archive == archive;

  @override
  int get hashCode =>
      operationId.hashCode +
      expectedRevision.hashCode +
      body.hashCode +
      archive.hashCode;

  factory LearnerNoteUpdate.fromJson(Map<String, dynamic> json) =>
      _$LearnerNoteUpdateFromJson(json);

  Map<String, dynamic> toJson() => _$LearnerNoteUpdateToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
