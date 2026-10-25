//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'discipline_acknowledge_write.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class DisciplineAcknowledgeWrite {
  /// Returns a new [DisciplineAcknowledgeWrite] instance.
  DisciplineAcknowledgeWrite({
    required this.operationId,

    required this.expectedRevision,
  });

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  // minimum: 1
  @JsonKey(name: r'expected_revision', required: true, includeIfNull: false)
  final int expectedRevision;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is DisciplineAcknowledgeWrite &&
          other.operationId == operationId &&
          other.expectedRevision == expectedRevision;

  @override
  int get hashCode => operationId.hashCode + expectedRevision.hashCode;

  factory DisciplineAcknowledgeWrite.fromJson(Map<String, dynamic> json) =>
      _$DisciplineAcknowledgeWriteFromJson(json);

  Map<String, dynamic> toJson() => _$DisciplineAcknowledgeWriteToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
