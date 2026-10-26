//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'school_assistance_translation_request.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SchoolAssistanceTranslationRequest {
  /// Returns a new [SchoolAssistanceTranslationRequest] instance.
  SchoolAssistanceTranslationRequest({
    required this.operationId,

    required this.messageId,

    required this.language,
  });

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  @JsonKey(name: r'message_id', required: true, includeIfNull: false)
  final String messageId;

  @JsonKey(name: r'language', required: true, includeIfNull: false)
  final String language;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is SchoolAssistanceTranslationRequest &&
          other.operationId == operationId &&
          other.messageId == messageId &&
          other.language == language;

  @override
  int get hashCode =>
      operationId.hashCode + messageId.hashCode + language.hashCode;

  factory SchoolAssistanceTranslationRequest.fromJson(
    Map<String, dynamic> json,
  ) => _$SchoolAssistanceTranslationRequestFromJson(json);

  Map<String, dynamic> toJson() =>
      _$SchoolAssistanceTranslationRequestToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
