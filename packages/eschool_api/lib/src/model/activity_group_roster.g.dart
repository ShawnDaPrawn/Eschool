// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'activity_group_roster.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ActivityGroupRoster _$ActivityGroupRosterFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('ActivityGroupRoster', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['memberships', 'staff', 'attendance']);
  final val = ActivityGroupRoster(
    memberships: $checkedConvert(
      'memberships',
      (v) => (v as List<dynamic>)
          .map((e) => ActivityRosterMember.fromJson(e as Map<String, dynamic>))
          .toList(),
    ),
    staff: $checkedConvert(
      'staff',
      (v) => (v as List<dynamic>)
          .map((e) => ActivityGroupStaff.fromJson(e as Map<String, dynamic>))
          .toList(),
    ),
    attendance: $checkedConvert(
      'attendance',
      (v) => (v as List<dynamic>)
          .map((e) => ActivityAttendance.fromJson(e as Map<String, dynamic>))
          .toList(),
    ),
    sessionRoster: $checkedConvert(
      'session_roster',
      (v) => v == null
          ? null
          : ActivitySessionRoster.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
}, fieldKeyMap: const {'sessionRoster': 'session_roster'});

Map<String, dynamic> _$ActivityGroupRosterToJson(
  ActivityGroupRoster instance,
) => <String, dynamic>{
  'memberships': instance.memberships.map((e) => e.toJson()).toList(),
  'staff': instance.staff.map((e) => e.toJson()).toList(),
  'attendance': instance.attendance.map((e) => e.toJson()).toList(),
  'session_roster': ?instance.sessionRoster?.toJson(),
};
