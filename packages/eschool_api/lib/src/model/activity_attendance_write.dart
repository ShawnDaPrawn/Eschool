//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/activity_attendance_entry_write.dart';
import 'package:json_annotation/json_annotation.dart';

part 'activity_attendance_write.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ActivityAttendanceWrite {
  /// Returns a new [ActivityAttendanceWrite] instance.
  ActivityAttendanceWrite({
    required this.operationId,

    required this.revision,

    required this.reason,

    required this.entries,
  });

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  // minimum: 0
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @JsonKey(name: r'reason', required: true, includeIfNull: false)
  final String reason;

  @JsonKey(name: r'entries', required: true, includeIfNull: false)
  final List<ActivityAttendanceEntryWrite> entries;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ActivityAttendanceWrite &&
          other.operationId == operationId &&
          other.revision == revision &&
          other.reason == reason &&
          other.entries == entries;

  @override
  int get hashCode =>
      operationId.hashCode +
      revision.hashCode +
      reason.hashCode +
      entries.hashCode;

  factory ActivityAttendanceWrite.fromJson(Map<String, dynamic> json) =>
      _$ActivityAttendanceWriteFromJson(json);

  Map<String, dynamic> toJson() => _$ActivityAttendanceWriteToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
