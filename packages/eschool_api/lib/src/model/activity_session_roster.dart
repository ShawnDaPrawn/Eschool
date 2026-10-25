//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/activity_session_roster_learner.dart';
import 'package:json_annotation/json_annotation.dart';

part 'activity_session_roster.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ActivitySessionRoster {
  /// Returns a new [ActivitySessionRoster] instance.
  ActivitySessionRoster({
    required this.sessionId,

    required this.groupId,

    required this.sessionRevision,

    required this.learners,
  });

  // minimum: 1
  @JsonKey(name: r'session_id', required: true, includeIfNull: false)
  final int sessionId;

  // minimum: 1
  @JsonKey(name: r'group_id', required: true, includeIfNull: false)
  final int groupId;

  // minimum: 1
  @JsonKey(name: r'session_revision', required: true, includeIfNull: false)
  final int sessionRevision;

  @JsonKey(name: r'learners', required: true, includeIfNull: false)
  final List<ActivitySessionRosterLearner> learners;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ActivitySessionRoster &&
          other.sessionId == sessionId &&
          other.groupId == groupId &&
          other.sessionRevision == sessionRevision &&
          other.learners == learners;

  @override
  int get hashCode =>
      sessionId.hashCode +
      groupId.hashCode +
      sessionRevision.hashCode +
      learners.hashCode;

  factory ActivitySessionRoster.fromJson(Map<String, dynamic> json) =>
      _$ActivitySessionRosterFromJson(json);

  Map<String, dynamic> toJson() => _$ActivitySessionRosterToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
