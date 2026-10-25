//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'discipline_record_write.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class DisciplineRecordWrite {
  /// Returns a new [DisciplineRecordWrite] instance.
  DisciplineRecordWrite({
    required this.operationId,

    required this.sessionYearId,

    required this.occurredOn,

    required this.infractionCode,

    required this.description,

    this.staffNote,

    this.sanctionCode,
  });

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  // minimum: 1
  @JsonKey(name: r'session_year_id', required: true, includeIfNull: false)
  final int sessionYearId;

  @JsonKey(name: r'occurred_on', required: true, includeIfNull: false)
  final String occurredOn;

  @JsonKey(name: r'infraction_code', required: true, includeIfNull: false)
  final String infractionCode;

  @JsonKey(name: r'description', required: true, includeIfNull: false)
  final String description;

  @JsonKey(name: r'staff_note', required: false, includeIfNull: false)
  final String? staffNote;

  @JsonKey(name: r'sanction_code', required: false, includeIfNull: false)
  final String? sanctionCode;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is DisciplineRecordWrite &&
          other.operationId == operationId &&
          other.sessionYearId == sessionYearId &&
          other.occurredOn == occurredOn &&
          other.infractionCode == infractionCode &&
          other.description == description &&
          other.staffNote == staffNote &&
          other.sanctionCode == sanctionCode;

  @override
  int get hashCode =>
      operationId.hashCode +
      sessionYearId.hashCode +
      occurredOn.hashCode +
      infractionCode.hashCode +
      description.hashCode +
      (staffNote == null ? 0 : staffNote.hashCode) +
      (sanctionCode == null ? 0 : sanctionCode.hashCode);

  factory DisciplineRecordWrite.fromJson(Map<String, dynamic> json) =>
      _$DisciplineRecordWriteFromJson(json);

  Map<String, dynamic> toJson() => _$DisciplineRecordWriteToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
