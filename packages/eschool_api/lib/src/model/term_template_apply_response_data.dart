//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/school_term_draft.dart';
import 'package:json_annotation/json_annotation.dart';

part 'term_template_apply_response_data.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class TermTemplateApplyResponseData {
  /// Returns a new [TermTemplateApplyResponseData] instance.
  TermTemplateApplyResponseData({
    required this.applied,

    required this.sessionYearId,

    required this.templateId,

    required this.createdCount,

    required this.wouldCreateCount,

    required this.skippedCount,

    required this.terms,
  });

  @JsonKey(name: r'applied', required: true, includeIfNull: false)
  final bool applied;

  // minimum: 1
  @JsonKey(name: r'session_year_id', required: true, includeIfNull: false)
  final int sessionYearId;

  // minimum: 1
  @JsonKey(name: r'template_id', required: true, includeIfNull: false)
  final int templateId;

  @JsonKey(name: r'created_count', required: true, includeIfNull: false)
  final int createdCount;

  @JsonKey(name: r'would_create_count', required: true, includeIfNull: false)
  final int wouldCreateCount;

  @JsonKey(name: r'skipped_count', required: true, includeIfNull: false)
  final int skippedCount;

  @JsonKey(name: r'terms', required: true, includeIfNull: false)
  final List<SchoolTermDraft> terms;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is TermTemplateApplyResponseData &&
          other.applied == applied &&
          other.sessionYearId == sessionYearId &&
          other.templateId == templateId &&
          other.createdCount == createdCount &&
          other.wouldCreateCount == wouldCreateCount &&
          other.skippedCount == skippedCount &&
          other.terms == terms;

  @override
  int get hashCode =>
      applied.hashCode +
      sessionYearId.hashCode +
      templateId.hashCode +
      createdCount.hashCode +
      wouldCreateCount.hashCode +
      skippedCount.hashCode +
      terms.hashCode;

  factory TermTemplateApplyResponseData.fromJson(Map<String, dynamic> json) =>
      _$TermTemplateApplyResponseDataFromJson(json);

  Map<String, dynamic> toJson() => _$TermTemplateApplyResponseDataToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
