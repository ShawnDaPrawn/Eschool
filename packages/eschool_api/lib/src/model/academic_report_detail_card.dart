//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/academic_report_stored_comment.dart';
import 'package:eschool_api/src/model/academic_report_payload.dart';
import 'package:json_annotation/json_annotation.dart';

part 'academic_report_detail_card.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicReportDetailCard {
  /// Returns a new [AcademicReportDetailCard] instance.
  AcademicReportDetailCard({
    required this.id,

    required this.learnerId,

    required this.payload,

    required this.pdfReady,

    required this.generatedAt,

    required this.comments,

    required this.downloadUrl,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  // minimum: 1
  @JsonKey(name: r'learner_id', required: true, includeIfNull: false)
  final int learnerId;

  @JsonKey(name: r'payload', required: true, includeIfNull: false)
  final AcademicReportPayload payload;

  @JsonKey(name: r'pdf_ready', required: true, includeIfNull: false)
  final bool pdfReady;

  @JsonKey(name: r'generated_at', required: true, includeIfNull: true)
  final String? generatedAt;

  @JsonKey(name: r'comments', required: true, includeIfNull: false)
  final List<AcademicReportStoredComment> comments;

  @JsonKey(name: r'download_url', required: true, includeIfNull: true)
  final String? downloadUrl;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicReportDetailCard &&
          other.id == id &&
          other.learnerId == learnerId &&
          other.payload == payload &&
          other.pdfReady == pdfReady &&
          other.generatedAt == generatedAt &&
          other.comments == comments &&
          other.downloadUrl == downloadUrl;

  @override
  int get hashCode =>
      id.hashCode +
      learnerId.hashCode +
      payload.hashCode +
      pdfReady.hashCode +
      (generatedAt == null ? 0 : generatedAt.hashCode) +
      comments.hashCode +
      (downloadUrl == null ? 0 : downloadUrl.hashCode);

  factory AcademicReportDetailCard.fromJson(Map<String, dynamic> json) =>
      _$AcademicReportDetailCardFromJson(json);

  Map<String, dynamic> toJson() => _$AcademicReportDetailCardToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
