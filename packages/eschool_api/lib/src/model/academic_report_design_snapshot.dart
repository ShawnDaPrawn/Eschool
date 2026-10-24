//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'academic_report_design_snapshot.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicReportDesignSnapshot {
  /// Returns a new [AcademicReportDesignSnapshot] instance.
  AcademicReportDesignSnapshot({
    required this.templateId,

    required this.templateRevision,

    required this.name,

    required this.heading,

    required this.introduction,

    required this.footer,

    required this.sections,

    required this.showCrest,

    required this.crest,
  });

  // minimum: 1
  @JsonKey(name: r'template_id', required: true, includeIfNull: false)
  final int templateId;

  // minimum: 1
  @JsonKey(name: r'template_revision', required: true, includeIfNull: false)
  final int templateRevision;

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  @JsonKey(name: r'heading', required: true, includeIfNull: false)
  final String heading;

  @JsonKey(name: r'introduction', required: true, includeIfNull: true)
  final String? introduction;

  @JsonKey(name: r'footer', required: true, includeIfNull: true)
  final String? footer;

  @JsonKey(name: r'sections', required: true, includeIfNull: false)
  final List<String> sections;

  @JsonKey(name: r'show_crest', required: true, includeIfNull: false)
  final bool showCrest;

  @JsonKey(name: r'crest', required: true, includeIfNull: true)
  final String? crest;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicReportDesignSnapshot &&
          other.templateId == templateId &&
          other.templateRevision == templateRevision &&
          other.name == name &&
          other.heading == heading &&
          other.introduction == introduction &&
          other.footer == footer &&
          other.sections == sections &&
          other.showCrest == showCrest &&
          other.crest == crest;

  @override
  int get hashCode =>
      templateId.hashCode +
      templateRevision.hashCode +
      name.hashCode +
      heading.hashCode +
      (introduction == null ? 0 : introduction.hashCode) +
      (footer == null ? 0 : footer.hashCode) +
      sections.hashCode +
      showCrest.hashCode +
      (crest == null ? 0 : crest.hashCode);

  factory AcademicReportDesignSnapshot.fromJson(Map<String, dynamic> json) =>
      _$AcademicReportDesignSnapshotFromJson(json);

  Map<String, dynamic> toJson() => _$AcademicReportDesignSnapshotToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
