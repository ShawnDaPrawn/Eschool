//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'pool_attendance_list_learners_inner.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class PoolAttendanceListLearnersInner {
  /// Returns a new [PoolAttendanceListLearnersInner] instance.
  PoolAttendanceListLearnersInner({required this.name, required this.status});

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  @JsonKey(name: r'status', required: true, includeIfNull: true)
  final String? status;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is PoolAttendanceListLearnersInner &&
          other.name == name &&
          other.status == status;

  @override
  int get hashCode => name.hashCode + (status == null ? 0 : status.hashCode);

  factory PoolAttendanceListLearnersInner.fromJson(Map<String, dynamic> json) =>
      _$PoolAttendanceListLearnersInnerFromJson(json);

  Map<String, dynamic> toJson() =>
      _$PoolAttendanceListLearnersInnerToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
