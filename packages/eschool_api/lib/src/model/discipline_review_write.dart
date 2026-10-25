//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'discipline_review_write.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class DisciplineReviewWrite {
  /// Returns a new [DisciplineReviewWrite] instance.
  DisciplineReviewWrite({
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
    unknownEnumValue: DisciplineReviewWriteDecisionEnum.unknownDefaultOpenApi,
  )
  final DisciplineReviewWriteDecisionEnum decision;

  @JsonKey(name: r'reason', required: true, includeIfNull: false)
  final String reason;

  @JsonKey(name: r'family_reason', required: false, includeIfNull: false)
  final String? familyReason;

  @JsonKey(name: r'sanction_code', required: false, includeIfNull: false)
  final String? sanctionCode;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is DisciplineReviewWrite &&
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

  factory DisciplineReviewWrite.fromJson(Map<String, dynamic> json) =>
      _$DisciplineReviewWriteFromJson(json);

  Map<String, dynamic> toJson() => _$DisciplineReviewWriteToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum DisciplineReviewWriteDecisionEnum {
  @JsonValue(r'confirm')
  confirm(r'confirm'),
  @JsonValue(r'overturn')
  overturn(r'overturn'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const DisciplineReviewWriteDecisionEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
