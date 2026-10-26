//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'school_assistance_publish_request.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SchoolAssistancePublishRequest {
  /// Returns a new [SchoolAssistancePublishRequest] instance.
  SchoolAssistancePublishRequest({
    required this.operationId,

    this.documentKey,

    required this.expectedRevision,

    required this.title,

    required this.markdown,

    required this.sourceReference,

    required this.identifiersReviewed,

    this.expiresOn,
  });

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  @JsonKey(name: r'document_key', required: false, includeIfNull: false)
  final String? documentKey;

  // minimum: 0
  @JsonKey(name: r'expected_revision', required: true, includeIfNull: false)
  final int expectedRevision;

  @JsonKey(name: r'title', required: true, includeIfNull: false)
  final String title;

  @JsonKey(name: r'markdown', required: true, includeIfNull: false)
  final String markdown;

  @JsonKey(name: r'source_reference', required: true, includeIfNull: false)
  final String sourceReference;

  /// Must be true: human-approved school publication and identifiers review.
  @JsonKey(name: r'identifiers_reviewed', required: true, includeIfNull: false)
  final bool identifiersReviewed;

  @JsonKey(name: r'expires_on', required: false, includeIfNull: false)
  final String? expiresOn;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is SchoolAssistancePublishRequest &&
          other.operationId == operationId &&
          other.documentKey == documentKey &&
          other.expectedRevision == expectedRevision &&
          other.title == title &&
          other.markdown == markdown &&
          other.sourceReference == sourceReference &&
          other.identifiersReviewed == identifiersReviewed &&
          other.expiresOn == expiresOn;

  @override
  int get hashCode =>
      operationId.hashCode +
      (documentKey == null ? 0 : documentKey.hashCode) +
      expectedRevision.hashCode +
      title.hashCode +
      markdown.hashCode +
      sourceReference.hashCode +
      identifiersReviewed.hashCode +
      (expiresOn == null ? 0 : expiresOn.hashCode);

  factory SchoolAssistancePublishRequest.fromJson(Map<String, dynamic> json) =>
      _$SchoolAssistancePublishRequestFromJson(json);

  Map<String, dynamic> toJson() => _$SchoolAssistancePublishRequestToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
