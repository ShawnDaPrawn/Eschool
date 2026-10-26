//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/pathway_rules.dart';
import 'package:json_annotation/json_annotation.dart';

part 'pathway_reference_write.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class PathwayReferenceWrite {
  /// Returns a new [PathwayReferenceWrite] instance.
  PathwayReferenceWrite({
    required this.operationId,

    required this.revision,

    required this.kind,

    required this.status,

    required this.title,

    required this.institution,

    required this.sourceTitle,

    required this.sourceUrl,

    required this.sourceYear,

    required this.reviewedOn,

    required this.expiresOn,

    required this.guidance,

    required this.rules,
  });

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  // minimum: 0
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @JsonKey(
    name: r'kind',
    required: true,
    includeIfNull: false,
    unknownEnumValue: PathwayReferenceWriteKindEnum.unknownDefaultOpenApi,
  )
  final PathwayReferenceWriteKindEnum kind;

  @JsonKey(
    name: r'status',
    required: true,
    includeIfNull: false,
    unknownEnumValue: PathwayReferenceWriteStatusEnum.unknownDefaultOpenApi,
  )
  final PathwayReferenceWriteStatusEnum status;

  @JsonKey(name: r'title', required: true, includeIfNull: false)
  final String title;

  @JsonKey(name: r'institution', required: true, includeIfNull: true)
  final String? institution;

  @JsonKey(name: r'source_title', required: true, includeIfNull: false)
  final String sourceTitle;

  @JsonKey(name: r'source_url', required: true, includeIfNull: false)
  final String sourceUrl;

  // minimum: 2000
  // maximum: 2100
  @JsonKey(name: r'source_year', required: true, includeIfNull: false)
  final int sourceYear;

  @JsonKey(name: r'reviewed_on', required: true, includeIfNull: false)
  final String reviewedOn;

  @JsonKey(name: r'expires_on', required: true, includeIfNull: false)
  final String expiresOn;

  @JsonKey(name: r'guidance', required: true, includeIfNull: false)
  final String guidance;

  @JsonKey(name: r'rules', required: true, includeIfNull: false)
  final PathwayRules rules;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is PathwayReferenceWrite &&
          other.operationId == operationId &&
          other.revision == revision &&
          other.kind == kind &&
          other.status == status &&
          other.title == title &&
          other.institution == institution &&
          other.sourceTitle == sourceTitle &&
          other.sourceUrl == sourceUrl &&
          other.sourceYear == sourceYear &&
          other.reviewedOn == reviewedOn &&
          other.expiresOn == expiresOn &&
          other.guidance == guidance &&
          other.rules == rules;

  @override
  int get hashCode =>
      operationId.hashCode +
      revision.hashCode +
      kind.hashCode +
      status.hashCode +
      title.hashCode +
      (institution == null ? 0 : institution.hashCode) +
      sourceTitle.hashCode +
      sourceUrl.hashCode +
      sourceYear.hashCode +
      reviewedOn.hashCode +
      expiresOn.hashCode +
      guidance.hashCode +
      rules.hashCode;

  factory PathwayReferenceWrite.fromJson(Map<String, dynamic> json) =>
      _$PathwayReferenceWriteFromJson(json);

  Map<String, dynamic> toJson() => _$PathwayReferenceWriteToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum PathwayReferenceWriteKindEnum {
  @JsonValue(r'programme')
  programme(r'programme'),
  @JsonValue(r'subject_choice_guide')
  subjectChoiceGuide(r'subject_choice_guide'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const PathwayReferenceWriteKindEnum(this.value);

  final String value;

  @override
  String toString() => value;
}

enum PathwayReferenceWriteStatusEnum {
  @JsonValue(r'published')
  published(r'published'),
  @JsonValue(r'withdrawn')
  withdrawn(r'withdrawn'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const PathwayReferenceWriteStatusEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
