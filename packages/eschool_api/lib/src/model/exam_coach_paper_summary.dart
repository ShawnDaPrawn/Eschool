//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'exam_coach_paper_summary.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ExamCoachPaperSummary {
  /// Returns a new [ExamCoachPaperSummary] instance.
  ExamCoachPaperSummary({
    required this.id,

    required this.versionId,

    required this.number,

    required this.title,

    required this.paperYear,

    required this.paperCode,

    required this.state,

    required this.licenceKind,

    required this.revision,

    required this.canEdit,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(name: r'version_id', required: true, includeIfNull: false)
  final String versionId;

  // minimum: 1
  @JsonKey(name: r'number', required: true, includeIfNull: false)
  final int number;

  @JsonKey(name: r'title', required: true, includeIfNull: false)
  final String title;

  // minimum: 1850
  // maximum: 2100
  @JsonKey(name: r'paper_year', required: true, includeIfNull: false)
  final int paperYear;

  @JsonKey(name: r'paper_code', required: true, includeIfNull: false)
  final String paperCode;

  @JsonKey(
    name: r'state',
    required: true,
    includeIfNull: false,
    unknownEnumValue: ExamCoachPaperSummaryStateEnum.unknownDefaultOpenApi,
  )
  final ExamCoachPaperSummaryStateEnum state;

  @JsonKey(
    name: r'licence_kind',
    required: true,
    includeIfNull: false,
    unknownEnumValue:
        ExamCoachPaperSummaryLicenceKindEnum.unknownDefaultOpenApi,
  )
  final ExamCoachPaperSummaryLicenceKindEnum licenceKind;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @JsonKey(name: r'can_edit', required: true, includeIfNull: false)
  final bool canEdit;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ExamCoachPaperSummary &&
          other.id == id &&
          other.versionId == versionId &&
          other.number == number &&
          other.title == title &&
          other.paperYear == paperYear &&
          other.paperCode == paperCode &&
          other.state == state &&
          other.licenceKind == licenceKind &&
          other.revision == revision &&
          other.canEdit == canEdit;

  @override
  int get hashCode =>
      id.hashCode +
      versionId.hashCode +
      number.hashCode +
      title.hashCode +
      paperYear.hashCode +
      paperCode.hashCode +
      state.hashCode +
      licenceKind.hashCode +
      revision.hashCode +
      canEdit.hashCode;

  factory ExamCoachPaperSummary.fromJson(Map<String, dynamic> json) =>
      _$ExamCoachPaperSummaryFromJson(json);

  Map<String, dynamic> toJson() => _$ExamCoachPaperSummaryToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum ExamCoachPaperSummaryStateEnum {
  @JsonValue(r'draft')
  draft(r'draft'),
  @JsonValue(r'published')
  published(r'published'),
  @JsonValue(r'withdrawn')
  withdrawn(r'withdrawn'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const ExamCoachPaperSummaryStateEnum(this.value);

  final String value;

  @override
  String toString() => value;
}

enum ExamCoachPaperSummaryLicenceKindEnum {
  @JsonValue(r'school_owned')
  schoolOwned(r'school_owned'),
  @JsonValue(r'open_licence')
  openLicence(r'open_licence'),
  @JsonValue(r'permission')
  permission(r'permission'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const ExamCoachPaperSummaryLicenceKindEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
