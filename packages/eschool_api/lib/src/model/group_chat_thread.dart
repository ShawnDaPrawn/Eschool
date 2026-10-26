//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/group_chat_audience.dart';
import 'package:json_annotation/json_annotation.dart';

part 'group_chat_thread.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class GroupChatThread {
  /// Returns a new [GroupChatThread] instance.
  GroupChatThread({
    required this.id,

    required this.activityGroupId,

    required this.title,

    required this.audience,

    required this.private,

    required this.closed,

    required this.revision,

    required this.lastMessageAt,
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

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is GroupChatThread &&
          other.id == id &&
          other.activityGroupId == activityGroupId &&
          other.title == title &&
          other.audience == audience &&
          other.private == private &&
          other.closed == closed &&
          other.revision == revision &&
          other.lastMessageAt == lastMessageAt;

  @override
  int get hashCode =>
      id.hashCode +
      activityGroupId.hashCode +
      (title == null ? 0 : title.hashCode) +
      audience.hashCode +
      private.hashCode +
      closed.hashCode +
      revision.hashCode +
      lastMessageAt.hashCode;

  factory GroupChatThread.fromJson(Map<String, dynamic> json) =>
      _$GroupChatThreadFromJson(json);

  Map<String, dynamic> toJson() => _$GroupChatThreadToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
