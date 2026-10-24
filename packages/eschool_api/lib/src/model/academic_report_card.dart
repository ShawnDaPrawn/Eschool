//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/academic_report_payload.dart';
import 'package:json_annotation/json_annotation.dart';

part 'academic_report_card.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicReportCard {
  /// Returns a new [AcademicReportCard] instance.
  AcademicReportCard({
    required this.id,

    required this.learnerId,

    required this.payload,

    required this.pdfReady,

    required this.generatedAt,
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

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicReportCard &&
          other.id == id &&
          other.learnerId == learnerId &&
          other.payload == payload &&
          other.pdfReady == pdfReady &&
          other.generatedAt == generatedAt;

  @override
  int get hashCode =>
      id.hashCode +
      learnerId.hashCode +
      payload.hashCode +
      pdfReady.hashCode +
      (generatedAt == null ? 0 : generatedAt.hashCode);

  factory AcademicReportCard.fromJson(Map<String, dynamic> json) =>
      _$AcademicReportCardFromJson(json);

  Map<String, dynamic> toJson() => _$AcademicReportCardToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
