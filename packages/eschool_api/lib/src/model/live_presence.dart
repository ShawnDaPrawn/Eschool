//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'live_presence.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class LivePresence {
  /// Returns a new [LivePresence] instance.
  LivePresence({
    required this.type,

    required this.schoolId,

    required this.groupId,

    required this.revision,

    required this.onlineCount,
  });

  @JsonKey(name: r'type', required: true, includeIfNull: false)
  final String type;

  // minimum: 1
  @JsonKey(name: r'school_id', required: true, includeIfNull: false)
  final int schoolId;

  // minimum: 1
  @JsonKey(name: r'group_id', required: true, includeIfNull: false)
  final int groupId;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  // minimum: 0
  @JsonKey(name: r'online_count', required: true, includeIfNull: false)
  final int onlineCount;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is LivePresence &&
          other.type == type &&
          other.schoolId == schoolId &&
          other.groupId == groupId &&
          other.revision == revision &&
          other.onlineCount == onlineCount;

  @override
  int get hashCode =>
      type.hashCode +
      schoolId.hashCode +
      groupId.hashCode +
      revision.hashCode +
      onlineCount.hashCode;

  factory LivePresence.fromJson(Map<String, dynamic> json) =>
      _$LivePresenceFromJson(json);

  Map<String, dynamic> toJson() => _$LivePresenceToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
