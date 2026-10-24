//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/academic_report_signatures.dart';
import 'package:eschool_api/src/model/academic_report_selection.dart';
import 'package:eschool_api/src/model/academic_report_design_snapshot.dart';
import 'package:eschool_api/src/model/academic_report_detail_card.dart';
import 'package:json_annotation/json_annotation.dart';

part 'academic_report_detail_data.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicReportDetailData {
  /// Returns a new [AcademicReportDetailData] instance.
  AcademicReportDetailData({
    required this.id,

    required this.state,

    required this.revision,

    required this.fingerprint,

    required this.sourceFingerprint,

    required this.selection,

    required this.design,

    required this.signatures,

    required this.releasedAt,

    required this.cards,

    required this.canGradeSign,

    required this.canPrincipalSign,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(name: r'state', required: true, includeIfNull: false)
  final String state;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @JsonKey(name: r'fingerprint', required: true, includeIfNull: false)
  final String fingerprint;

  @JsonKey(name: r'source_fingerprint', required: true, includeIfNull: false)
  final String sourceFingerprint;

  @JsonKey(name: r'selection', required: true, includeIfNull: false)
  final AcademicReportSelection selection;

  @JsonKey(name: r'design', required: true, includeIfNull: false)
  final AcademicReportDesignSnapshot design;

  @JsonKey(name: r'signatures', required: true, includeIfNull: false)
  final AcademicReportSignatures signatures;

  @JsonKey(name: r'released_at', required: true, includeIfNull: true)
  final String? releasedAt;

  @JsonKey(name: r'cards', required: true, includeIfNull: false)
  final List<AcademicReportDetailCard> cards;

  @JsonKey(name: r'can_grade_sign', required: true, includeIfNull: false)
  final bool canGradeSign;

  @JsonKey(name: r'can_principal_sign', required: true, includeIfNull: false)
  final bool canPrincipalSign;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicReportDetailData &&
          other.id == id &&
          other.state == state &&
          other.revision == revision &&
          other.fingerprint == fingerprint &&
          other.sourceFingerprint == sourceFingerprint &&
          other.selection == selection &&
          other.design == design &&
          other.signatures == signatures &&
          other.releasedAt == releasedAt &&
          other.cards == cards &&
          other.canGradeSign == canGradeSign &&
          other.canPrincipalSign == canPrincipalSign;

  @override
  int get hashCode =>
      id.hashCode +
      state.hashCode +
      revision.hashCode +
      fingerprint.hashCode +
      sourceFingerprint.hashCode +
      selection.hashCode +
      design.hashCode +
      signatures.hashCode +
      (releasedAt == null ? 0 : releasedAt.hashCode) +
      cards.hashCode +
      canGradeSign.hashCode +
      canPrincipalSign.hashCode;

  factory AcademicReportDetailData.fromJson(Map<String, dynamic> json) =>
      _$AcademicReportDetailDataFromJson(json);

  Map<String, dynamic> toJson() => _$AcademicReportDetailDataToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
