//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'transfer_attendance.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class TransferAttendance {
  /// Returns a new [TransferAttendance] instance.
  TransferAttendance({
    required this.onDate,

    required this.kind,

    required this.state,
  });

  @JsonKey(name: r'on_date', required: true, includeIfNull: false)
  final String onDate;

  @JsonKey(name: r'kind', required: true, includeIfNull: false)
  final String kind;

  @JsonKey(name: r'state', required: true, includeIfNull: false)
  final String state;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is TransferAttendance &&
          other.onDate == onDate &&
          other.kind == kind &&
          other.state == state;

  @override
  int get hashCode => onDate.hashCode + kind.hashCode + state.hashCode;

  factory TransferAttendance.fromJson(Map<String, dynamic> json) =>
      _$TransferAttendanceFromJson(json);

  Map<String, dynamic> toJson() => _$TransferAttendanceToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
