//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/content_topic.dart';
import 'package:json_annotation/json_annotation.dart';

part 'content_reader.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ContentReader {
  /// Returns a new [ContentReader] instance.
  ContentReader({
    required this.id,

    required this.contentItemId,

    required this.learnerId,

    required this.schoolId,

    required this.readerId,

    required this.title,

    required this.summary,

    required this.markdown,

    required this.bodySha256,

    required this.publishedAt,

    required this.topics,

    required this.serverTime,

    required this.offlineUntil,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  // minimum: 1
  @JsonKey(name: r'content_item_id', required: true, includeIfNull: false)
  final int contentItemId;

  // minimum: 1
  @JsonKey(name: r'learner_id', required: true, includeIfNull: false)
  final int learnerId;

  // minimum: 1
  @JsonKey(name: r'school_id', required: true, includeIfNull: false)
  final int schoolId;

  // minimum: 1
  @JsonKey(name: r'reader_id', required: true, includeIfNull: false)
  final int readerId;

  @JsonKey(name: r'title', required: true, includeIfNull: false)
  final String title;

  @JsonKey(name: r'summary', required: true, includeIfNull: false)
  final String summary;

  @JsonKey(name: r'markdown', required: true, includeIfNull: false)
  final String markdown;

  @JsonKey(name: r'body_sha256', required: true, includeIfNull: false)
  final String bodySha256;

  @JsonKey(name: r'published_at', required: true, includeIfNull: false)
  final DateTime publishedAt;

  @JsonKey(name: r'topics', required: true, includeIfNull: false)
  final List<ContentTopic> topics;

  @JsonKey(name: r'server_time', required: true, includeIfNull: false)
  final DateTime serverTime;

  @JsonKey(name: r'offline_until', required: true, includeIfNull: false)
  final DateTime offlineUntil;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ContentReader &&
          other.id == id &&
          other.contentItemId == contentItemId &&
          other.learnerId == learnerId &&
          other.schoolId == schoolId &&
          other.readerId == readerId &&
          other.title == title &&
          other.summary == summary &&
          other.markdown == markdown &&
          other.bodySha256 == bodySha256 &&
          other.publishedAt == publishedAt &&
          other.topics == topics &&
          other.serverTime == serverTime &&
          other.offlineUntil == offlineUntil;

  @override
  int get hashCode =>
      id.hashCode +
      contentItemId.hashCode +
      learnerId.hashCode +
      schoolId.hashCode +
      readerId.hashCode +
      title.hashCode +
      summary.hashCode +
      markdown.hashCode +
      bodySha256.hashCode +
      publishedAt.hashCode +
      topics.hashCode +
      serverTime.hashCode +
      offlineUntil.hashCode;

  factory ContentReader.fromJson(Map<String, dynamic> json) =>
      _$ContentReaderFromJson(json);

  Map<String, dynamic> toJson() => _$ContentReaderToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
