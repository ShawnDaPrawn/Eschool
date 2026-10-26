//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'exam_coach_withdrawal_write.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ExamCoachWithdrawalWrite {
  /// Returns a new [ExamCoachWithdrawalWrite] instance.
  ExamCoachWithdrawalWrite({
    required this.operationId,

    required this.expectedRevision,

    required this.reason,
  });

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  // minimum: 1
  @JsonKey(name: r'expected_revision', required: true, includeIfNull: false)
  final int expectedRevision;

  @JsonKey(name: r'reason', required: true, includeIfNull: false)
  final String reason;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ExamCoachWithdrawalWrite &&
          other.operationId == operationId &&
          other.expectedRevision == expectedRevision &&
          other.reason == reason;

  @override
  int get hashCode =>
      operationId.hashCode + expectedRevision.hashCode + reason.hashCode;

  factory ExamCoachWithdrawalWrite.fromJson(Map<String, dynamic> json) =>
      _$ExamCoachWithdrawalWriteFromJson(json);

  Map<String, dynamic> toJson() => _$ExamCoachWithdrawalWriteToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
