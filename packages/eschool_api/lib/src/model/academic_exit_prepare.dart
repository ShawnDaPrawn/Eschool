//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'academic_exit_prepare.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicExitPrepare {
  /// Returns a new [AcademicExitPrepare] instance.
  AcademicExitPrepare({
    required this.transitionId,

    required this.reportIds,

    required this.documentIds,

    required this.operationId,

    required this.fingerprint,
  });

  // minimum: 1
  @JsonKey(name: r'transition_id', required: true, includeIfNull: false)
  final int transitionId;

  @JsonKey(name: r'report_ids', required: true, includeIfNull: false)
  final Set<int> reportIds;

  @JsonKey(name: r'document_ids', required: true, includeIfNull: false)
  final Set<String> documentIds;

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  @JsonKey(name: r'fingerprint', required: true, includeIfNull: false)
  final String fingerprint;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicExitPrepare &&
          other.transitionId == transitionId &&
          other.reportIds == reportIds &&
          other.documentIds == documentIds &&
          other.operationId == operationId &&
          other.fingerprint == fingerprint;

  @override
  int get hashCode =>
      transitionId.hashCode +
      reportIds.hashCode +
      documentIds.hashCode +
      operationId.hashCode +
      fingerprint.hashCode;

  factory AcademicExitPrepare.fromJson(Map<String, dynamic> json) =>
      _$AcademicExitPrepareFromJson(json);

  Map<String, dynamic> toJson() => _$AcademicExitPrepareToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
