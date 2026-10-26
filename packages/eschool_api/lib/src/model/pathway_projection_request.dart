//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/pathway_mark.dart';
import 'package:json_annotation/json_annotation.dart';

part 'pathway_projection_request.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class PathwayProjectionRequest {
  /// Returns a new [PathwayProjectionRequest] instance.
  PathwayProjectionRequest({
    required this.learnerId,

    required this.revision,

    required this.source_,

    this.sessionYearId,

    this.termId,

    this.marks,

    this.subjectIds,
  });

  // minimum: 1
  @JsonKey(name: r'learner_id', required: true, includeIfNull: false)
  final int learnerId;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @JsonKey(
    name: r'source',
    required: true,
    includeIfNull: false,
    unknownEnumValue: PathwayProjectionRequestSource_Enum.unknownDefaultOpenApi,
  )
  final PathwayProjectionRequestSource_Enum source_;

  // minimum: 1
  @JsonKey(name: r'session_year_id', required: false, includeIfNull: false)
  final int? sessionYearId;

  // minimum: 1
  @JsonKey(name: r'term_id', required: false, includeIfNull: false)
  final int? termId;

  @JsonKey(name: r'marks', required: false, includeIfNull: false)
  final List<PathwayMark>? marks;

  @JsonKey(name: r'subject_ids', required: false, includeIfNull: false)
  final List<int>? subjectIds;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is PathwayProjectionRequest &&
          other.learnerId == learnerId &&
          other.revision == revision &&
          other.source_ == source_ &&
          other.sessionYearId == sessionYearId &&
          other.termId == termId &&
          other.marks == marks &&
          other.subjectIds == subjectIds;

  @override
  int get hashCode =>
      learnerId.hashCode +
      revision.hashCode +
      source_.hashCode +
      (sessionYearId == null ? 0 : sessionYearId.hashCode) +
      (termId == null ? 0 : termId.hashCode) +
      marks.hashCode +
      subjectIds.hashCode;

  factory PathwayProjectionRequest.fromJson(Map<String, dynamic> json) =>
      _$PathwayProjectionRequestFromJson(json);

  Map<String, dynamic> toJson() => _$PathwayProjectionRequestToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum PathwayProjectionRequestSource_Enum {
  @JsonValue(r'released')
  released(r'released'),
  @JsonValue(r'scenario')
  scenario(r'scenario'),
  @JsonValue(r'selection')
  selection(r'selection'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const PathwayProjectionRequestSource_Enum(this.value);

  final String value;

  @override
  String toString() => value;
}
