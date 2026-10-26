//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'exam_coach_plan_receipt.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ExamCoachPlanReceipt {
  /// Returns a new [ExamCoachPlanReceipt] instance.
  ExamCoachPlanReceipt({
    required this.id,

    required this.kind,

    required this.state,
  });

  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final String id;

  @JsonKey(
    name: r'kind',
    required: true,
    includeIfNull: false,
    unknownEnumValue: ExamCoachPlanReceiptKindEnum.unknownDefaultOpenApi,
  )
  final ExamCoachPlanReceiptKindEnum kind;

  @JsonKey(
    name: r'state',
    required: true,
    includeIfNull: false,
    unknownEnumValue: ExamCoachPlanReceiptStateEnum.unknownDefaultOpenApi,
  )
  final ExamCoachPlanReceiptStateEnum state;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ExamCoachPlanReceipt &&
          other.id == id &&
          other.kind == kind &&
          other.state == state;

  @override
  int get hashCode => id.hashCode + kind.hashCode + state.hashCode;

  factory ExamCoachPlanReceipt.fromJson(Map<String, dynamic> json) =>
      _$ExamCoachPlanReceiptFromJson(json);

  Map<String, dynamic> toJson() => _$ExamCoachPlanReceiptToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum ExamCoachPlanReceiptKindEnum {
  @JsonValue(r'study_plan')
  studyPlan(r'study_plan'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const ExamCoachPlanReceiptKindEnum(this.value);

  final String value;

  @override
  String toString() => value;
}

enum ExamCoachPlanReceiptStateEnum {
  @JsonValue(r'created')
  created(r'created'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const ExamCoachPlanReceiptStateEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
