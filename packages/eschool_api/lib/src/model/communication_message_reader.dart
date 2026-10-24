//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'communication_message_reader.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class CommunicationMessageReader {
  /// Returns a new [CommunicationMessageReader] instance.
  CommunicationMessageReader({
    required this.id,

    required this.title,

    required this.state,

    required this.revision,

    required this.publishedAt,

    required this.scheduledAt,

    required this.updatedAt,

    required this.schoolId,

    required this.readerId,

    required this.markdown,

    required this.readAt,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(name: r'title', required: true, includeIfNull: false)
  final String title;

  @JsonKey(
    name: r'state',
    required: true,
    includeIfNull: false,
    unknownEnumValue: CommunicationMessageReaderStateEnum.unknownDefaultOpenApi,
  )
  final CommunicationMessageReaderStateEnum state;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @JsonKey(name: r'published_at', required: true, includeIfNull: true)
  final DateTime? publishedAt;

  @JsonKey(name: r'scheduled_at', required: true, includeIfNull: true)
  final DateTime? scheduledAt;

  @JsonKey(name: r'updated_at', required: true, includeIfNull: false)
  final DateTime updatedAt;

  // minimum: 1
  @JsonKey(name: r'school_id', required: true, includeIfNull: false)
  final int schoolId;

  // minimum: 1
  @JsonKey(name: r'reader_id', required: true, includeIfNull: false)
  final int readerId;

  @JsonKey(name: r'markdown', required: true, includeIfNull: false)
  final String markdown;

  @JsonKey(name: r'read_at', required: true, includeIfNull: true)
  final DateTime? readAt;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is CommunicationMessageReader &&
          other.id == id &&
          other.title == title &&
          other.state == state &&
          other.revision == revision &&
          other.publishedAt == publishedAt &&
          other.scheduledAt == scheduledAt &&
          other.updatedAt == updatedAt &&
          other.schoolId == schoolId &&
          other.readerId == readerId &&
          other.markdown == markdown &&
          other.readAt == readAt;

  @override
  int get hashCode =>
      id.hashCode +
      title.hashCode +
      state.hashCode +
      revision.hashCode +
      (publishedAt == null ? 0 : publishedAt.hashCode) +
      (scheduledAt == null ? 0 : scheduledAt.hashCode) +
      updatedAt.hashCode +
      schoolId.hashCode +
      readerId.hashCode +
      markdown.hashCode +
      (readAt == null ? 0 : readAt.hashCode);

  factory CommunicationMessageReader.fromJson(Map<String, dynamic> json) =>
      _$CommunicationMessageReaderFromJson(json);

  Map<String, dynamic> toJson() => _$CommunicationMessageReaderToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum CommunicationMessageReaderStateEnum {
  @JsonValue(r'draft')
  draft(r'draft'),
  @JsonValue(r'pending')
  pending(r'pending'),
  @JsonValue(r'approved')
  approved(r'approved'),
  @JsonValue(r'dispatching')
  dispatching(r'dispatching'),
  @JsonValue(r'sent')
  sent(r'sent'),
  @JsonValue(r'rejected')
  rejected(r'rejected'),
  @JsonValue(r'blocked')
  blocked(r'blocked'),
  @JsonValue(r'withdrawn')
  withdrawn(r'withdrawn'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const CommunicationMessageReaderStateEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
