//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/exam_coach_answer_revocation.dart';
import 'package:json_annotation/json_annotation.dart';

part 'exam_coach_answer_revocation_response.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ExamCoachAnswerRevocationResponse {
  /// Returns a new [ExamCoachAnswerRevocationResponse] instance.
  ExamCoachAnswerRevocationResponse({
    required this.error,

    required this.message,

    required this.data,

    required this.code,
  });

  @JsonKey(name: r'error', required: true, includeIfNull: false)
  final bool error;

  @JsonKey(name: r'message', required: true, includeIfNull: false)
  final String message;

  @JsonKey(name: r'data', required: true, includeIfNull: false)
  final ExamCoachAnswerRevocation data;

  // minimum: 0
  @JsonKey(name: r'code', required: true, includeIfNull: false)
  final int code;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ExamCoachAnswerRevocationResponse &&
          other.error == error &&
          other.message == message &&
          other.data == data &&
          other.code == code;

  @override
  int get hashCode =>
      error.hashCode + message.hashCode + data.hashCode + code.hashCode;

  factory ExamCoachAnswerRevocationResponse.fromJson(
    Map<String, dynamic> json,
  ) => _$ExamCoachAnswerRevocationResponseFromJson(json);

  Map<String, dynamic> toJson() =>
      _$ExamCoachAnswerRevocationResponseToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
