//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/academic_register_row_write.dart';
import 'package:json_annotation/json_annotation.dart';

part 'academic_register_sync_write.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicRegisterSyncWrite {
  /// Returns a new [AcademicRegisterSyncWrite] instance.
  AcademicRegisterSyncWrite({
    required this.operationId,

    required this.baseRevision,

    required this.rosterHash,

    this.reason,

    required this.rows,
  });

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  // minimum: 0
  @JsonKey(name: r'base_revision', required: true, includeIfNull: false)
  final int baseRevision;

  @JsonKey(name: r'roster_hash', required: true, includeIfNull: false)
  final String rosterHash;

  @JsonKey(name: r'reason', required: false, includeIfNull: false)
  final String? reason;

  @JsonKey(name: r'rows', required: true, includeIfNull: false)
  final List<AcademicRegisterRowWrite> rows;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicRegisterSyncWrite &&
          other.operationId == operationId &&
          other.baseRevision == baseRevision &&
          other.rosterHash == rosterHash &&
          other.reason == reason &&
          other.rows == rows;

  @override
  int get hashCode =>
      operationId.hashCode +
      baseRevision.hashCode +
      rosterHash.hashCode +
      (reason == null ? 0 : reason.hashCode) +
      rows.hashCode;

  factory AcademicRegisterSyncWrite.fromJson(Map<String, dynamic> json) =>
      _$AcademicRegisterSyncWriteFromJson(json);

  Map<String, dynamic> toJson() => _$AcademicRegisterSyncWriteToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
