//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/cover_teacher.dart';
import 'package:json_annotation/json_annotation.dart';

part 'cover_candidates.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class CoverCandidates {
  /// Returns a new [CoverCandidates] instance.
  CoverCandidates({
    required this.occurrenceId,

    required this.revision,

    required this.teachers,
  });

  // minimum: 1
  @JsonKey(name: r'occurrence_id', required: true, includeIfNull: false)
  final int occurrenceId;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @JsonKey(name: r'teachers', required: true, includeIfNull: false)
  final List<CoverTeacher> teachers;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is CoverCandidates &&
          other.occurrenceId == occurrenceId &&
          other.revision == revision &&
          other.teachers == teachers;

  @override
  int get hashCode =>
      occurrenceId.hashCode + revision.hashCode + teachers.hashCode;

  factory CoverCandidates.fromJson(Map<String, dynamic> json) =>
      _$CoverCandidatesFromJson(json);

  Map<String, dynamic> toJson() => _$CoverCandidatesToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
