//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'academic_report_signature.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicReportSignature {
  /// Returns a new [AcademicReportSignature] instance.
  AcademicReportSignature({
    required this.name,

    required this.signedAt,

    required this.revision,
  });

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  @JsonKey(name: r'signed_at', required: true, includeIfNull: false)
  final String signedAt;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicReportSignature &&
          other.name == name &&
          other.signedAt == signedAt &&
          other.revision == revision;

  @override
  int get hashCode => name.hashCode + signedAt.hashCode + revision.hashCode;

  factory AcademicReportSignature.fromJson(Map<String, dynamic> json) =>
      _$AcademicReportSignatureFromJson(json);

  Map<String, dynamic> toJson() => _$AcademicReportSignatureToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
