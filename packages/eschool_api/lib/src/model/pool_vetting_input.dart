//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'pool_vetting_input.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class PoolVettingInput {
  /// Returns a new [PoolVettingInput] instance.
  PoolVettingInput({
    required this.operationId,

    required this.revision,

    required this.outcome,

    required this.checks,

    required this.checkedOn,

    this.expiresOn,

    this.note,
  });

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  // minimum: 0
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @JsonKey(
    name: r'outcome',
    required: true,
    includeIfNull: false,
    unknownEnumValue: PoolVettingInputOutcomeEnum.unknownDefaultOpenApi,
  )
  final PoolVettingInputOutcomeEnum outcome;

  @JsonKey(
    name: r'checks',
    required: true,
    includeIfNull: false,
    unknownEnumValue: PoolVettingInputChecksEnum.unknownDefaultOpenApi,
  )
  final List<PoolVettingInputChecksEnum> checks;

  @JsonKey(name: r'checked_on', required: true, includeIfNull: false)
  final DateTime checkedOn;

  /// Required for cleared, at most 36 months after the check.
  @JsonKey(name: r'expires_on', required: false, includeIfNull: false)
  final DateTime? expiresOn;

  @JsonKey(name: r'note', required: false, includeIfNull: false)
  final String? note;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is PoolVettingInput &&
          other.operationId == operationId &&
          other.revision == revision &&
          other.outcome == outcome &&
          other.checks == checks &&
          other.checkedOn == checkedOn &&
          other.expiresOn == expiresOn &&
          other.note == note;

  @override
  int get hashCode =>
      operationId.hashCode +
      revision.hashCode +
      outcome.hashCode +
      checks.hashCode +
      checkedOn.hashCode +
      (expiresOn == null ? 0 : expiresOn.hashCode) +
      (note == null ? 0 : note.hashCode);

  factory PoolVettingInput.fromJson(Map<String, dynamic> json) =>
      _$PoolVettingInputFromJson(json);

  Map<String, dynamic> toJson() => _$PoolVettingInputToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum PoolVettingInputOutcomeEnum {
  @JsonValue(r'cleared')
  cleared(r'cleared'),
  @JsonValue(r'refused')
  refused(r'refused'),
  @JsonValue(r'revoked')
  revoked(r'revoked'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const PoolVettingInputOutcomeEnum(this.value);

  final String value;

  @override
  String toString() => value;
}

enum PoolVettingInputChecksEnum {
  @JsonValue(r'identity')
  identity(r'identity'),
  @JsonValue(r'child_protection_register')
  childProtectionRegister(r'child_protection_register'),
  @JsonValue(r'sexual_offences_register')
  sexualOffencesRegister(r'sexual_offences_register'),
  @JsonValue(r'police_clearance')
  policeClearance(r'police_clearance'),
  @JsonValue(r'references')
  references(r'references'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const PoolVettingInputChecksEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
