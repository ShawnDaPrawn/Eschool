//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'practice_test_answer_receipt.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class PracticeTestAnswerReceipt {
  /// Returns a new [PracticeTestAnswerReceipt] instance.
  PracticeTestAnswerReceipt({
    required this.id,

    required this.saved,

    required this.serverTime,

    required this.deadlineAt,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  // minimum: 0
  @JsonKey(name: r'saved', required: true, includeIfNull: false)
  final int saved;

  @JsonKey(name: r'server_time', required: true, includeIfNull: false)
  final DateTime serverTime;

  @JsonKey(name: r'deadline_at', required: true, includeIfNull: false)
  final DateTime deadlineAt;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is PracticeTestAnswerReceipt &&
          other.id == id &&
          other.saved == saved &&
          other.serverTime == serverTime &&
          other.deadlineAt == deadlineAt;

  @override
  int get hashCode =>
      id.hashCode + saved.hashCode + serverTime.hashCode + deadlineAt.hashCode;

  factory PracticeTestAnswerReceipt.fromJson(Map<String, dynamic> json) =>
      _$PracticeTestAnswerReceiptFromJson(json);

  Map<String, dynamic> toJson() => _$PracticeTestAnswerReceiptToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
