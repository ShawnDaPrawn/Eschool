//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'academic_attendance_today_entry.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicAttendanceTodayEntry {
  /// Returns a new [AcademicAttendanceTodayEntry] instance.
  AcademicAttendanceTodayEntry({
    required this.registerId,

    required this.label,

    required this.kind,

    required this.state,

    required this.source_,

    required this.amendedAt,
  });

  // minimum: 1
  @JsonKey(name: r'register_id', required: true, includeIfNull: true)
  final int? registerId;

  @JsonKey(name: r'label', required: true, includeIfNull: false)
  final String label;

  @JsonKey(name: r'kind', required: true, includeIfNull: false)
  final String kind;

  @JsonKey(name: r'state', required: true, includeIfNull: false)
  final String state;

  @JsonKey(name: r'source', required: true, includeIfNull: false)
  final String source_;

  @JsonKey(name: r'amended_at', required: true, includeIfNull: true)
  final String? amendedAt;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicAttendanceTodayEntry &&
          other.registerId == registerId &&
          other.label == label &&
          other.kind == kind &&
          other.state == state &&
          other.source_ == source_ &&
          other.amendedAt == amendedAt;

  @override
  int get hashCode =>
      (registerId == null ? 0 : registerId.hashCode) +
      label.hashCode +
      kind.hashCode +
      state.hashCode +
      source_.hashCode +
      (amendedAt == null ? 0 : amendedAt.hashCode);

  factory AcademicAttendanceTodayEntry.fromJson(Map<String, dynamic> json) =>
      _$AcademicAttendanceTodayEntryFromJson(json);

  Map<String, dynamic> toJson() => _$AcademicAttendanceTodayEntryToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
