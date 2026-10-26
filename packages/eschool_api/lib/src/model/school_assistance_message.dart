//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'school_assistance_message.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SchoolAssistanceMessage {
  /// Returns a new [SchoolAssistanceMessage] instance.
  SchoolAssistanceMessage({
    required this.id,

    required this.authorKind,

    required this.text,

    required this.language,

    required this.sentAt,
  });

  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final String id;

  @JsonKey(
    name: r'author_kind',
    required: true,
    includeIfNull: false,
    unknownEnumValue:
        SchoolAssistanceMessageAuthorKindEnum.unknownDefaultOpenApi,
  )
  final SchoolAssistanceMessageAuthorKindEnum authorKind;

  @JsonKey(name: r'text', required: true, includeIfNull: false)
  final String text;

  @JsonKey(name: r'language', required: true, includeIfNull: false)
  final String language;

  @JsonKey(name: r'sent_at', required: true, includeIfNull: false)
  final DateTime sentAt;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is SchoolAssistanceMessage &&
          other.id == id &&
          other.authorKind == authorKind &&
          other.text == text &&
          other.language == language &&
          other.sentAt == sentAt;

  @override
  int get hashCode =>
      id.hashCode +
      authorKind.hashCode +
      text.hashCode +
      language.hashCode +
      sentAt.hashCode;

  factory SchoolAssistanceMessage.fromJson(Map<String, dynamic> json) =>
      _$SchoolAssistanceMessageFromJson(json);

  Map<String, dynamic> toJson() => _$SchoolAssistanceMessageToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum SchoolAssistanceMessageAuthorKindEnum {
  @JsonValue(r'parent')
  parent(r'parent'),
  @JsonValue(r'office')
  office(r'office'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const SchoolAssistanceMessageAuthorKindEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
