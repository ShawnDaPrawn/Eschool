//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'school_assistance_thread_metadata.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SchoolAssistanceThreadMetadata {
  /// Returns a new [SchoolAssistanceThreadMetadata] instance.
  SchoolAssistanceThreadMetadata({
    required this.id,

    required this.state,

    required this.revision,

    required this.createdAt,
  });

  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final String id;

  @JsonKey(
    name: r'state',
    required: true,
    includeIfNull: false,
    unknownEnumValue:
        SchoolAssistanceThreadMetadataStateEnum.unknownDefaultOpenApi,
  )
  final SchoolAssistanceThreadMetadataStateEnum state;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @JsonKey(name: r'created_at', required: true, includeIfNull: false)
  final DateTime createdAt;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is SchoolAssistanceThreadMetadata &&
          other.id == id &&
          other.state == state &&
          other.revision == revision &&
          other.createdAt == createdAt;

  @override
  int get hashCode =>
      id.hashCode + state.hashCode + revision.hashCode + createdAt.hashCode;

  factory SchoolAssistanceThreadMetadata.fromJson(Map<String, dynamic> json) =>
      _$SchoolAssistanceThreadMetadataFromJson(json);

  Map<String, dynamic> toJson() => _$SchoolAssistanceThreadMetadataToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum SchoolAssistanceThreadMetadataStateEnum {
  @JsonValue(r'open')
  open(r'open'),
  @JsonValue(r'closed')
  closed(r'closed'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const SchoolAssistanceThreadMetadataStateEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
