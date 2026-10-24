//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/content_source.dart';
import 'package:eschool_api/src/model/content_selection.dart';
import 'package:json_annotation/json_annotation.dart';

part 'content_version.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ContentVersion {
  /// Returns a new [ContentVersion] instance.
  ContentVersion({
    required this.id,

    required this.contentItemId,

    required this.number,

    required this.state,

    required this.selection,

    required this.markdown,

    required this.revision,

    required this.failureCode,

    required this.durationSeconds,

    required this.language,

    required this.createdAt,

    required this.publishedAt,

    required this.source_,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  // minimum: 1
  @JsonKey(name: r'content_item_id', required: true, includeIfNull: false)
  final int contentItemId;

  @JsonKey(name: r'number', required: true, includeIfNull: false)
  final int number;

  @JsonKey(
    name: r'state',
    required: true,
    includeIfNull: false,
    unknownEnumValue: ContentVersionStateEnum.unknownDefaultOpenApi,
  )
  final ContentVersionStateEnum state;

  @JsonKey(name: r'selection', required: true, includeIfNull: false)
  final ContentSelection selection;

  @JsonKey(name: r'markdown', required: true, includeIfNull: true)
  final String? markdown;

  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @JsonKey(name: r'failure_code', required: true, includeIfNull: true)
  final String? failureCode;

  @JsonKey(name: r'duration_seconds', required: true, includeIfNull: true)
  final int? durationSeconds;

  @JsonKey(name: r'language', required: true, includeIfNull: true)
  final String? language;

  @JsonKey(name: r'created_at', required: true, includeIfNull: false)
  final DateTime createdAt;

  @JsonKey(name: r'published_at', required: true, includeIfNull: true)
  final DateTime? publishedAt;

  @JsonKey(name: r'source', required: true, includeIfNull: false)
  final ContentSource source_;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ContentVersion &&
          other.id == id &&
          other.contentItemId == contentItemId &&
          other.number == number &&
          other.state == state &&
          other.selection == selection &&
          other.markdown == markdown &&
          other.revision == revision &&
          other.failureCode == failureCode &&
          other.durationSeconds == durationSeconds &&
          other.language == language &&
          other.createdAt == createdAt &&
          other.publishedAt == publishedAt &&
          other.source_ == source_;

  @override
  int get hashCode =>
      id.hashCode +
      contentItemId.hashCode +
      number.hashCode +
      state.hashCode +
      selection.hashCode +
      (markdown == null ? 0 : markdown.hashCode) +
      revision.hashCode +
      (failureCode == null ? 0 : failureCode.hashCode) +
      (durationSeconds == null ? 0 : durationSeconds.hashCode) +
      (language == null ? 0 : language.hashCode) +
      createdAt.hashCode +
      (publishedAt == null ? 0 : publishedAt.hashCode) +
      source_.hashCode;

  factory ContentVersion.fromJson(Map<String, dynamic> json) =>
      _$ContentVersionFromJson(json);

  Map<String, dynamic> toJson() => _$ContentVersionToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum ContentVersionStateEnum {
  @JsonValue(r'queued')
  queued(r'queued'),
  @JsonValue(r'scanning')
  scanning(r'scanning'),
  @JsonValue(r'converting')
  converting(r'converting'),
  @JsonValue(r'draft')
  draft(r'draft'),
  @JsonValue(r'published')
  published(r'published'),
  @JsonValue(r'failed')
  failed(r'failed'),
  @JsonValue(r'quarantined')
  quarantined(r'quarantined'),
  @JsonValue(r'superseded')
  superseded(r'superseded'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const ContentVersionStateEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
