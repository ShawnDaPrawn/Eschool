//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'exam_coach_operation_write.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ExamCoachOperationWrite {
  /// Returns a new [ExamCoachOperationWrite] instance.
  ExamCoachOperationWrite({required this.operationId});

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ExamCoachOperationWrite && other.operationId == operationId;

  @override
  int get hashCode => operationId.hashCode;

  factory ExamCoachOperationWrite.fromJson(Map<String, dynamic> json) =>
      _$ExamCoachOperationWriteFromJson(json);

  Map<String, dynamic> toJson() => _$ExamCoachOperationWriteToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
