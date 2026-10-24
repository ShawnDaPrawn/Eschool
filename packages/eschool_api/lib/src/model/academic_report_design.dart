//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'academic_report_design.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicReportDesign {
  /// Returns a new [AcademicReportDesign] instance.
  AcademicReportDesign({
    required this.heading,

    required this.introduction,

    required this.footer,

    required this.sections,

    required this.showCrest,
  });

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

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicReportDesign &&
          other.heading == heading &&
          other.introduction == introduction &&
          other.footer == footer &&
          other.sections == sections &&
          other.showCrest == showCrest;

  @override
  int get hashCode =>
      heading.hashCode +
      (introduction == null ? 0 : introduction.hashCode) +
      (footer == null ? 0 : footer.hashCode) +
      sections.hashCode +
      showCrest.hashCode;

  factory AcademicReportDesign.fromJson(Map<String, dynamic> json) =>
      _$AcademicReportDesignFromJson(json);

  Map<String, dynamic> toJson() => _$AcademicReportDesignToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
