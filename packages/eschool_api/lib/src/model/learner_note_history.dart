//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/learner_note_revision.dart';
import 'package:json_annotation/json_annotation.dart';

part 'learner_note_history.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class LearnerNoteHistory {
  /// Returns a new [LearnerNoteHistory] instance.
  LearnerNoteHistory({required this.noteId, required this.revisions});

  // minimum: 1
  @JsonKey(name: r'note_id', required: true, includeIfNull: false)
  final int noteId;

  @JsonKey(name: r'revisions', required: true, includeIfNull: false)
  final List<LearnerNoteRevision> revisions;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is LearnerNoteHistory &&
          other.noteId == noteId &&
          other.revisions == revisions;

  @override
  int get hashCode => noteId.hashCode + revisions.hashCode;

  factory LearnerNoteHistory.fromJson(Map<String, dynamic> json) =>
      _$LearnerNoteHistoryFromJson(json);

  Map<String, dynamic> toJson() => _$LearnerNoteHistoryToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
