//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'pool_vetting_record.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class PoolVettingRecord {
  /// Returns a new [PoolVettingRecord] instance.
  PoolVettingRecord({
    required this.id,

    required this.outcome,

    required this.checks,

    required this.checkedBy,

    required this.checkedOn,

    required this.expiresOn,

    required this.note,
  });

  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(
    name: r'outcome',
    required: true,
    includeIfNull: false,
    unknownEnumValue: PoolVettingRecordOutcomeEnum.unknownDefaultOpenApi,
  )
  final PoolVettingRecordOutcomeEnum outcome;

  @JsonKey(name: r'checks', required: true, includeIfNull: false)
  final List<String> checks;

  @JsonKey(name: r'checked_by', required: true, includeIfNull: false)
  final int checkedBy;

  @JsonKey(name: r'checked_on', required: true, includeIfNull: false)
  final String checkedOn;

  @JsonKey(name: r'expires_on', required: true, includeIfNull: true)
  final String? expiresOn;

  /// Only for people-pool-vet holders; null otherwise.
  @JsonKey(name: r'note', required: true, includeIfNull: true)
  final String? note;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is PoolVettingRecord &&
          other.id == id &&
          other.outcome == outcome &&
          other.checks == checks &&
          other.checkedBy == checkedBy &&
          other.checkedOn == checkedOn &&
          other.expiresOn == expiresOn &&
          other.note == note;

  @override
  int get hashCode =>
      id.hashCode +
      outcome.hashCode +
      checks.hashCode +
      checkedBy.hashCode +
      checkedOn.hashCode +
      (expiresOn == null ? 0 : expiresOn.hashCode) +
      (note == null ? 0 : note.hashCode);

  factory PoolVettingRecord.fromJson(Map<String, dynamic> json) =>
      _$PoolVettingRecordFromJson(json);

  Map<String, dynamic> toJson() => _$PoolVettingRecordToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum PoolVettingRecordOutcomeEnum {
  @JsonValue(r'cleared')
  cleared(r'cleared'),
  @JsonValue(r'refused')
  refused(r'refused'),
  @JsonValue(r'revoked')
  revoked(r'revoked'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const PoolVettingRecordOutcomeEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
