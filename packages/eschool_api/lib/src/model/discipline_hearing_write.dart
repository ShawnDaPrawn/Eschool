//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'discipline_hearing_write.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class DisciplineHearingWrite {
  /// Returns a new [DisciplineHearingWrite] instance.
  DisciplineHearingWrite({
    required this.operationId,

    required this.expectedRevision,

    required this.decision,

    required this.reason,

    this.familyReason,

    this.sanctionCode,
  });

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  // minimum: 1
  @JsonKey(name: r'expected_revision', required: true, includeIfNull: false)
  final int expectedRevision;

  @JsonKey(
    name: r'decision',
    required: true,
    includeIfNull: false,
    unknownEnumValue: DisciplineHearingWriteDecisionEnum.unknownDefaultOpenApi,
  )
  final DisciplineHearingWriteDecisionEnum decision;

  @JsonKey(name: r'reason', required: true, includeIfNull: false)
  final String reason;

  @JsonKey(name: r'family_reason', required: false, includeIfNull: false)
  final String? familyReason;

  @JsonKey(name: r'sanction_code', required: false, includeIfNull: false)
  final String? sanctionCode;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is DisciplineHearingWrite &&
          other.operationId == operationId &&
          other.expectedRevision == expectedRevision &&
          other.decision == decision &&
          other.reason == reason &&
          other.familyReason == familyReason &&
          other.sanctionCode == sanctionCode;

  @override
  int get hashCode =>
      operationId.hashCode +
      expectedRevision.hashCode +
      decision.hashCode +
      reason.hashCode +
      (familyReason == null ? 0 : familyReason.hashCode) +
      (sanctionCode == null ? 0 : sanctionCode.hashCode);

  factory DisciplineHearingWrite.fromJson(Map<String, dynamic> json) =>
      _$DisciplineHearingWriteFromJson(json);

  Map<String, dynamic> toJson() => _$DisciplineHearingWriteToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum DisciplineHearingWriteDecisionEnum {
  @JsonValue(r'no_action')
  noAction(r'no_action'),
  @JsonValue(r'sanction')
  sanction(r'sanction'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const DisciplineHearingWriteDecisionEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
