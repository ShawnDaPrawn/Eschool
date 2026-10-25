//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/activity_group_staff.dart';
import 'package:eschool_api/src/model/activity_attendance.dart';
import 'package:eschool_api/src/model/activity_session_roster.dart';
import 'package:eschool_api/src/model/activity_roster_member.dart';
import 'package:json_annotation/json_annotation.dart';

part 'activity_group_roster.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ActivityGroupRoster {
  /// Returns a new [ActivityGroupRoster] instance.
  ActivityGroupRoster({
    required this.memberships,

    required this.staff,

    required this.attendance,

    this.sessionRoster,
  });

  @JsonKey(name: r'memberships', required: true, includeIfNull: false)
  final List<ActivityRosterMember> memberships;

  @JsonKey(name: r'staff', required: true, includeIfNull: false)
  final List<ActivityGroupStaff> staff;

  @JsonKey(name: r'attendance', required: true, includeIfNull: false)
  final List<ActivityAttendance> attendance;

  @JsonKey(name: r'session_roster', required: false, includeIfNull: false)
  final ActivitySessionRoster? sessionRoster;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ActivityGroupRoster &&
          other.memberships == memberships &&
          other.staff == staff &&
          other.attendance == attendance &&
          other.sessionRoster == sessionRoster;

  @override
  int get hashCode =>
      memberships.hashCode +
      staff.hashCode +
      attendance.hashCode +
      sessionRoster.hashCode;

  factory ActivityGroupRoster.fromJson(Map<String, dynamic> json) =>
      _$ActivityGroupRosterFromJson(json);

  Map<String, dynamic> toJson() => _$ActivityGroupRosterToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
