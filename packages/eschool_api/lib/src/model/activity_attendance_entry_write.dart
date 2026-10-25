//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'activity_attendance_entry_write.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ActivityAttendanceEntryWrite {
  /// Returns a new [ActivityAttendanceEntryWrite] instance.
  ActivityAttendanceEntryWrite({
    required this.learnerId,

    required this.status,

    required this.revision,
  });

  // minimum: 1
  @JsonKey(name: r'learner_id', required: true, includeIfNull: false)
  final int learnerId;

  @JsonKey(
    name: r'status',
    required: true,
    includeIfNull: false,
    unknownEnumValue:
        ActivityAttendanceEntryWriteStatusEnum.unknownDefaultOpenApi,
  )
  final ActivityAttendanceEntryWriteStatusEnum status;

  // minimum: 0
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ActivityAttendanceEntryWrite &&
          other.learnerId == learnerId &&
          other.status == status &&
          other.revision == revision;

  @override
  int get hashCode => learnerId.hashCode + status.hashCode + revision.hashCode;

  factory ActivityAttendanceEntryWrite.fromJson(Map<String, dynamic> json) =>
      _$ActivityAttendanceEntryWriteFromJson(json);

  Map<String, dynamic> toJson() => _$ActivityAttendanceEntryWriteToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum ActivityAttendanceEntryWriteStatusEnum {
  @JsonValue(r'present')
  present(r'present'),
  @JsonValue(r'absent')
  absent(r'absent'),
  @JsonValue(r'late')
  late_(r'late'),
  @JsonValue(r'excused')
  excused(r'excused'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const ActivityAttendanceEntryWriteStatusEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
