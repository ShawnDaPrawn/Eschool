//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'school_assistance_document_result.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SchoolAssistanceDocumentResult {
  /// Returns a new [SchoolAssistanceDocumentResult] instance.
  SchoolAssistanceDocumentResult({
    required this.id,

    required this.documentKey,

    required this.revision,

    required this.title,

    required this.sourceReference,

    required this.expiresOn,

    required this.state,

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
        SchoolAssistanceDocumentResultStateEnum.unknownDefaultOpenApi,
  )
  final SchoolAssistanceDocumentResultStateEnum state;

  // minimum: 1
  @JsonKey(name: r'school_id', required: true, includeIfNull: false)
  final int schoolId;

  // minimum: 1
  @JsonKey(name: r'reader_id', required: true, includeIfNull: false)
  final int readerId;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is SchoolAssistanceDocumentResult &&
          other.id == id &&
          other.documentKey == documentKey &&
          other.revision == revision &&
          other.title == title &&
          other.sourceReference == sourceReference &&
          other.expiresOn == expiresOn &&
          other.state == state &&
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
      schoolId.hashCode +
      readerId.hashCode;

  factory SchoolAssistanceDocumentResult.fromJson(Map<String, dynamic> json) =>
      _$SchoolAssistanceDocumentResultFromJson(json);

  Map<String, dynamic> toJson() => _$SchoolAssistanceDocumentResultToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum SchoolAssistanceDocumentResultStateEnum {
  @JsonValue(r'published')
  published(r'published'),
  @JsonValue(r'withdrawn')
  withdrawn(r'withdrawn'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const SchoolAssistanceDocumentResultStateEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
