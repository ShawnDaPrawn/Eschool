//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/pool_attendance_list_learners_inner.dart';
import 'package:json_annotation/json_annotation.dart';

part 'pool_attendance_list.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class PoolAttendanceList {
  /// Returns a new [PoolAttendanceList] instance.
  PoolAttendanceList({
    required this.bookingId,

    required this.onDate,

    required this.learners,
  });

  @JsonKey(name: r'booking_id', required: true, includeIfNull: false)
  final int bookingId;

  @JsonKey(name: r'on_date', required: true, includeIfNull: false)
  final String onDate;

  /// The session attendance list: names and the recorded status only, no ids or contact details.
  @JsonKey(name: r'learners', required: true, includeIfNull: false)
  final List<PoolAttendanceListLearnersInner> learners;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is PoolAttendanceList &&
          other.bookingId == bookingId &&
          other.onDate == onDate &&
          other.learners == learners;

  @override
  int get hashCode => bookingId.hashCode + onDate.hashCode + learners.hashCode;

  factory PoolAttendanceList.fromJson(Map<String, dynamic> json) =>
      _$PoolAttendanceListFromJson(json);

  Map<String, dynamic> toJson() => _$PoolAttendanceListToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
