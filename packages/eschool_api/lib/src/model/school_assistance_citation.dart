//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/school_assistance_source_locator.dart';
import 'package:json_annotation/json_annotation.dart';

part 'school_assistance_citation.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SchoolAssistanceCitation {
  /// Returns a new [SchoolAssistanceCitation] instance.
  SchoolAssistanceCitation({
    required this.token,

    required this.kind,

    required this.title,

    required this.locator,
  });

  @JsonKey(name: r'token', required: true, includeIfNull: false)
  final String token;

  @JsonKey(
    name: r'kind',
    required: true,
    includeIfNull: false,
    unknownEnumValue: SchoolAssistanceCitationKindEnum.unknownDefaultOpenApi,
  )
  final SchoolAssistanceCitationKindEnum kind;

  @JsonKey(name: r'title', required: true, includeIfNull: false)
  final String title;

  @JsonKey(name: r'locator', required: true, includeIfNull: false)
  final SchoolAssistanceSourceLocator locator;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is SchoolAssistanceCitation &&
          other.token == token &&
          other.kind == kind &&
          other.title == title &&
          other.locator == locator;

  @override
  int get hashCode =>
      token.hashCode + kind.hashCode + title.hashCode + locator.hashCode;

  factory SchoolAssistanceCitation.fromJson(Map<String, dynamic> json) =>
      _$SchoolAssistanceCitationFromJson(json);

  Map<String, dynamic> toJson() => _$SchoolAssistanceCitationToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum SchoolAssistanceCitationKindEnum {
  @JsonValue(r'document')
  document(r'document'),
  @JsonValue(r'calendar')
  calendar(r'calendar'),
  @JsonValue(r'released_marks')
  releasedMarks(r'released_marks'),
  @JsonValue(r'attendance')
  attendance(r'attendance'),
  @JsonValue(r'support_message')
  supportMessage(r'support_message'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const SchoolAssistanceCitationKindEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
