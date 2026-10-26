//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'school_assistance_prepare_answer_request.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SchoolAssistancePrepareAnswerRequest {
  /// Returns a new [SchoolAssistancePrepareAnswerRequest] instance.
  SchoolAssistancePrepareAnswerRequest({
    required this.operationId,

    required this.documents,

    required this.includeCalendar,

    required this.includeRecords,

    required this.question,

    required this.language,
  });

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  @JsonKey(name: r'documents', required: true, includeIfNull: false)
  final List<String> documents;

  @JsonKey(name: r'include_calendar', required: true, includeIfNull: false)
  final bool includeCalendar;

  @JsonKey(name: r'include_records', required: true, includeIfNull: false)
  final bool includeRecords;

  @JsonKey(name: r'question', required: true, includeIfNull: false)
  final String question;

  @JsonKey(name: r'language', required: true, includeIfNull: false)
  final String language;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is SchoolAssistancePrepareAnswerRequest &&
          other.operationId == operationId &&
          other.documents == documents &&
          other.includeCalendar == includeCalendar &&
          other.includeRecords == includeRecords &&
          other.question == question &&
          other.language == language;

  @override
  int get hashCode =>
      operationId.hashCode +
      documents.hashCode +
      includeCalendar.hashCode +
      includeRecords.hashCode +
      question.hashCode +
      language.hashCode;

  factory SchoolAssistancePrepareAnswerRequest.fromJson(
    Map<String, dynamic> json,
  ) => _$SchoolAssistancePrepareAnswerRequestFromJson(json);

  Map<String, dynamic> toJson() =>
      _$SchoolAssistancePrepareAnswerRequestToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
