//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'transfer_prepare_input.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class TransferPrepareInput {
  /// Returns a new [TransferPrepareInput] instance.
  TransferPrepareInput({
    required this.operationId,

    required this.learnerId,

    required this.guardianId,

    required this.toSchoolId,

    required this.externalSchool,

    required this.lastAttendedOn,

    required this.startsOn,

    required this.reportIds,

    required this.documentIds,
  });

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  // minimum: 1
  @JsonKey(name: r'learner_id', required: true, includeIfNull: false)
  final int learnerId;

  // minimum: 1
  @JsonKey(name: r'guardian_id', required: true, includeIfNull: false)
  final int guardianId;

  // minimum: 1
  @JsonKey(name: r'to_school_id', required: true, includeIfNull: true)
  final int? toSchoolId;

  @JsonKey(name: r'external_school', required: true, includeIfNull: true)
  final String? externalSchool;

  @JsonKey(name: r'last_attended_on', required: true, includeIfNull: false)
  final String lastAttendedOn;

  @JsonKey(name: r'starts_on', required: true, includeIfNull: false)
  final String startsOn;

  @JsonKey(name: r'report_ids', required: true, includeIfNull: false)
  final List<int> reportIds;

  @JsonKey(name: r'document_ids', required: true, includeIfNull: false)
  final List<String> documentIds;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is TransferPrepareInput &&
          other.operationId == operationId &&
          other.learnerId == learnerId &&
          other.guardianId == guardianId &&
          other.toSchoolId == toSchoolId &&
          other.externalSchool == externalSchool &&
          other.lastAttendedOn == lastAttendedOn &&
          other.startsOn == startsOn &&
          other.reportIds == reportIds &&
          other.documentIds == documentIds;

  @override
  int get hashCode =>
      operationId.hashCode +
      learnerId.hashCode +
      guardianId.hashCode +
      (toSchoolId == null ? 0 : toSchoolId.hashCode) +
      (externalSchool == null ? 0 : externalSchool.hashCode) +
      lastAttendedOn.hashCode +
      startsOn.hashCode +
      reportIds.hashCode +
      documentIds.hashCode;

  factory TransferPrepareInput.fromJson(Map<String, dynamic> json) =>
      _$TransferPrepareInputFromJson(json);

  Map<String, dynamic> toJson() => _$TransferPrepareInputToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
