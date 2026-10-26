//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'teaching_draft_section.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class TeachingDraftSection {
  /// Returns a new [TeachingDraftSection] instance.
  TeachingDraftSection({required this.level, required this.body});

  @JsonKey(
    name: r'level',
    required: true,
    includeIfNull: false,
    unknownEnumValue: TeachingDraftSectionLevelEnum.unknownDefaultOpenApi,
  )
  final TeachingDraftSectionLevelEnum level;

  @JsonKey(name: r'body', required: true, includeIfNull: false)
  final String body;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is TeachingDraftSection &&
          other.level == level &&
          other.body == body;

  @override
  int get hashCode => level.hashCode + body.hashCode;

  factory TeachingDraftSection.fromJson(Map<String, dynamic> json) =>
      _$TeachingDraftSectionFromJson(json);

  Map<String, dynamic> toJson() => _$TeachingDraftSectionToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum TeachingDraftSectionLevelEnum {
  @JsonValue(r'support')
  support(r'support'),
  @JsonValue(r'core')
  core(r'core'),
  @JsonValue(r'extension')
  extension_(r'extension'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const TeachingDraftSectionLevelEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
