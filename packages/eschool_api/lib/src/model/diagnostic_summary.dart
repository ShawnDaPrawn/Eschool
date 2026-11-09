//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/diagnostic_summary_diagnostics_inner.dart';
import 'package:json_annotation/json_annotation.dart';

part 'diagnostic_summary.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class DiagnosticSummary {
  /// Returns a new [DiagnosticSummary] instance.
  DiagnosticSummary({required this.learnerId, required this.diagnostics});

  // minimum: 1
  @JsonKey(name: r'learner_id', required: true, includeIfNull: false)
  final int learnerId;

  @JsonKey(name: r'diagnostics', required: true, includeIfNull: false)
  final List<DiagnosticSummaryDiagnosticsInner> diagnostics;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is DiagnosticSummary &&
          other.learnerId == learnerId &&
          other.diagnostics == diagnostics;

  @override
  int get hashCode => learnerId.hashCode + diagnostics.hashCode;

  factory DiagnosticSummary.fromJson(Map<String, dynamic> json) =>
      _$DiagnosticSummaryFromJson(json);

  Map<String, dynamic> toJson() => _$DiagnosticSummaryToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
