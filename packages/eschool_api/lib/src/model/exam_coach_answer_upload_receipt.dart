//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'exam_coach_answer_upload_receipt.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ExamCoachAnswerUploadReceipt {
  /// Returns a new [ExamCoachAnswerUploadReceipt] instance.
  ExamCoachAnswerUploadReceipt({
    required this.id,

    required this.operationId,

    required this.state,
  });

  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final String id;

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  @JsonKey(
    name: r'state',
    required: true,
    includeIfNull: false,
    unknownEnumValue:
        ExamCoachAnswerUploadReceiptStateEnum.unknownDefaultOpenApi,
  )
  final ExamCoachAnswerUploadReceiptStateEnum state;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ExamCoachAnswerUploadReceipt &&
          other.id == id &&
          other.operationId == operationId &&
          other.state == state;

  @override
  int get hashCode => id.hashCode + operationId.hashCode + state.hashCode;

  factory ExamCoachAnswerUploadReceipt.fromJson(Map<String, dynamic> json) =>
      _$ExamCoachAnswerUploadReceiptFromJson(json);

  Map<String, dynamic> toJson() => _$ExamCoachAnswerUploadReceiptToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum ExamCoachAnswerUploadReceiptStateEnum {
  @JsonValue(r'prepared')
  prepared(r'prepared'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const ExamCoachAnswerUploadReceiptStateEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
