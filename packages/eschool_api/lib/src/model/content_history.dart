//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'content_history.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ContentHistory {
  /// Returns a new [ContentHistory] instance.
  ContentHistory({
    required this.id,

    required this.number,

    required this.state,

    required this.title,

    required this.publishedAt,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(name: r'number', required: true, includeIfNull: false)
  final int number;

  @JsonKey(
    name: r'state',
    required: true,
    includeIfNull: false,
    unknownEnumValue: ContentHistoryStateEnum.unknownDefaultOpenApi,
  )
  final ContentHistoryStateEnum state;

  @JsonKey(name: r'title', required: true, includeIfNull: false)
  final String title;

  @JsonKey(name: r'published_at', required: true, includeIfNull: true)
  final DateTime? publishedAt;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ContentHistory &&
          other.id == id &&
          other.number == number &&
          other.state == state &&
          other.title == title &&
          other.publishedAt == publishedAt;

  @override
  int get hashCode =>
      id.hashCode +
      number.hashCode +
      state.hashCode +
      title.hashCode +
      (publishedAt == null ? 0 : publishedAt.hashCode);

  factory ContentHistory.fromJson(Map<String, dynamic> json) =>
      _$ContentHistoryFromJson(json);

  Map<String, dynamic> toJson() => _$ContentHistoryToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum ContentHistoryStateEnum {
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

  const ContentHistoryStateEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
