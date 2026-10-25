//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/learner_note_metadata.dart';
import 'package:json_annotation/json_annotation.dart';

part 'learner_note_list.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class LearnerNoteList {
  /// Returns a new [LearnerNoteList] instance.
  LearnerNoteList({required this.notes, required this.nextAfter});

  @JsonKey(name: r'notes', required: true, includeIfNull: false)
  final List<LearnerNoteMetadata> notes;

  // minimum: 1
  @JsonKey(name: r'next_after', required: true, includeIfNull: true)
  final int? nextAfter;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is LearnerNoteList &&
          other.notes == notes &&
          other.nextAfter == nextAfter;

  @override
  int get hashCode =>
      notes.hashCode + (nextAfter == null ? 0 : nextAfter.hashCode);

  factory LearnerNoteList.fromJson(Map<String, dynamic> json) =>
      _$LearnerNoteListFromJson(json);

  Map<String, dynamic> toJson() => _$LearnerNoteListToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
