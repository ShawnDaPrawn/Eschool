//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'practice_test_start_input.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class PracticeTestStartInput {
  /// Returns a new [PracticeTestStartInput] instance.
  PracticeTestStartInput({required this.operationId});

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is PracticeTestStartInput && other.operationId == operationId;

  @override
  int get hashCode => operationId.hashCode;

  factory PracticeTestStartInput.fromJson(Map<String, dynamic> json) =>
      _$PracticeTestStartInputFromJson(json);

  Map<String, dynamic> toJson() => _$PracticeTestStartInputToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
