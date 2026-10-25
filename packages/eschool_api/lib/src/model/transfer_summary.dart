//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'transfer_summary.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class TransferSummary {
  /// Returns a new [TransferSummary] instance.
  TransferSummary({
    required this.id,

    required this.publicId,

    required this.state,

    required this.revision,

    required this.fingerprint,

    required this.learnerName,

    required this.studentNumber,

    required this.guardianName,

    required this.fromSchoolName,

    required this.toSchoolName,

    required this.lastAttendedOn,

    required this.startsOn,

    required this.expiresAt,

    required this.exportOnly,

    required this.reportCount,

    required this.documentCount,

    required this.markCount,

    required this.attendanceCount,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(name: r'public_id', required: true, includeIfNull: false)
  final String publicId;

  @JsonKey(
    name: r'state',
    required: true,
    includeIfNull: false,
    unknownEnumValue: TransferSummaryStateEnum.unknownDefaultOpenApi,
  )
  final TransferSummaryStateEnum state;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @JsonKey(name: r'fingerprint', required: true, includeIfNull: false)
  final String fingerprint;

  @JsonKey(name: r'learner_name', required: true, includeIfNull: false)
  final String learnerName;

  @JsonKey(name: r'student_number', required: true, includeIfNull: true)
  final String? studentNumber;

  @JsonKey(name: r'guardian_name', required: true, includeIfNull: false)
  final String guardianName;

  @JsonKey(name: r'from_school_name', required: true, includeIfNull: false)
  final String fromSchoolName;

  @JsonKey(name: r'to_school_name', required: true, includeIfNull: false)
  final String toSchoolName;

  @JsonKey(name: r'last_attended_on', required: true, includeIfNull: false)
  final String lastAttendedOn;

  @JsonKey(name: r'starts_on', required: true, includeIfNull: false)
  final String startsOn;

  @JsonKey(name: r'expires_at', required: true, includeIfNull: true)
  final DateTime? expiresAt;

  @JsonKey(name: r'export_only', required: true, includeIfNull: false)
  final bool exportOnly;

  // minimum: 0
  @JsonKey(name: r'report_count', required: true, includeIfNull: false)
  final int reportCount;

  // minimum: 0
  @JsonKey(name: r'document_count', required: true, includeIfNull: false)
  final int documentCount;

  // minimum: 0
  @JsonKey(name: r'mark_count', required: true, includeIfNull: false)
  final int markCount;

  // minimum: 0
  @JsonKey(name: r'attendance_count', required: true, includeIfNull: false)
  final int attendanceCount;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is TransferSummary &&
          other.id == id &&
          other.publicId == publicId &&
          other.state == state &&
          other.revision == revision &&
          other.fingerprint == fingerprint &&
          other.learnerName == learnerName &&
          other.studentNumber == studentNumber &&
          other.guardianName == guardianName &&
          other.fromSchoolName == fromSchoolName &&
          other.toSchoolName == toSchoolName &&
          other.lastAttendedOn == lastAttendedOn &&
          other.startsOn == startsOn &&
          other.expiresAt == expiresAt &&
          other.exportOnly == exportOnly &&
          other.reportCount == reportCount &&
          other.documentCount == documentCount &&
          other.markCount == markCount &&
          other.attendanceCount == attendanceCount;

  @override
  int get hashCode =>
      id.hashCode +
      publicId.hashCode +
      state.hashCode +
      revision.hashCode +
      fingerprint.hashCode +
      learnerName.hashCode +
      (studentNumber == null ? 0 : studentNumber.hashCode) +
      guardianName.hashCode +
      fromSchoolName.hashCode +
      toSchoolName.hashCode +
      lastAttendedOn.hashCode +
      startsOn.hashCode +
      (expiresAt == null ? 0 : expiresAt.hashCode) +
      exportOnly.hashCode +
      reportCount.hashCode +
      documentCount.hashCode +
      markCount.hashCode +
      attendanceCount.hashCode;

  factory TransferSummary.fromJson(Map<String, dynamic> json) =>
      _$TransferSummaryFromJson(json);

  Map<String, dynamic> toJson() => _$TransferSummaryToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum TransferSummaryStateEnum {
  @JsonValue(r'awaiting_consent')
  awaitingConsent(r'awaiting_consent'),
  @JsonValue(r'consented')
  consented(r'consented'),
  @JsonValue(r'released')
  released(r'released'),
  @JsonValue(r'accepted')
  accepted(r'accepted'),
  @JsonValue(r'cancelled')
  cancelled(r'cancelled'),
  @JsonValue(r'expired')
  expired(r'expired'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const TransferSummaryStateEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
