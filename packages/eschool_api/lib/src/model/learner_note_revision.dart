//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'learner_note_revision.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class LearnerNoteRevision {
  /// Returns a new [LearnerNoteRevision] instance.
  LearnerNoteRevision({
    required this.revision,

    required this.authorId,

    required this.authorName,

    required this.body,

    required this.archived,

    required this.createdAt,
  });

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  // minimum: 1
  @JsonKey(name: r'author_id', required: true, includeIfNull: false)
  final int authorId;

  @JsonKey(name: r'author_name', required: true, includeIfNull: false)
  final String authorName;

  @JsonKey(name: r'body', required: true, includeIfNull: false)
  final String body;

  @JsonKey(name: r'archived', required: true, includeIfNull: false)
  final bool archived;

  @JsonKey(name: r'created_at', required: true, includeIfNull: false)
  final DateTime createdAt;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is LearnerNoteRevision &&
          other.revision == revision &&
          other.authorId == authorId &&
          other.authorName == authorName &&
          other.body == body &&
          other.archived == archived &&
          other.createdAt == createdAt;

  @override
  int get hashCode =>
      revision.hashCode +
      authorId.hashCode +
      authorName.hashCode +
      body.hashCode +
      archived.hashCode +
      createdAt.hashCode;

  factory LearnerNoteRevision.fromJson(Map<String, dynamic> json) =>
      _$LearnerNoteRevisionFromJson(json);

  Map<String, dynamic> toJson() => _$LearnerNoteRevisionToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
