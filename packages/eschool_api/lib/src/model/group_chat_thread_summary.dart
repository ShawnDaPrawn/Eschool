//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/group_chat_audience.dart';
import 'package:json_annotation/json_annotation.dart';

part 'group_chat_thread_summary.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class GroupChatThreadSummary {
  /// Returns a new [GroupChatThreadSummary] instance.
  GroupChatThreadSummary({
    required this.id,

    required this.activityGroupId,

    required this.title,

    required this.audience,

    required this.private,

    required this.closed,

    required this.revision,

    required this.lastMessageAt,

    required this.unread,
  });

  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final String id;

  // minimum: 1
  @JsonKey(name: r'activity_group_id', required: true, includeIfNull: false)
  final int activityGroupId;

  @JsonKey(name: r'title', required: true, includeIfNull: true)
  final String? title;

  @JsonKey(
    name: r'audience',
    required: true,
    includeIfNull: false,
    unknownEnumValue: GroupChatAudience.unknownDefaultOpenApi,
  )
  final GroupChatAudience audience;

  @JsonKey(name: r'private', required: true, includeIfNull: false)
  final bool private;

  @JsonKey(name: r'closed', required: true, includeIfNull: false)
  final bool closed;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @JsonKey(name: r'last_message_at', required: true, includeIfNull: false)
  final DateTime lastMessageAt;

  // minimum: 0
  @JsonKey(name: r'unread', required: true, includeIfNull: false)
  final int unread;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is GroupChatThreadSummary &&
          other.id == id &&
          other.activityGroupId == activityGroupId &&
          other.title == title &&
          other.audience == audience &&
          other.private == private &&
          other.closed == closed &&
          other.revision == revision &&
          other.lastMessageAt == lastMessageAt &&
          other.unread == unread;

  @override
  int get hashCode =>
      id.hashCode +
      activityGroupId.hashCode +
      (title == null ? 0 : title.hashCode) +
      audience.hashCode +
      private.hashCode +
      closed.hashCode +
      revision.hashCode +
      lastMessageAt.hashCode +
      unread.hashCode;

  factory GroupChatThreadSummary.fromJson(Map<String, dynamic> json) =>
      _$GroupChatThreadSummaryFromJson(json);

  Map<String, dynamic> toJson() => _$GroupChatThreadSummaryToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
