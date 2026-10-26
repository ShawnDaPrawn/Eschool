//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/pathway_rules.dart';
import 'package:json_annotation/json_annotation.dart';

part 'pathway_reference.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class PathwayReference {
  /// Returns a new [PathwayReference] instance.
  PathwayReference({
    required this.id,

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

  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final String id;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @JsonKey(
    name: r'kind',
    required: true,
    includeIfNull: false,
    unknownEnumValue: PathwayReferenceKindEnum.unknownDefaultOpenApi,
  )
  final PathwayReferenceKindEnum kind;

  @JsonKey(
    name: r'status',
    required: true,
    includeIfNull: false,
    unknownEnumValue: PathwayReferenceStatusEnum.unknownDefaultOpenApi,
  )
  final PathwayReferenceStatusEnum status;

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
      other is PathwayReference &&
          other.id == id &&
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
      id.hashCode +
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

  factory PathwayReference.fromJson(Map<String, dynamic> json) =>
      _$PathwayReferenceFromJson(json);

  Map<String, dynamic> toJson() => _$PathwayReferenceToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum PathwayReferenceKindEnum {
  @JsonValue(r'programme')
  programme(r'programme'),
  @JsonValue(r'subject_choice_guide')
  subjectChoiceGuide(r'subject_choice_guide'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const PathwayReferenceKindEnum(this.value);

  final String value;

  @override
  String toString() => value;
}

enum PathwayReferenceStatusEnum {
  @JsonValue(r'published')
  published(r'published'),
  @JsonValue(r'withdrawn')
  withdrawn(r'withdrawn'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const PathwayReferenceStatusEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
