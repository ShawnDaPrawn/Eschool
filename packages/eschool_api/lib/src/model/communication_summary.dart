//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'communication_summary.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class CommunicationSummary {
  /// Returns a new [CommunicationSummary] instance.
  CommunicationSummary({
    required this.id,

    required this.title,

    required this.state,

    required this.revision,

    required this.publishedAt,

    required this.scheduledAt,

    required this.updatedAt,

    this.readAt,

    this.isAuthor,

    this.canReview,
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
    unknownEnumValue: CommunicationSummaryStateEnum.unknownDefaultOpenApi,
  )
  final CommunicationSummaryStateEnum state;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @JsonKey(name: r'published_at', required: true, includeIfNull: true)
  final DateTime? publishedAt;

  @JsonKey(name: r'scheduled_at', required: true, includeIfNull: true)
  final DateTime? scheduledAt;

  @JsonKey(name: r'updated_at', required: true, includeIfNull: false)
  final DateTime updatedAt;

  @JsonKey(name: r'read_at', required: false, includeIfNull: false)
  final DateTime? readAt;

  @JsonKey(name: r'is_author', required: false, includeIfNull: false)
  final bool? isAuthor;

  @JsonKey(name: r'can_review', required: false, includeIfNull: false)
  final bool? canReview;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is CommunicationSummary &&
          other.id == id &&
          other.title == title &&
          other.state == state &&
          other.revision == revision &&
          other.publishedAt == publishedAt &&
          other.scheduledAt == scheduledAt &&
          other.updatedAt == updatedAt &&
          other.readAt == readAt &&
          other.isAuthor == isAuthor &&
          other.canReview == canReview;

  @override
  int get hashCode =>
      id.hashCode +
      title.hashCode +
      state.hashCode +
      revision.hashCode +
      (publishedAt == null ? 0 : publishedAt.hashCode) +
      (scheduledAt == null ? 0 : scheduledAt.hashCode) +
      updatedAt.hashCode +
      (readAt == null ? 0 : readAt.hashCode) +
      isAuthor.hashCode +
      canReview.hashCode;

  factory CommunicationSummary.fromJson(Map<String, dynamic> json) =>
      _$CommunicationSummaryFromJson(json);

  Map<String, dynamic> toJson() => _$CommunicationSummaryToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum CommunicationSummaryStateEnum {
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

  const CommunicationSummaryStateEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
