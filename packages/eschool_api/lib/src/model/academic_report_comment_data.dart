//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'academic_report_comment_data.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicReportCommentData {
  /// Returns a new [AcademicReportCommentData] instance.
  AcademicReportCommentData({
    required this.id,

    required this.revision,

    required this.batchRevision,

    required this.fingerprint,

    required this.text,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  // minimum: 1
  @JsonKey(name: r'batch_revision', required: true, includeIfNull: false)
  final int batchRevision;

  @JsonKey(name: r'fingerprint', required: true, includeIfNull: false)
  final String fingerprint;

  @JsonKey(name: r'text', required: true, includeIfNull: false)
  final String text;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicReportCommentData &&
          other.id == id &&
          other.revision == revision &&
          other.batchRevision == batchRevision &&
          other.fingerprint == fingerprint &&
          other.text == text;

  @override
  int get hashCode =>
      id.hashCode +
      revision.hashCode +
      batchRevision.hashCode +
      fingerprint.hashCode +
      text.hashCode;

  factory AcademicReportCommentData.fromJson(Map<String, dynamic> json) =>
      _$AcademicReportCommentDataFromJson(json);

  Map<String, dynamic> toJson() => _$AcademicReportCommentDataToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
