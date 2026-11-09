//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'practice_test_withdraw_input.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class PracticeTestWithdrawInput {
  /// Returns a new [PracticeTestWithdrawInput] instance.
  PracticeTestWithdrawInput({
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
      other is PracticeTestWithdrawInput &&
          other.operationId == operationId &&
          other.expectedRevision == expectedRevision;

  @override
  int get hashCode => operationId.hashCode + expectedRevision.hashCode;

  factory PracticeTestWithdrawInput.fromJson(Map<String, dynamic> json) =>
      _$PracticeTestWithdrawInputFromJson(json);

  Map<String, dynamic> toJson() => _$PracticeTestWithdrawInputToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
