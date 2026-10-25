//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'discipline_detention_enroll_write.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class DisciplineDetentionEnrollWrite {
  /// Returns a new [DisciplineDetentionEnrollWrite] instance.
  DisciplineDetentionEnrollWrite({
    required this.operationId,

    required this.recordId,

    required this.expectedRevision,
  });

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  // minimum: 1
  @JsonKey(name: r'record_id', required: true, includeIfNull: false)
  final int recordId;

  // minimum: 1
  @JsonKey(name: r'expected_revision', required: true, includeIfNull: false)
  final int expectedRevision;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is DisciplineDetentionEnrollWrite &&
          other.operationId == operationId &&
          other.recordId == recordId &&
          other.expectedRevision == expectedRevision;

  @override
  int get hashCode =>
      operationId.hashCode + recordId.hashCode + expectedRevision.hashCode;

  factory DisciplineDetentionEnrollWrite.fromJson(Map<String, dynamic> json) =>
      _$DisciplineDetentionEnrollWriteFromJson(json);

  Map<String, dynamic> toJson() => _$DisciplineDetentionEnrollWriteToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
