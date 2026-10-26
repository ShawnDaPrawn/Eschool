//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'school_assistance_open_thread_request.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SchoolAssistanceOpenThreadRequest {
  /// Returns a new [SchoolAssistanceOpenThreadRequest] instance.
  SchoolAssistanceOpenThreadRequest({
    required this.operationId,

    required this.text,

    required this.language,
  });

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  @JsonKey(name: r'text', required: true, includeIfNull: false)
  final String text;

  @JsonKey(name: r'language', required: true, includeIfNull: false)
  final String language;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is SchoolAssistanceOpenThreadRequest &&
          other.operationId == operationId &&
          other.text == text &&
          other.language == language;

  @override
  int get hashCode => operationId.hashCode + text.hashCode + language.hashCode;

  factory SchoolAssistanceOpenThreadRequest.fromJson(
    Map<String, dynamic> json,
  ) => _$SchoolAssistanceOpenThreadRequestFromJson(json);

  Map<String, dynamic> toJson() =>
      _$SchoolAssistanceOpenThreadRequestToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
