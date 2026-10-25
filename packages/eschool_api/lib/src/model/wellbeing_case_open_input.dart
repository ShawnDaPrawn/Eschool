//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'wellbeing_case_open_input.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class WellbeingCaseOpenInput {
  /// Returns a new [WellbeingCaseOpenInput] instance.
  WellbeingCaseOpenInput({required this.operationId, required this.learnerId});

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  // minimum: 1
  @JsonKey(name: r'learner_id', required: true, includeIfNull: false)
  final int learnerId;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is WellbeingCaseOpenInput &&
          other.operationId == operationId &&
          other.learnerId == learnerId;

  @override
  int get hashCode => operationId.hashCode + learnerId.hashCode;

  factory WellbeingCaseOpenInput.fromJson(Map<String, dynamic> json) =>
      _$WellbeingCaseOpenInputFromJson(json);

  Map<String, dynamic> toJson() => _$WellbeingCaseOpenInputToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
