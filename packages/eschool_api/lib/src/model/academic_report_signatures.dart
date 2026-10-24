//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/academic_report_signature.dart';
import 'package:json_annotation/json_annotation.dart';

part 'academic_report_signatures.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicReportSignatures {
  /// Returns a new [AcademicReportSignatures] instance.
  AcademicReportSignatures({this.gradeHead, this.principal});

  @JsonKey(name: r'grade_head', required: false, includeIfNull: false)
  final AcademicReportSignature? gradeHead;

  @JsonKey(name: r'principal', required: false, includeIfNull: false)
  final AcademicReportSignature? principal;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicReportSignatures &&
          other.gradeHead == gradeHead &&
          other.principal == principal;

  @override
  int get hashCode => gradeHead.hashCode + principal.hashCode;

  factory AcademicReportSignatures.fromJson(Map<String, dynamic> json) =>
      _$AcademicReportSignaturesFromJson(json);

  Map<String, dynamic> toJson() => _$AcademicReportSignaturesToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
