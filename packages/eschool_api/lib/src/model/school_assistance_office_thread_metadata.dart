//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'school_assistance_office_thread_metadata.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SchoolAssistanceOfficeThreadMetadata {
  /// Returns a new [SchoolAssistanceOfficeThreadMetadata] instance.
  SchoolAssistanceOfficeThreadMetadata({
    required this.id,

    required this.state,

    required this.revision,

    required this.createdAt,

    required this.assignedToReader,
  });

  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final String id;

  @JsonKey(
    name: r'state',
    required: true,
    includeIfNull: false,
    unknownEnumValue:
        SchoolAssistanceOfficeThreadMetadataStateEnum.unknownDefaultOpenApi,
  )
  final SchoolAssistanceOfficeThreadMetadataStateEnum state;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @JsonKey(name: r'created_at', required: true, includeIfNull: false)
  final DateTime createdAt;

  @JsonKey(name: r'assigned_to_reader', required: true, includeIfNull: false)
  final bool assignedToReader;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is SchoolAssistanceOfficeThreadMetadata &&
          other.id == id &&
          other.state == state &&
          other.revision == revision &&
          other.createdAt == createdAt &&
          other.assignedToReader == assignedToReader;

  @override
  int get hashCode =>
      id.hashCode +
      state.hashCode +
      revision.hashCode +
      createdAt.hashCode +
      assignedToReader.hashCode;

  factory SchoolAssistanceOfficeThreadMetadata.fromJson(
    Map<String, dynamic> json,
  ) => _$SchoolAssistanceOfficeThreadMetadataFromJson(json);

  Map<String, dynamic> toJson() =>
      _$SchoolAssistanceOfficeThreadMetadataToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum SchoolAssistanceOfficeThreadMetadataStateEnum {
  @JsonValue(r'open')
  open(r'open'),
  @JsonValue(r'closed')
  closed(r'closed'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const SchoolAssistanceOfficeThreadMetadataStateEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
