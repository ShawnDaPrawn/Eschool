//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/pathway_selection_result.dart';
import 'package:eschool_api/src/model/pathway_released_evidence.dart';
import 'package:eschool_api/src/model/pathway_score_result.dart';
import 'package:json_annotation/json_annotation.dart';

part 'pathway_projection.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class PathwayProjection {
  /// Returns a new [PathwayProjection] instance.
  PathwayProjection({
    required this.schoolId,

    required this.readerId,

    required this.learnerId,

    required this.referenceId,

    required this.revision,

    required this.kind,

    required this.source_,

    required this.score,

    required this.selection,

    required this.releasedEvidence,
  });

  // minimum: 1
  @JsonKey(name: r'school_id', required: true, includeIfNull: false)
  final int schoolId;

  // minimum: 1
  @JsonKey(name: r'reader_id', required: true, includeIfNull: false)
  final int readerId;

  // minimum: 1
  @JsonKey(name: r'learner_id', required: true, includeIfNull: false)
  final int learnerId;

  @JsonKey(name: r'reference_id', required: true, includeIfNull: false)
  final String referenceId;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @JsonKey(
    name: r'kind',
    required: true,
    includeIfNull: false,
    unknownEnumValue: PathwayProjectionKindEnum.unknownDefaultOpenApi,
  )
  final PathwayProjectionKindEnum kind;

  @JsonKey(
    name: r'source',
    required: true,
    includeIfNull: false,
    unknownEnumValue: PathwayProjectionSource_Enum.unknownDefaultOpenApi,
  )
  final PathwayProjectionSource_Enum source_;

  @JsonKey(name: r'score', required: true, includeIfNull: true)
  final PathwayScoreResult? score;

  @JsonKey(name: r'selection', required: true, includeIfNull: true)
  final PathwaySelectionResult? selection;

  @JsonKey(name: r'released_evidence', required: true, includeIfNull: false)
  final List<PathwayReleasedEvidence> releasedEvidence;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is PathwayProjection &&
          other.schoolId == schoolId &&
          other.readerId == readerId &&
          other.learnerId == learnerId &&
          other.referenceId == referenceId &&
          other.revision == revision &&
          other.kind == kind &&
          other.source_ == source_ &&
          other.score == score &&
          other.selection == selection &&
          other.releasedEvidence == releasedEvidence;

  @override
  int get hashCode =>
      schoolId.hashCode +
      readerId.hashCode +
      learnerId.hashCode +
      referenceId.hashCode +
      revision.hashCode +
      kind.hashCode +
      source_.hashCode +
      (score == null ? 0 : score.hashCode) +
      (selection == null ? 0 : selection.hashCode) +
      releasedEvidence.hashCode;

  factory PathwayProjection.fromJson(Map<String, dynamic> json) =>
      _$PathwayProjectionFromJson(json);

  Map<String, dynamic> toJson() => _$PathwayProjectionToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum PathwayProjectionKindEnum {
  @JsonValue(r'programme')
  programme(r'programme'),
  @JsonValue(r'subject_choice_guide')
  subjectChoiceGuide(r'subject_choice_guide'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const PathwayProjectionKindEnum(this.value);

  final String value;

  @override
  String toString() => value;
}

enum PathwayProjectionSource_Enum {
  @JsonValue(r'released')
  released(r'released'),
  @JsonValue(r'scenario')
  scenario(r'scenario'),
  @JsonValue(r'selection')
  selection(r'selection'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const PathwayProjectionSource_Enum(this.value);

  final String value;

  @override
  String toString() => value;
}
