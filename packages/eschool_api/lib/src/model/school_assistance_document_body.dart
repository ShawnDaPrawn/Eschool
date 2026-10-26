//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'school_assistance_document_body.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SchoolAssistanceDocumentBody {
  /// Returns a new [SchoolAssistanceDocumentBody] instance.
  SchoolAssistanceDocumentBody({
    required this.id,

    required this.documentKey,

    required this.revision,

    required this.title,

    required this.sourceReference,

    required this.expiresOn,

    required this.state,

    required this.markdown,

    required this.schoolId,

    required this.readerId,
  });

  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final String id;

  @JsonKey(name: r'document_key', required: true, includeIfNull: false)
  final String documentKey;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @JsonKey(name: r'title', required: true, includeIfNull: false)
  final String title;

  @JsonKey(name: r'source_reference', required: true, includeIfNull: false)
  final String sourceReference;

  @JsonKey(name: r'expires_on', required: true, includeIfNull: true)
  final String? expiresOn;

  @JsonKey(
    name: r'state',
    required: true,
    includeIfNull: false,
    unknownEnumValue:
        SchoolAssistanceDocumentBodyStateEnum.unknownDefaultOpenApi,
  )
  final SchoolAssistanceDocumentBodyStateEnum state;

  @JsonKey(name: r'markdown', required: true, includeIfNull: false)
  final String markdown;

  // minimum: 1
  @JsonKey(name: r'school_id', required: true, includeIfNull: false)
  final int schoolId;

  // minimum: 1
  @JsonKey(name: r'reader_id', required: true, includeIfNull: false)
  final int readerId;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is SchoolAssistanceDocumentBody &&
          other.id == id &&
          other.documentKey == documentKey &&
          other.revision == revision &&
          other.title == title &&
          other.sourceReference == sourceReference &&
          other.expiresOn == expiresOn &&
          other.state == state &&
          other.markdown == markdown &&
          other.schoolId == schoolId &&
          other.readerId == readerId;

  @override
  int get hashCode =>
      id.hashCode +
      documentKey.hashCode +
      revision.hashCode +
      title.hashCode +
      sourceReference.hashCode +
      (expiresOn == null ? 0 : expiresOn.hashCode) +
      state.hashCode +
      markdown.hashCode +
      schoolId.hashCode +
      readerId.hashCode;

  factory SchoolAssistanceDocumentBody.fromJson(Map<String, dynamic> json) =>
      _$SchoolAssistanceDocumentBodyFromJson(json);

  Map<String, dynamic> toJson() => _$SchoolAssistanceDocumentBodyToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum SchoolAssistanceDocumentBodyStateEnum {
  @JsonValue(r'published')
  published(r'published'),
  @JsonValue(r'withdrawn')
  withdrawn(r'withdrawn'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const SchoolAssistanceDocumentBodyStateEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
