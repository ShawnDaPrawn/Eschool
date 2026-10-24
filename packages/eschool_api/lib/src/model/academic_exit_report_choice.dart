//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'academic_exit_report_choice.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicExitReportChoice {
  /// Returns a new [AcademicExitReportChoice] instance.
  AcademicExitReportChoice({
    required this.id,

    required this.yearName,

    required this.termName,

    required this.releasedAt,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(name: r'year_name', required: true, includeIfNull: false)
  final String yearName;

  @JsonKey(name: r'term_name', required: true, includeIfNull: true)
  final String? termName;

  @JsonKey(name: r'released_at', required: true, includeIfNull: false)
  final DateTime releasedAt;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicExitReportChoice &&
          other.id == id &&
          other.yearName == yearName &&
          other.termName == termName &&
          other.releasedAt == releasedAt;

  @override
  int get hashCode =>
      id.hashCode +
      yearName.hashCode +
      (termName == null ? 0 : termName.hashCode) +
      releasedAt.hashCode;

  factory AcademicExitReportChoice.fromJson(Map<String, dynamic> json) =>
      _$AcademicExitReportChoiceFromJson(json);

  Map<String, dynamic> toJson() => _$AcademicExitReportChoiceToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
