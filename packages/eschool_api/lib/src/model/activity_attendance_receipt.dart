//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/activity_attendance.dart';
import 'package:json_annotation/json_annotation.dart';

part 'activity_attendance_receipt.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ActivityAttendanceReceipt {
  /// Returns a new [ActivityAttendanceReceipt] instance.
  ActivityAttendanceReceipt({
    required this.sessionRevision,

    required this.items,

    required this.unrecordedCount,
  });

  // minimum: 1
  @JsonKey(name: r'session_revision', required: true, includeIfNull: false)
  final int sessionRevision;

  @JsonKey(name: r'items', required: true, includeIfNull: false)
  final List<ActivityAttendance> items;

  // minimum: 0
  @JsonKey(name: r'unrecorded_count', required: true, includeIfNull: false)
  final int unrecordedCount;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ActivityAttendanceReceipt &&
          other.sessionRevision == sessionRevision &&
          other.items == items &&
          other.unrecordedCount == unrecordedCount;

  @override
  int get hashCode =>
      sessionRevision.hashCode + items.hashCode + unrecordedCount.hashCode;

  factory ActivityAttendanceReceipt.fromJson(Map<String, dynamic> json) =>
      _$ActivityAttendanceReceiptFromJson(json);

  Map<String, dynamic> toJson() => _$ActivityAttendanceReceiptToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
