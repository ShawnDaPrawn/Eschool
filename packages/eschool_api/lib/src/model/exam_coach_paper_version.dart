//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/exam_coach_licence.dart';
import 'package:eschool_api/src/model/exam_coach_question.dart';
import 'package:json_annotation/json_annotation.dart';

part 'exam_coach_paper_version.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ExamCoachPaperVersion {
  /// Returns a new [ExamCoachPaperVersion] instance.
  ExamCoachPaperVersion({
    required this.id,

    required this.versionId,

    required this.number,

    required this.title,

    required this.framework,

    required this.paperYear,

    required this.paperCode,

    required this.state,

    required this.licenceKind,

    required this.licenceReference,

    required this.questions,

    this.licence,

    this.revision,

    this.groupIds,

    this.canEdit,
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

  @JsonKey(name: r'framework', required: true, includeIfNull: false)
  final String framework;

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
    unknownEnumValue: ExamCoachPaperVersionStateEnum.unknownDefaultOpenApi,
  )
  final ExamCoachPaperVersionStateEnum state;

  @JsonKey(
    name: r'licence_kind',
    required: true,
    includeIfNull: false,
    unknownEnumValue:
        ExamCoachPaperVersionLicenceKindEnum.unknownDefaultOpenApi,
  )
  final ExamCoachPaperVersionLicenceKindEnum licenceKind;

  @JsonKey(name: r'licence_reference', required: true, includeIfNull: false)
  final String licenceReference;

  @JsonKey(name: r'questions', required: true, includeIfNull: false)
  final List<ExamCoachQuestion> questions;

  @JsonKey(name: r'licence', required: false, includeIfNull: false)
  final ExamCoachLicence? licence;

  // minimum: 1
  @JsonKey(name: r'revision', required: false, includeIfNull: false)
  final int? revision;

  @JsonKey(name: r'group_ids', required: false, includeIfNull: false)
  final List<int>? groupIds;

  @JsonKey(name: r'can_edit', required: false, includeIfNull: false)
  final bool? canEdit;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ExamCoachPaperVersion &&
          other.id == id &&
          other.versionId == versionId &&
          other.number == number &&
          other.title == title &&
          other.framework == framework &&
          other.paperYear == paperYear &&
          other.paperCode == paperCode &&
          other.state == state &&
          other.licenceKind == licenceKind &&
          other.licenceReference == licenceReference &&
          other.questions == questions &&
          other.licence == licence &&
          other.revision == revision &&
          other.groupIds == groupIds &&
          other.canEdit == canEdit;

  @override
  int get hashCode =>
      id.hashCode +
      versionId.hashCode +
      number.hashCode +
      title.hashCode +
      framework.hashCode +
      paperYear.hashCode +
      paperCode.hashCode +
      state.hashCode +
      licenceKind.hashCode +
      licenceReference.hashCode +
      questions.hashCode +
      licence.hashCode +
      revision.hashCode +
      groupIds.hashCode +
      canEdit.hashCode;

  factory ExamCoachPaperVersion.fromJson(Map<String, dynamic> json) =>
      _$ExamCoachPaperVersionFromJson(json);

  Map<String, dynamic> toJson() => _$ExamCoachPaperVersionToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum ExamCoachPaperVersionStateEnum {
  @JsonValue(r'draft')
  draft(r'draft'),
  @JsonValue(r'published')
  published(r'published'),
  @JsonValue(r'withdrawn')
  withdrawn(r'withdrawn'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const ExamCoachPaperVersionStateEnum(this.value);

  final String value;

  @override
  String toString() => value;
}

enum ExamCoachPaperVersionLicenceKindEnum {
  @JsonValue(r'school_owned')
  schoolOwned(r'school_owned'),
  @JsonValue(r'open_licence')
  openLicence(r'open_licence'),
  @JsonValue(r'permission')
  permission(r'permission'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const ExamCoachPaperVersionLicenceKindEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
